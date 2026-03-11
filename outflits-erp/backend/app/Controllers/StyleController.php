<?php

namespace App\Controllers;

use App\Models\Style;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StyleController
{
    public function index(Request $request)
    {
        return Style::query()
            ->with(['collection', 'products.variants'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->paginate($request->integer('per_page', 25));
    }

    public function store(Request $request)
    {
        return Style::query()->create($request->validate([
            'collection_id' => ['required', 'exists:collections,id'],
            'style_code' => ['nullable', 'string', 'max:100', 'unique:styles,style_code'],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive,clearance,discontinued'],
        ]) + ['status_changed_at' => now()]);
    }

    public function show(Style $style)
    {
        return $style->load(['collection', 'products.variants']);
    }

    public function update(Request $request, Style $style)
    {
        $payload = $request->validate([
            'collection_id' => ['sometimes', 'exists:collections,id'],
            'style_code' => ['sometimes', 'nullable', 'string', 'max:100', 'unique:styles,style_code,' . $style->id],
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'in:active,inactive,clearance,discontinued'],
        ]);

        if (isset($payload['status'])) {
            $payload['status_changed_at'] = now();
            if ($payload['status'] === 'clearance') {
                $payload['clearance_at'] = now();
            }
        }

        $style->update($payload);

        return $style->refresh();
    }

    public function moveToClearance(Style $style): JsonResponse
    {
        $style->update([
            'status' => 'clearance',
            'status_changed_at' => now(),
            'clearance_at' => now(),
        ]);

        return response()->json([
            'message' => 'Style moved to clearance successfully.',
            'style' => $style->refresh(),
        ]);
    }
}
