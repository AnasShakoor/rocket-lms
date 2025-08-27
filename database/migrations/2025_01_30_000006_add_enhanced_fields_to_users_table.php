<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('scfhs_number', 15)->nullable()->after('email')->comment('SCFHS registration number');
            $table->string('id_number', 15)->nullable()->after('scfhs_number')->comment('National ID number');
            $table->string('nationality', 3)->nullable()->after('id_number')->comment('ISO country code');
            $table->boolean('newsletter_subscribed')->default(true)->after('nationality')->comment('Newsletter subscription status');
            $table->timestamp('newsletter_subscribed_at')->nullable()->after('newsletter_subscribed');
            $table->string('terms_accepted_version')->nullable()->after('newsletter_subscribed_at')->comment('Version of T&C accepted');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_accepted_version');
            $table->string('terms_accepted_ip')->nullable()->after('terms_accepted_at')->comment('IP address when T&C accepted');
            $table->string('phone_normalized')->nullable()->after('mobile')->comment('Normalized phone number (966XXXXXXXXX)');
            
            // Add indexes for performance
            $table->index('scfhs_number');
            $table->index('id_number');
            $table->index('phone_normalized');
            $table->index('newsletter_subscribed');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['scfhs_number']);
            $table->dropIndex(['id_number']);
            $table->dropIndex(['phone_normalized']);
            $table->dropIndex(['newsletter_subscribed']);
            
            $table->dropColumn([
                'scfhs_number', 'id_number', 'nationality', 'newsletter_subscribed',
                'newsletter_subscribed_at', 'terms_accepted_version', 'terms_accepted_at',
                'terms_accepted_ip', 'phone_normalized'
            ]);
        });
    }
};
