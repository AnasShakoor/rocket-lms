<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateSalesTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing sales records to set the type field based on existing data
        DB::table('sales')->whereNotNull('webinar_id')->update(['type' => 'webinar']);
        DB::table('sales')->whereNotNull('bundle_id')->update(['type' => 'bundle']);
        DB::table('sales')->whereNotNull('meeting_id')->update(['type' => 'meeting']);
        DB::table('sales')->whereNotNull('subscribe_id')->update(['type' => 'subscribe']);
        DB::table('sales')->whereNotNull('product_order_id')->update(['type' => 'product']);
        DB::table('sales')->whereNotNull('gift_id')->update(['type' => 'gift']);
        DB::table('sales')->whereNotNull('promotion_id')->update(['type' => 'promotion']);
        DB::table('sales')->whereNotNull('registration_package_id')->update(['type' => 'registrationPackage']);
        DB::table('sales')->whereNotNull('installment_payment_id')->update(['type' => 'installmentPayment']);

        // Set default type for records that don't have any specific ID
        DB::table('sales')->whereNull('type')->update(['type' => 'webinar']);

        // Set default values for other missing fields
        DB::table('sales')->whereNull('access_to_purchased_item')->update(['access_to_purchased_item' => true]);
        DB::table('sales')->whereNull('manual_added')->update(['manual_added' => false]);
        DB::table('sales')->whereNull('tax')->update(['tax' => 0]);
        DB::table('sales')->whereNull('commission')->update(['commission' => 0]);
        DB::table('sales')->whereNull('discount')->update(['discount' => 0]);
        DB::table('sales')->whereNull('total_amount')->update(['total_amount' => DB::raw('amount')]);
        DB::table('sales')->whereNull('product_delivery_fee')->update(['product_delivery_fee' => 0]);
    }
}
