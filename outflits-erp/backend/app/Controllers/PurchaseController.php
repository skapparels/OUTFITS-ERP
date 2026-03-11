<?php

namespace App\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseController
{
    public function index(Request $request) { return PurchaseOrder::query()->with('items')->paginate($request->integer('per_page', 30)); }
    public function store(Request $request) { return PurchaseOrder::query()->create($request->validate(['supplier_id'=>'required|exists:suppliers,id','order_date'=>'required|date','status'=>'required|string'])); }
    public function show(PurchaseOrder $purchase) { return $purchase->load('items'); }
}
