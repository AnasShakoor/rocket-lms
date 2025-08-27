<?php

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class InstallmentStep extends Model implements TranslatableContract
{
    use Translatable;

    protected $table = 'installment_steps';
    public $timestamps = false;
    protected $guarded = ['id'];

    public $translatedAttributes = ['title'];

    public function getTitleAttribute()
    {
        return getTranslateAttributeValue($this, 'title');
    }

    public function getAmountAttribute()
    {
        // Ensure amount is numeric
        return (float) $this->attributes['amount'];
    }


    /*********
     * Relations
     * */
    public function installment()
    {
        return $this->belongsTo(Installment::class, 'installment_id', 'id');
    }

    /*********
     * Helpers
     * */

    public function getPrice($itemPrice = 1)
    {
        // Ensure values are numeric
        $itemPrice = (float) $itemPrice;
        $amount = (float) $this->amount;

        if ($this->amount_type == 'percent') {
            return ($itemPrice * $amount) / 100;
        } else {
            return $amount;
        }
    }

    public function getDeadlineTitle($itemPrice = 1)
    {
        $percentText = ($this->amount_type == 'percent') ? "({$this->amount}%)" : '';

        // $100 after 30 days
        return trans('update.amount_after_n_days', ['amount' => handlePrice($this->getPrice($itemPrice)), 'days' => $this->deadline, 'percent' => $percentText]);
    }
}
