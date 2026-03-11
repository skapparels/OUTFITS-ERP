<?php

namespace App\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController
{
    public function index(Request $request)
    {
        return Store::query()->with('franchise')->paginate($request->integer('per_page', 25));
    }

    public function store(Request $request)
    {
        return Store::query()->create($request->validate([
            'franchise_id' => ['nullable', 'exists:franchises,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:stores,code'],
            'city' => ['nullable', 'string', 'max:120'],
        ]));
    }

    public function show(Store $store)
    {
        return $store->load('franchise');
    }

    public function update(Request $request, Store $store)
    {
        $store->update($request->validate([
            'franchise_id' => ['sometimes', 'nullable', 'exists:franchises,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', 'max:50', 'unique:stores,code,' . $store->id],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
        ]));

        return $store->refresh();
    }

    public function destroy(Store $store)
    {
        $store->delete();
        return response()->noContent();
    }
}
