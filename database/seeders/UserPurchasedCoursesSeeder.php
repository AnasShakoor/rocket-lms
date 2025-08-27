<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Webinar;
use App\Models\Order;
use App\Models\Sale;
use App\User;
use Illuminate\Support\Facades\DB;

class UserPurchasedCoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the user with email user@gmail.com
        $user = User::where('email', 'user@gmail.com')->first();

        if (!$user) {
            $this->command->error('User with email user@gmail.com not found!');
            return;
        }

        // Get the teacher (we'll use the first teacher or create one)
        $teacher = User::where('role_name', 'teacher')->first();

        if (!$teacher) {
            $this->command->error('No teacher found!');
            return;
        }

        // Create 2 webinars with certificates enabled
        $webinars = [
            [
                'id' => 1001,
                'teacher_id' => $teacher->id,
                'creator_id' => $teacher->id,
                'slug' => 'sample-course-1',
                'start_date' => time(),
                'image_cover' => '/assets/default/img/course.jpg',
                'thumbnail' => '/assets/default/img/course.jpg',
                'capacity' => 100,
                'price' => 9999, // Price in cents
                'support' => true,
                'certificate' => true, // Enable certificates
                'status' => 'active',
                'type' => 'course',
                'duration' => 1800, // 30 minutes in seconds
                'downloadable' => false,
                'partner_instructor' => false,
                'subscribe' => false,
                'private' => false,
                'forum' => false,
                'enable_waitlist' => false,
                'created_at' => time(),
                'updated_at' => time(),
            ],
            [
                'id' => 1002,
                'teacher_id' => $teacher->id,
                'creator_id' => $teacher->id,
                'slug' => 'sample-course-2',
                'start_date' => time(),
                'image_cover' => '/assets/default/img/course.jpg',
                'thumbnail' => '/assets/default/img/course.jpg',
                'capacity' => 150,
                'price' => 14999, // Price in cents
                'support' => true,
                'certificate' => true, // Enable certificates
                'status' => 'active',
                'type' => 'course',
                'duration' => 2700, // 45 minutes in seconds
                'downloadable' => false,
                'partner_instructor' => false,
                'subscribe' => false,
                'private' => false,
                'forum' => false,
                'enable_waitlist' => false,
                'created_at' => time(),
                'updated_at' => time(),
            ]
        ];

                foreach ($webinars as $webinarData) {
            $this->command->info("Creating webinar with ID: {$webinarData['id']}");

            // Create or update webinar using DB to avoid translatable issues
            $webinarId = $webinarData['id'];
            unset($webinarData['id']); // Remove ID for insert

            DB::table('webinars')->updateOrInsert(
                ['id' => $webinarId],
                $webinarData
            );

            $this->command->info("Webinar created/updated: {$webinarId}");

            // Add translations for the webinar
            $this->addWebinarTranslations(null, $webinarId);

            $this->command->info("Translations added for webinar: {$webinarId}");

            // Create order for this webinar
            $order = Order::updateOrCreate(
                ['id' => 2000 + $webinarId],
                [
                    'user_id' => $user->id,
                    'status' => 'paid',
                    'payment_method' => 'credit',
                    'amount' => $webinarData['price'], // Already in cents
                    'total_amount' => $webinarData['price'],
                    'created_at' => time(),
                ]
            );

            $this->command->info("Order created: {$order->id}");

            // Create sale record linking user to webinar using DB to avoid timestamp issues
            $saleId = 3000 + $webinarId;
            DB::table('sales')->updateOrInsert(
                ['id' => $saleId],
                [
                    'buyer_id' => $user->id,
                    'seller_id' => $teacher->id,
                    'order_id' => $order->id,
                    'webinar_id' => $webinarId,
                    'type' => 'webinar',
                    'payment_method' => 'credit',
                    'amount' => $webinarData['price'], // Already in cents
                    'total_amount' => $webinarData['price'],
                    'created_at' => time(),
                ]
            );

            $this->command->info("Sale record created: {$saleId}");

            $this->command->info("Created webinar with ID {$webinarId} and linked to user {$user->email}");
        }

        $this->command->info('Successfully created 2 purchased courses for user@gmail.com with certificates enabled!');
    }

    /**
     * Add translations for webinar
     */
    private function addWebinarTranslations($webinar, $webinarId)
    {
        $titles = [
            'en' => "Sample Course " . ($webinarId - 1000),
            'ar' => "دورة تدريبية " . ($webinarId - 1000),
            'es' => "Curso de Muestra " . ($webinarId - 1000),
        ];

        foreach ($titles as $locale => $title) {
            DB::table('webinar_translations')->updateOrInsert(
                [
                    'webinar_id' => $webinarId,
                    'locale' => $locale,
                ],
                [
                    'title' => $title,
                    'description' => "This is a sample course description for {$title}. It includes comprehensive content and materials for learning.",
                    'seo_description' => "Learn and master new skills with {$title}. Comprehensive online course with certificate upon completion.",
                ]
            );
        }
    }
}
