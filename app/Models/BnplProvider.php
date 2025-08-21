<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BnplProvider extends Model
{
    protected $fillable = [
        'name',
        'logo_path',
        'fee_percentage',
        'installment_count',
        'is_active',
        'config'
    ];

    protected $casts = [
        'fee_percentage' => 'decimal:2',
        'installment_count' => 'integer',
        'is_active' => 'boolean',
        'config' => 'array'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getLogoUrlAttribute()
    {
        if ($this->logo_path) {
            return asset('storage/' . $this->logo_path);
        }
        return null;
    }

    public function calculateInstallmentAmount($basePrice, $vatPercentage = 15)
    {
        $priceWithVat = $basePrice * (1 + ($vatPercentage / 100));
        $priceWithFee = $priceWithVat * (1 + ($this->fee_percentage / 100));
        return round($priceWithFee / $this->installment_count, 2);
    }
}

