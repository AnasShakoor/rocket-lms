<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Section;
use App\Models\Permission;

class CertificateRequestsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        echo "🚀 Adding Certificate Requests permissions...\n";

        // First, ensure the LMS Operational section exists (or create a new one for certificates)
        Section::updateOrCreate(
            ['id' => 3210],
            ['name' => 'admin_certificate_management', 'caption' => 'Certificate Management']
        );

        // Create Certificate Requests sections
        $certificateSections = [
            ['id' => 3211, 'name' => 'admin_certificate_requests_list', 'caption' => 'Certificate Requests List'],
            ['id' => 3212, 'name' => 'admin_certificate_requests_view', 'caption' => 'View Certificate Request Details'],
            ['id' => 3213, 'name' => 'admin_certificate_requests_edit', 'caption' => 'Edit Certificate Request Status'],
        ];

        foreach ($certificateSections as $section) {
            Section::updateOrCreate(
                ['id' => $section['id']],
                [
                    'name' => $section['name'],
                    'caption' => $section['caption'],
                    'section_group_id' => 3210
                ]
            );
            echo "   ✅ Created section: {$section['name']}\n";
        }

        // Now add permissions for admin role (role_id = 2)
        $permissions = [
            // Certificate Requests permissions
            ['id' => 3211, 'section_id' => 3211],
            ['id' => 3212, 'section_id' => 3212],
            ['id' => 3213, 'section_id' => 3213],
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

        echo "🎉 Certificate Requests permissions added successfully!\n";
        echo "You should now see the Certificate Requests option in your admin panel sidebar.\n";
    }
}
