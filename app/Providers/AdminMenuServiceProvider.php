<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;

class AdminMenuServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Share admin menu data with all admin views
        View::composer('admin.*', function ($view) {
            $view->with('adminMenu', $this->getAdminMenu());
        });
    }

    private function getAdminMenu()
    {
        $menu = [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'url' => getAdminPanelUrl('/'),
                'active' => request()->is(getAdminPanelUrl('/', false))
            ],
            'lms_operational' => [
                'title' => 'LMS Operational',
                'icon' => 'fas fa-cogs',
                'children' => [
                    'simulation' => [
                        'title' => 'Simulation',
                        'icon' => 'fas fa-magic',
                        'url' => getAdminPanelUrl('/simulation'),
                        'active' => request()->is(getAdminPanelUrl('/simulation*', false)),
                        'permission' => 'admin_simulation_access'
                    ]
                ]
            ],
            'bnpl_providers' => [
                'title' => 'BNPL Providers',
                'icon' => 'fas fa-credit-card',
                'url' => getAdminPanelUrl('/bnpl-providers'),
                'active' => request()->is(getAdminPanelUrl('/bnpl-providers*', false)),
                'permission' => 'admin_bnpl_providers_access'
            ],
            'enhanced_reports' => [
                'title' => 'Enhanced Reports',
                'icon' => 'fas fa-chart-line',
                'url' => getAdminPanelUrl('/enhanced-reports'),
                'active' => request()->is(getAdminPanelUrl('/enhanced-reports*', false)),
                'permission' => 'admin_enhanced_reports_access'
            ],
            'certificate_requests' => [
                'title' => 'Certificate Requests',
                'icon' => 'fas fa-certificate',
                'url' => getAdminPanelUrl('/certificate-requests'),
                'active' => request()->is(getAdminPanelUrl('/certificate-requests*', false)),
                'permission' => 'admin_certificate_requests_list'
            ]
        ];

        return $menu;
    }
}
