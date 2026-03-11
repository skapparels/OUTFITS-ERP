<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function shifts()
    {
        return $this->hasMany(ShiftSchedule::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function tasks()
    {
        return $this->hasMany(StaffTask::class);
    }

    public function overtimeEntries()
    {
        return $this->hasMany(OvertimeEntry::class);
    }
}
