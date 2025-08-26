<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(SectionsTableSeeder::class);

        $this->call(PaymentChannelsTableSeeder::class);

        $this->call(LandingBuilderComponentsSeeder::class);

        $this->call(ThemeHeaderFooterSeeder::class);

        $this->call(DefaultThemeSeeder::class);

        // New seeders for enhanced features
        $this->call(BnplProvidersSeeder::class);

        // Add BNPL permissions
        $this->call(BnplPermissionsSeeder::class);

        // Add purchased courses for user@gmail.com
        $this->call(UserPurchasedCoursesSeeder::class);
    }
}
