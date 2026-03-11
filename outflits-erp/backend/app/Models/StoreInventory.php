<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class StoreInventory extends Model { use HasFactory; protected $guarded = []; protected $table = "store_inventory"; public function variant(){return $this->belongsTo(ProductVariant::class, "product_variant_id");} }
