<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmailService
{
    /**
     * Send CME initiation email
     */
    public function sendCmeInitiatedEmail(User $user, $courseTitle = null, $completionDate = null)
    {
        try {
            $data = [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'course_title' => $courseTitle ?? 'Your Course',
                'completion_date' => $completionDate ?? now()->format('Y-m-d'),
                'cme_hours' => $this->getCmeHours($courseTitle),
                'certificate_url' => $this->generateCertificateUrl($user, $courseTitle)
            ];

            // Send email using your email template
            Mail::send('emails.cme-initiated', $data, function($message) use ($user, $data) {
                $message->to($user->email, $user->name)
                        ->subject('Your CME has been initiated successfully')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info("CME initiation email sent to user {$user->id} for course: {$data['course_title']}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send CME email to user {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send completion certificate email
     */
    public function sendCompletionCertificateEmail(User $user, $courseTitle, $completionDate)
    {
        try {
            $data = [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'course_title' => $courseTitle,
                'completion_date' => $completionDate,
                'cme_hours' => $this->getCmeHours($courseTitle),
                'certificate_url' => $this->generateCertificateUrl($user, $courseTitle)
            ];

            Mail::send('emails.completion-certificate', $data, function($message) use ($user, $data) {
                $message->to($user->email, $user->name)
                        ->subject('Course Completion Certificate Available')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info("Completion certificate email sent to user {$user->id} for course: {$data['course_title']}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send completion certificate email to user {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send custom email
     */
    public function sendCustomEmail(User $user, $subject, $message, $template = 'emails.custom')
    {
        try {
            $data = [
                'user_name' => $user->name,
                'user_email' => $user->email,
                'custom_message' => $message
            ];

            Mail::send($template, $data, function($message) use ($user, $subject) {
                $message->to($user->email, $user->name)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info("Custom email sent to user {$user->id} with subject: {$subject}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send custom email to user {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send bulk emails to multiple users
     */
    public function sendBulkEmails($userIds, $emailType, $subject, $customMessage = null)
    {
        $users = User::whereIn('id', $userIds)->get();
        $successCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            $success = false;

            switch ($emailType) {
                case 'cme_initiated':
                    $success = $this->sendCmeInitiatedEmail($user);
                    break;
                case 'completion_certificate':
                    $success = $this->sendCompletionCertificateEmail($user, 'Course', now());
                    break;
                case 'custom':
                    $success = $this->sendCustomEmail($user, $subject, $customMessage);
                    break;
                default:
                    Log::warning("Unknown email type: {$emailType}");
                    $failedCount++;
                    break;
            }

            if ($success) {
                $successCount++;
            } else {
                $failedCount++;
            }
        }

        Log::info("Bulk email sending completed. Success: {$successCount}, Failed: {$failedCount}");
        
        return [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'total' => count($users)
        ];
    }

    /**
     * Get CME hours for a course
     */
    private function getCmeHours($courseTitle)
    {
        // This would typically come from your course database
        // For now, returning a default value
        return 2.0;
    }

    /**
     * Generate certificate URL
     */
    private function generateCertificateUrl(User $user, $courseTitle)
    {
        // This would generate a secure URL to the user's certificate
        // For now, returning a placeholder
        return route('certificate.download', [
            'user' => $user->id,
            'course' => urlencode($courseTitle)
        ]);
    }

    /**
     * Send email notification for new course enrollment
     */
    public function sendEnrollmentNotification(User $user, $courseTitle)
    {
        try {
            $data = [
                'user_name' => $user->name,
                'course_title' => $courseTitle,
                'enrollment_date' => now()->format('Y-m-d'),
                'dashboard_url' => route('user.dashboard')
            ];

            Mail::send('emails.course-enrollment', $data, function($message) use ($user, $data) {
                $message->to($user->email, $user->name)
                        ->subject('Welcome to ' . $data['course_title'])
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send enrollment notification to user {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send progress update email
     */
    public function sendProgressUpdateEmail(User $user, $courseTitle, $progress)
    {
        try {
            $data = [
                'user_name' => $user->name,
                'course_title' => $courseTitle,
                'progress' => $progress,
                'course_url' => route('course.show', ['course' => $courseTitle])
            ];

            Mail::send('emails.progress-update', $data, function($message) use ($user, $data) {
                $message->to($user->email, $user->name)
                        ->subject('Your Progress Update - ' . $data['course_title'])
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to send progress update email to user {$user->id}: " . $e->getMessage());
            return false;
        }
    }
}
