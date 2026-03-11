<?php

namespace App\Controllers;

use App\Models\Customer;
use App\Models\CustomerRecommendation;
use App\Models\LoyaltyTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController
{
    public function index(Request $request): JsonResponse
    {
        $rows = Customer::query()
            ->when($request->boolean('vip_only'), fn ($q) => $q->where('is_vip', true))
            ->when($request->filled('membership_level'), fn ($q) => $q->where('membership_level', $request->string('membership_level')))
            ->orderByDesc('lifetime_value')
            ->paginate($request->integer('per_page', 30));

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'unique:customers,phone'],
            'email' => ['nullable', 'email'],
            'membership_level' => ['nullable', 'string', 'max:30'],
            'is_vip' => ['nullable', 'boolean'],
            'date_of_birth' => ['nullable', 'date'],
            'preferences' => ['nullable', 'array'],
        ]);

        $customer = Customer::query()->create($payload + [
            'membership_level' => $payload['membership_level'] ?? 'base',
        ]);

        return response()->json($customer, 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json($customer->load(['visits', 'loyaltyTransactions', 'recommendations.variant']));
    }

    public function update(Request $request, Customer $customer): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['sometimes', 'string', 'max:30', 'unique:customers,phone,' . $customer->id],
            'email' => ['nullable', 'email'],
            'membership_level' => ['sometimes', 'string', 'max:30'],
            'is_vip' => ['sometimes', 'boolean'],
            'date_of_birth' => ['nullable', 'date'],
            'preferences' => ['nullable', 'array'],
        ]);

        $customer->update($payload);
        return response()->json($customer->refresh());
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();
        return response()->json([], 204);
    }

    public function addVisit(Request $request, Customer $customer): JsonResponse
    {
        $payload = $request->validate([
            'store_id' => ['nullable', 'exists:stores,id'],
            'staff_id' => ['nullable', 'exists:staff,id'],
            'channel' => ['nullable', 'string', 'max:30'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $visit = $customer->visits()->create($payload + ['channel' => $payload['channel'] ?? 'store']);
        $customer->update(['last_visit_at' => now()]);

        return response()->json($visit, 201);
    }

    public function addRecommendation(Request $request, Customer $customer): JsonResponse
    {
        $payload = $request->validate([
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'source' => ['nullable', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'in:pending,shown,accepted,rejected'],
        ]);

        $rec = $customer->recommendations()->create([
            'product_variant_id' => $payload['product_variant_id'] ?? null,
            'source' => $payload['source'] ?? 'manual',
            'score' => $payload['score'] ?? 0,
            'reason' => $payload['reason'] ?? null,
            'status' => $payload['status'] ?? 'pending',
        ]);

        return response()->json($rec->load('variant.product'), 201);
    }

    public function adjustLoyalty(Request $request, Customer $customer): JsonResponse
    {
        $payload = $request->validate([
            'points' => ['required', 'integer', 'not_in:0'],
            'type' => ['required', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:500'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $updated = DB::transaction(function () use ($customer, $payload) {
            $newBalance = max(0, $customer->reward_points + $payload['points']);

            LoyaltyTransaction::query()->create([
                'customer_id' => $customer->id,
                'points' => $payload['points'],
                'type' => $payload['type'],
            ]);

            $customer->update([
                'reward_points' => $newBalance,
                'lifetime_value' => $customer->lifetime_value + ($payload['amount'] ?? 0),
            ]);

            return $customer->refresh();
        });

        return response()->json($updated);
    }

    public function loyaltyLedger(Customer $customer, Request $request): JsonResponse
    {
        $ledger = $customer->loyaltyTransactions()
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return response()->json($ledger);
    }

    public function behaviorSummary(Customer $customer): JsonResponse
    {
        $visitsCount = $customer->visits()->count();
        $acceptedRecommendations = CustomerRecommendation::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'accepted')
            ->count();

        return response()->json([
            'customer_id' => $customer->id,
            'membership_level' => $customer->membership_level,
            'is_vip' => $customer->is_vip,
            'reward_points' => $customer->reward_points,
            'lifetime_value' => $customer->lifetime_value,
            'total_visits' => $visitsCount,
            'accepted_recommendations' => $acceptedRecommendations,
            'last_visit_at' => $customer->last_visit_at,
            'preferences' => $customer->preferences,
        ]);
    }
}
