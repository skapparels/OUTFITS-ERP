<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Product extends Model { use HasFactory; protected $guarded = []; public function style(){return $this->belongsTo(Style::class);} public function variants(){return $this->hasMany(ProductVariant::class);} }
