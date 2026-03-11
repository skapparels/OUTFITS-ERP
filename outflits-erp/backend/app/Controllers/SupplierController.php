<?php

namespace App\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController
{
    public function index(Request $request) { return Supplier::query()->paginate($request->integer('per_page', 30)); }
    public function store(Request $request) { return Supplier::query()->create($request->validate(['name'=>'required','email'=>'nullable|email','phone'=>'nullable|string'])); }
    public function show(Supplier $supplier) { return $supplier; }
    public function update(Request $request, Supplier $supplier) { $supplier->update($request->all()); return $supplier; }
    public function destroy(Supplier $supplier) { $supplier->delete(); return response()->noContent(); }
}
