<?php

namespace App\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController
{
    public function index(Request $request) { return Customer::query()->paginate($request->integer('per_page', 30)); }
    public function store(Request $request) { return Customer::query()->create($request->validate(['name'=>'required','phone'=>'required','email'=>'nullable|email','membership_level'=>'nullable|string'])); }
    public function show(Customer $customer) { return $customer; }
    public function update(Request $request, Customer $customer) { $customer->update($request->all()); return $customer; }
    public function destroy(Customer $customer) { $customer->delete(); return response()->noContent(); }
}
