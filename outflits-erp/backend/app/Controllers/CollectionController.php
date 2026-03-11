<?php

namespace App\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;

class CollectionController
{
    public function index(Request $request)
    {
        return Collection::query()->withCount('styles')->paginate($request->integer('per_page', 25));
    }

    public function store(Request $request)
    {
        return Collection::query()->create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'season_start' => ['nullable', 'date'],
            'season_end' => ['nullable', 'date', 'after_or_equal:season_start'],
        ]));
    }

    public function show(Collection $collection)
    {
        return $collection->load('styles');
    }

    public function update(Request $request, Collection $collection)
    {
        $collection->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'season_start' => ['sometimes', 'nullable', 'date'],
            'season_end' => ['sometimes', 'nullable', 'date'],
        ]));

        return $collection->refresh();
    }

    public function destroy(Collection $collection)
    {
        $collection->delete();
        return response()->noContent();
    }
}
