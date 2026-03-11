<?php

namespace App\Controllers;

use App\Models\Franchise;
use Illuminate\Http\Request;

class FranchiseController
{
    public function index(Request $request)
    {
        return Franchise::query()->withCount('stores')->paginate($request->integer('per_page', 25));
    }

    public function store(Request $request)
    {
        return Franchise::query()->create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
        ]));
    }

    public function show(Franchise $franchise)
    {
        return $franchise->load('stores');
    }

    public function update(Request $request, Franchise $franchise)
    {
        $franchise->update($request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'credit_limit' => ['sometimes', 'numeric', 'min:0'],
        ]));

        return $franchise->refresh();
    }

    public function destroy(Franchise $franchise)
    {
        $franchise->delete();
        return response()->noContent();
    }
}
