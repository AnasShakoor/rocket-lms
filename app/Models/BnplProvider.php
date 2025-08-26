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
        'config',
        'surcharge_percentage',
        'fee_description',
        'bnpl_fee',
        'public_api_key',
        'secret_api_key',
        'merchant_code',
        'merchant_id',
        'app_id',
        'app_secret_key',
        'widget_access_key'
    ];

    protected $casts = [
        'fee_percentage' => 'decimal:2',
        'installment_count' => 'integer',
        'is_active' => 'boolean',
        'config' => 'array',
        'surcharge_percentage' => 'decimal:2',
        'bnpl_fee' => 'decimal:2'
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

