<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\User;
use App\Models\Webinar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CertificatesDownloadController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        try {
            // Get user's purchased courses that have certificates enabled
            $purchasedCourses = \App\Models\Webinar::query()
                ->where('status', 'active')
                ->where('certificate', 1)
                ->whereHas('sales', function($query) use ($user) {
                    $query->where('buyer_id', $user->id)
                          ->where('refund_at', null)
                          ->where('type', 'webinar');
                })
                ->orderBy('id')
                ->get();
        } catch (\Exception $e) {
            // Log error and return empty collection
            Log::error('Error fetching purchased courses: ' . $e->getMessage());
            $purchasedCourses = collect([]);
        }

        $breadcrumbs = [
            ['text' => trans('update.platform'), 'url' => '/'],
            ['text' => trans('panel.dashboard'), 'url' => '/panel'],
            ['text' => trans('panel.certificates'), 'url' => '/panel/certificates'],
            ['text' => trans('panel.download_certificate'), 'url' => null],
        ];

        $data = [
            'pageTitle' => trans('panel.download_certificate'),
            'breadcrumbs' => $breadcrumbs,
            'purchasedCourses' => $purchasedCourses,
        ];

        return view('design_1.panel.certificates.download.index', $data);
    }

    public function download(Request $request, $courseId)
    {
        $user = auth()->user();

        // Get course information
        $course = \App\Models\Webinar::find($courseId);

        if (!$course) {
            return back()->with('error', 'Course not found');
        }

        // Send notification to admin
        $this->sendAdminNotification($user, $course);

        // Send email to admin
        $this->sendAdminEmail($user, $course);

        return back()->with('success', trans('panel.certificate_request_sent'));
    }

    private function sendAdminNotification($user, $course)
    {
        // Get all admin users
        $admins = \App\User::where('role_name', 'admin')->get();

        foreach ($admins as $admin) {
            $notifyOptions = [
                '[u.name]' => $user->full_name,
                '[c.title]' => $course->title,
                '[c.id]' => $course->id,
            ];

            sendNotification('certificate_request_without_completion', $notifyOptions, $admin->id);
        }
    }

    private function sendAdminEmail($user, $course)
    {
        // Get admin email from settings or use default
        $adminEmail = getGeneralSettings('admin_email') ?? config('mail.from.address');

        if ($adminEmail) {
            $data = [
                'user_name' => $user->full_name,
                'user_email' => $user->email,
                'course_name' => $course->title,
                'course_id' => $course->id,
                'request_date' => now()->format('M d, Y H:i:s'),
            ];

            Mail::send('emails.certificate_request_without_completion', $data, function($message) use ($adminEmail, $user, $course) {
                $message->to($adminEmail)
                        ->subject('Certificate Request Without Course Completion - ' . $course->title);
            });
        }
    }


}
