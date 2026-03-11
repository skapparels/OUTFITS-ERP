<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'preferences' => 'array',
        'is_vip' => 'boolean',
    ];

    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function visits()
    {
        return $this->hasMany(CustomerVisit::class);
    }

    public function recommendations()
    {
        return $this->hasMany(CustomerRecommendation::class);
    }
}
