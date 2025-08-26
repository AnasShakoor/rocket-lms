<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Section;
use App\Models\Permission;

class BnplPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        echo "🚀 Adding BNPL Providers permissions...\n";

        // First, ensure the LMS Operational section exists
        Section::updateOrCreate(
            ['id' => 3180], 
            ['name' => 'admin_lms_operational', 'caption' => 'LMS Operational']
        );

        // Create BNPL sections
        $bnplSections = [
            ['id' => 3186, 'name' => 'admin_bnpl_providers_access', 'caption' => 'BNPL Providers Access'],
            ['id' => 3187, 'name' => 'admin_bnpl_providers_create', 'caption' => 'Create BNPL Providers'],
            ['id' => 3188, 'name' => 'admin_bnpl_providers_edit', 'caption' => 'Edit BNPL Providers'],
            ['id' => 3189, 'name' => 'admin_bnpl_providers_delete', 'caption' => 'Delete BNPL Providers'],
        ];

        foreach ($bnplSections as $section) {
            Section::updateOrCreate(
                ['id' => $section['id']], 
                [
                    'name' => $section['name'], 
                    'caption' => $section['caption'],
                    'section_group_id' => 3180
                ]
            );
            echo "   ✅ Created section: {$section['name']}\n";
        }

        // Create Enhanced Reports sections
        Section::updateOrCreate(
            ['id' => 3200], 
            ['name' => 'admin_enhanced_reports', 'caption' => 'Enhanced Reports']
        );

        $enhancedReportSections = [
            ['id' => 3201, 'name' => 'admin_enhanced_reports_access', 'caption' => 'Enhanced Reports Access'],
            ['id' => 3202, 'name' => 'admin_enhanced_reports_export', 'caption' => 'Export Reports'],
            ['id' => 3203, 'name' => 'admin_enhanced_reports_archive', 'caption' => 'Archive Records'],
        ];

        foreach ($enhancedReportSections as $section) {
            Section::updateOrCreate(
                ['id' => $section['id']], 
                [
                    'name' => $section['name'], 
                    'caption' => $section['caption'],
                    'section_group_id' => 3200
                ]
            );
            echo "   ✅ Created section: {$section['name']}\n";
        }

        // Now add permissions for admin role (role_id = 2)
        $permissions = [
            // BNPL Providers permissions
            ['id' => 3186, 'section_id' => 3186],
            ['id' => 3187, 'section_id' => 3187],
            ['id' => 3188, 'section_id' => 3188],
            ['id' => 3189, 'section_id' => 3189],
            
            // Enhanced Reports permissions
            ['id' => 3201, 'section_id' => 3201],
            ['id' => 3202, 'section_id' => 3202],
            ['id' => 3203, 'section_id' => 3203],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['id' => $permission['id']], 
                [
                    'role_id' => 2, 
                    'section_id' => $permission['section_id'], 
                    'allow' => 1
                ]
            );
            echo "   ✅ Added permission: {$permission['id']}\n";
        }

        echo "🎉 BNPL Providers permissions added successfully!\n";
        echo "You should now see the BNPL Providers option in your admin panel sidebar.\n";
    }
}
