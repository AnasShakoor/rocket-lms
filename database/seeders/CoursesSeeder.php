<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Webinar;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if users exist, if not, create them
        if (User::count() === 0) {
            $this->command->info('No users found. Creating default users first...');
            $this->call(UsersTableSeeder::class);
        }

        // Get or create some categories
        $categories = $this->getOrCreateCategories();

        // Get existing users (teacher and creator)
        $teacher = User::where('role_name', 'teacher')->first();
        $creator = User::where('role_name', 'admin')->first();

        if (!$teacher || !$creator) {
            $this->command->error('Required users (teacher/admin) not found. Please run UsersTableSeeder first.');
            return;
        }

        $this->command->info('Found teacher: ' . $teacher->full_name);
        $this->command->info('Found creator: ' . $creator->full_name);

        // Course data
        $courses = [
            [
                'title' => 'Complete Web Development Bootcamp',
                'description' => 'Learn web development from scratch with HTML, CSS, JavaScript, and modern frameworks.',
                'summary' => 'Master web development fundamentals and build real-world projects.',
                'seo_description' => 'Comprehensive web development course covering frontend and backend technologies.',
                'type' => 'course',
                'price' => 99.99,
                'duration' => 120, // minutes
                'capacity' => 100,
                'support' => true,
                'certificate' => true,
                'downloadable' => true,
                'forum' => true,
                'points' => 500,
                'thumbnail' => 'assets/default/images/courses/web-development.jpg',
                'image_cover' => 'assets/default/images/courses/web-development-cover.jpg',
                'video_demo' => 'https://www.youtube.com/watch?v=demo1',
                'video_demo_source' => 'youtube',
                'status' => 'active'
            ],
            [
                'title' => 'Advanced JavaScript Mastery',
                'description' => 'Deep dive into modern JavaScript including ES6+, async programming, and design patterns.',
                'summary' => 'Advanced JavaScript concepts for experienced developers.',
                'seo_description' => 'Master advanced JavaScript techniques and modern development practices.',
                'type' => 'course',
                'price' => 79.99,
                'duration' => 90,
                'capacity' => 75,
                'support' => true,
                'certificate' => true,
                'downloadable' => true,
                'forum' => true,
                'points' => 400,
                'thumbnail' => 'assets/default/images/courses/javascript.jpg',
                'image_cover' => 'assets/default/images/courses/javascript-cover.jpg',
                'video_demo' => 'https://www.youtube.com/watch?v=demo2',
                'video_demo_source' => 'youtube',
                'status' => 'active'
            ],
            [
                'title' => 'React.js Complete Guide',
                'description' => 'Build modern web applications with React.js, hooks, context, and advanced state management.',
                'summary' => 'Comprehensive React.js course for building scalable applications.',
                'seo_description' => 'Learn React.js from basics to advanced concepts with hands-on projects.',
                'type' => 'course',
                'price' => 89.99,
                'duration' => 150,
                'capacity' => 80,
                'support' => true,
                'certificate' => true,
                'downloadable' => true,
                'forum' => true,
                'points' => 600,
                'thumbnail' => 'assets/default/images/courses/react.jpg',
                'image_cover' => 'assets/default/images/courses/react-cover.jpg',
                'video_demo' => 'https://www.youtube.com/watch?v=demo3',
                'video_demo_source' => 'youtube',
                'status' => 'active'
            ],
            [
                'title' => 'Python for Data Science',
                'description' => 'Learn Python programming for data analysis, machine learning, and scientific computing.',
                'summary' => 'Python course focused on data science and analytics.',
                'seo_description' => 'Master Python for data science with practical examples and real-world datasets.',
                'type' => 'course',
                'price' => 119.99,
                'duration' => 180,
                'capacity' => 60,
                'support' => true,
                'certificate' => true,
                'downloadable' => true,
                'forum' => true,
                'points' => 700,
                'thumbnail' => 'assets/default/images/courses/python-data.jpg',
                'image_cover' => 'assets/default/images/courses/python-data-cover.jpg',
                'video_demo' => 'https://www.youtube.com/watch?v=demo4',
                'video_demo_source' => 'youtube',
                'status' => 'active'
            ],
            [
                'title' => 'UI/UX Design Fundamentals',
                'description' => 'Master the principles of user interface and user experience design.',
                'summary' => 'Learn design thinking and create user-centered digital products.',
                'seo_description' => 'Comprehensive UI/UX design course covering research, wireframing, and prototyping.',
                'type' => 'course',
                'price' => 69.99,
                'duration' => 100,
                'capacity' => 50,
                'support' => true,
                'certificate' => true,
                'downloadable' => true,
                'forum' => true,
                'points' => 350,
                'thumbnail' => 'assets/default/images/courses/ui-ux.jpg',
                'image_cover' => 'assets/default/images/courses/ui-ux-cover.jpg',
                'video_demo' => 'https://www.youtube.com/watch?v=demo5',
                'video_demo_source' => 'youtube',
                'status' => 'active'
            ],
            [
                'title' => 'Digital Marketing Strategy',
                'description' => 'Develop comprehensive digital marketing strategies for modern businesses.',
                'summary' => 'Learn digital marketing techniques and tools for business growth.',
                'seo_description' => 'Master digital marketing strategies including SEO, social media, and content marketing.',
                'type' => 'course',
                'price' => 59.99,
                'duration' => 80,
                'capacity' => 120,
                'support' => true,
                'certificate' => true,
                'downloadable' => true,
                'forum' => true,
                'points' => 300,
                'thumbnail' => 'assets/default/images/courses/digital-marketing.jpg',
                'image_cover' => 'assets/default/images/courses/digital-marketing-cover.jpg',
                'video_demo' => 'https://www.youtube.com/watch?v=demo6',
                'video_demo_source' => 'youtube',
                'status' => 'active'
            ],
            [
                'title' => 'Mobile App Development with Flutter',
                'description' => 'Build cross-platform mobile applications using Google\'s Flutter framework.',
                'summary' => 'Learn Flutter development for iOS and Android apps.',
                'seo_description' => 'Master Flutter development and build professional mobile applications.',
                'type' => 'course',
                'price' => 109.99,
                'duration' => 200,
                'capacity' => 70,
                'support' => true,
                'certificate' => true,
                'downloadable' => true,
                'forum' => true,
                'points' => 800,
                'thumbnail' => 'assets/default/images/courses/flutter.jpg',
                'image_cover' => 'assets/default/images/courses/flutter-cover.jpg',
                'video_demo' => 'https://www.youtube.com/watch?v=demo7',
                'video_demo_source' => 'youtube',
                'status' => 'active'
            ]
        ];

        $this->command->info('Starting to create ' . count($courses) . ' dummy courses...');

        foreach ($courses as $index => $courseData) {
            $this->command->info('Creating course ' . ($index + 1) . ': ' . $courseData['title']);
            $this->createCourse($courseData, $teacher, $creator, $categories);
        }

        $this->command->info('Successfully created ' . count($courses) . ' dummy courses!');
    }

    private function getOrCreateCategories()
    {
        $categories = [];

        // Create main categories if they don't exist
        $mainCategories = [
            'Programming' => [
                'Web Development',
                'Mobile Development',
                'Data Science'
            ],
            'Design' => [
                'UI/UX Design',
                'Graphic Design'
            ],
            'Business' => [
                'Digital Marketing',
                'Business Strategy'
            ]
        ];

        foreach ($mainCategories as $mainTitle => $subTitles) {
            $mainCategory = Category::firstOrCreate(
                ['id' => Category::count() + 1],
                [
                    'slug' => Str::slug($mainTitle),
                    'order' => Category::count() + 1
                ]
            );

            // Create translations for the main category
            $mainCategory->translations()->updateOrCreate(
                ['locale' => 'en'],
                ['title' => $mainTitle]
            );

            $mainCategory->translations()->updateOrCreate(
                ['locale' => 'ar'],
                ['title' => $mainTitle . ' (Arabic)']
            );

            $categories[] = $mainCategory;

            foreach ($subTitles as $subTitle) {
                $subCategory = Category::firstOrCreate(
                    ['id' => Category::count() + 1],
                    [
                        'parent_id' => $mainCategory->id,
                        'slug' => Str::slug($subTitle),
                        'order' => Category::count() + 1
                    ]
                );

                // Create translations for the sub category
                $subCategory->translations()->updateOrCreate(
                    ['locale' => 'en'],
                    ['title' => $subTitle]
                );

                $subCategory->translations()->updateOrCreate(
                    ['locale' => 'ar'],
                    ['title' => $subTitle . ' (Arabic)']
                );

                $categories[] = $subCategory;
            }
        }

        $this->command->info('Created/Found ' . count($categories) . ' categories');

        return $categories;
    }

    private function createCourse($courseData, $teacher, $creator, $categories)
    {
        // Select a random category
        $category = $categories[array_rand($categories)];

        // Create the course with translations using Translatable trait
        $course = Webinar::create([
            'teacher_id' => $teacher->id,
            'creator_id' => $creator->id,
            'category_id' => $category->id,
            'type' => $courseData['type'],
            'slug' => Str::slug($courseData['title']),
            'thumbnail' => $courseData['thumbnail'],
            'image_cover' => $courseData['image_cover'],
            'video_demo' => $courseData['video_demo'],
            'video_demo_source' => $courseData['video_demo_source'],
            'price' => $courseData['price'],
            'duration' => $courseData['duration'],
            'capacity' => $courseData['capacity'],
            'support' => $courseData['support'],
            'certificate' => $courseData['certificate'],
            'downloadable' => $courseData['downloadable'],
            'forum' => $courseData['forum'],
            'points' => $courseData['points'],
            'status' => $courseData['status'],
            'created_at' => time(),
            'updated_at' => time()
        ]);

        // Create translations using the translations relationship
        $course->translations()->create([
            'locale' => 'en',
            'title' => $courseData['title'],
            'description' => $courseData['description'],
            'summary' => $courseData['summary'],
            'seo_description' => $courseData['seo_description']
        ]);

        $course->translations()->create([
            'locale' => 'ar',
            'title' => $courseData['title'] . ' (Arabic)',
            'description' => $courseData['description'] . ' - Arabic version',
            'summary' => $courseData['summary'] . ' - Arabic version',
            'seo_description' => $courseData['seo_description'] . ' - Arabic version'
        ]);

        return $course;
    }
}
