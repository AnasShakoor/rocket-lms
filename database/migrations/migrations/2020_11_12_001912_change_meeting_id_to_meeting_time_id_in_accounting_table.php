<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChangeMeetingIdToMeetingTimeIdInAccountingTable extends Migration
{
    public function up()
    {
        // Drop FK if it exists
        $fkExists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
              AND TABLE_NAME = 'accounting' 
              AND COLUMN_NAME = 'meeting_id' 
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if (!empty($fkExists)) {
            DB::statement("ALTER TABLE `accounting` DROP FOREIGN KEY `{$fkExists[0]->CONSTRAINT_NAME}`;");
        }

        // Rename the column
        $columnExists = Schema::hasColumn('accounting', 'meeting_id');
        if ($columnExists) {
            DB::statement("ALTER TABLE `accounting` CHANGE COLUMN `meeting_id` `meeting_time_id` INT UNSIGNED NULL");
        }
    }

    public function down()
    {
        // Reverse rename if needed
        if (Schema::hasColumn('accounting', 'meeting_time_id')) {
            DB::statement("ALTER TABLE `accounting` CHANGE COLUMN `meeting_time_id` `meeting_id` INT UNSIGNED NULL");
        }
    }
}
