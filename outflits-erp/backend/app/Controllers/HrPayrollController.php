<?php

namespace App\Controllers;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\OvertimeEntry;
use App\Models\Payroll;
use App\Models\ShiftSchedule;
use App\Models\Staff;
use App\Models\StaffTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HrPayrollController
{
    public function staffIndex(Request $request): JsonResponse
    {
        $rows = Staff::query()
            ->withCount(['attendances', 'tasks', 'leaveRequests'])
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->when($request->filled('employment_status'), fn ($q) => $q->where('employment_status', $request->string('employment_status')))
            ->paginate($request->integer('per_page', 30));

        return response()->json($rows);
    }

    public function staffStore(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'store_id' => ['nullable', 'exists:stores,id'],
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', 'string', 'max:80'],
            'joining_date' => ['required', 'date'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'employment_status' => ['nullable', 'in:active,inactive,terminated'],
        ]);

        $staff = Staff::query()->create($payload + [
            'employee_code' => 'EMP-' . now()->format('YmdHis') . '-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
            'basic_salary' => $payload['basic_salary'] ?? 0,
            'employment_status' => $payload['employment_status'] ?? 'active',
        ]);

        return response()->json($staff, 201);
    }

    public function staffShow(Staff $staff): JsonResponse
    {
        return response()->json($staff->load(['attendances', 'tasks', 'shifts', 'leaveRequests', 'overtimeEntries', 'payrolls']));
    }

    public function staffUpdate(Request $request, Staff $staff): JsonResponse
    {
        $payload = $request->validate([
            'store_id' => ['nullable', 'exists:stores,id'],
            'name' => ['sometimes', 'string', 'max:120'],
            'role' => ['sometimes', 'string', 'max:80'],
            'basic_salary' => ['sometimes', 'numeric', 'min:0'],
            'employment_status' => ['sometimes', 'in:active,inactive,terminated'],
        ]);

        $staff->update($payload);
        return response()->json($staff->refresh());
    }

    public function staffDestroy(Staff $staff): JsonResponse
    {
        $staff->delete();
        return response()->json([], 204);
    }

    public function markAttendance(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'staff_id' => ['required', 'exists:staff,id'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:present,absent,half_day,leave'],
        ]);

        $attendance = Attendance::query()->updateOrCreate(
            ['staff_id' => $payload['staff_id'], 'date' => $payload['date']],
            ['status' => $payload['status']]
        );

        return response()->json($attendance, 201);
    }

    public function attendanceReport(Request $request): JsonResponse
    {
        $rows = Attendance::query()
            ->with('staff')
            ->when($request->filled('staff_id'), fn ($q) => $q->where('staff_id', $request->integer('staff_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('date', '<=', $request->date('to')))
            ->orderByDesc('date')
            ->paginate($request->integer('per_page', 50));

        return response()->json($rows);
    }

    public function assignShift(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'staff_id' => ['required', 'exists:staff,id'],
            'shift_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'shift_name' => ['nullable', 'string', 'max:60'],
        ]);

        $shift = ShiftSchedule::query()->updateOrCreate(
            ['staff_id' => $payload['staff_id'], 'shift_date' => $payload['shift_date']],
            [
                'start_time' => $payload['start_time'],
                'end_time' => $payload['end_time'],
                'shift_name' => $payload['shift_name'] ?? null,
            ]
        );

        return response()->json($shift, 201);
    }

    public function requestLeave(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'staff_id' => ['required', 'exists:staff,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'leave_type' => ['required', 'string', 'max:50'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $leave = LeaveRequest::query()->create($payload);

        return response()->json($leave, 201);
    }

    public function reviewLeave(Request $request, LeaveRequest $leave): JsonResponse
    {
        $payload = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);

        $leave->update([
            'status' => $payload['status'],
            'reviewed_by' => auth('api')->id(),
            'reviewed_at' => now(),
        ]);

        return response()->json($leave->refresh());
    }

    public function addTask(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'staff_id' => ['required', 'exists:staff,id'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'in:pending,in_progress,done'],
            'due_date' => ['nullable', 'date'],
        ]);

        $task = StaffTask::query()->create($payload + ['status' => $payload['status'] ?? 'pending']);
        return response()->json($task, 201);
    }

    public function addOvertime(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'staff_id' => ['required', 'exists:staff,id'],
            'date' => ['required', 'date'],
            'hours' => ['required', 'numeric', 'min:0.5'],
            'rate_per_hour' => ['required', 'numeric', 'min:0'],
        ]);

        $entry = OvertimeEntry::query()->create($payload);
        return response()->json($entry, 201);
    }

    public function reviewOvertime(Request $request, OvertimeEntry $entry): JsonResponse
    {
        $payload = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
        ]);

        $entry->update([
            'status' => $payload['status'],
            'approved_by' => auth('api')->id(),
        ]);

        return response()->json($entry->refresh());
    }

    public function generatePayroll(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'staff_id' => ['nullable', 'exists:staff,id'],
        ]);

        $staffRows = Staff::query()
            ->where('employment_status', 'active')
            ->when($payload['staff_id'] ?? null, fn ($q) => $q->where('id', $payload['staff_id']))
            ->get();

        $created = [];

        DB::transaction(function () use (&$created, $staffRows, $payload) {
            foreach ($staffRows as $staff) {
                $periodStart = Carbon::createFromFormat('Y-m', $payload['month'])->startOfMonth();
                $periodEnd = Carbon::createFromFormat('Y-m', $payload['month'])->endOfMonth();

                $approvedOvertime = OvertimeEntry::query()
                    ->where('staff_id', $staff->id)
                    ->where('status', 'approved')
                    ->whereDate('date', '>=', $periodStart)
                    ->whereDate('date', '<=', $periodEnd)
                    ->get();

                $overtimePay = $approvedOvertime->sum(fn ($x) => $x->hours * $x->rate_per_hour);
                $gross = (float) $staff->basic_salary + (float) $overtimePay;
                $net = $gross;

                $payroll = Payroll::query()->updateOrCreate(
                    ['staff_id' => $staff->id, 'month' => $payload['month']],
                    ['gross_pay' => $gross, 'net_pay' => $net]
                );

                $created[] = $payroll;
            }
        });

        return response()->json(['generated_count' => count($created), 'rows' => $created]);
    }

    public function payrollIndex(Request $request): JsonResponse
    {
        $rows = Payroll::query()
            ->with('staff')
            ->when($request->filled('month'), fn ($q) => $q->where('month', $request->string('month')))
            ->paginate($request->integer('per_page', 50));

        return response()->json($rows);
    }
}
