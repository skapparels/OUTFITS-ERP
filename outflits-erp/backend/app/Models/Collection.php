<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Collection extends Model { use HasFactory; protected $guarded = []; public function styles(){return $this->hasMany(Style::class);} }
