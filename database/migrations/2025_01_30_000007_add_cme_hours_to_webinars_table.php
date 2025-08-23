<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('webinars', function (Blueprint $table) {
            if (!Schema::hasColumn('webinars', 'cme_hours')) {
                $table->decimal('cme_hours', 4, 1)->default(0.0)->after('price')->comment('CME credit hours for this course');
            }
        });
    }

    public function down()
    {
        Schema::table('webinars', function (Blueprint $table) {
            if (Schema::hasColumn('webinars', 'cme_hours')) {
                $table->dropColumn('cme_hours');
            }
        });
    }
};

