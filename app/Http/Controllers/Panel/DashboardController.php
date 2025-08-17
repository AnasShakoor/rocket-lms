<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Panel\Traits\DashboardTrait;
use App\Mixins\RegistrationPackage\UserPackage;
use App\Models\Comment;
use App\Models\Gift;
use App\Models\Meeting;
use App\Models\ReserveMeeting;
use App\Models\Sale;
use App\Models\Subscribe;
use App\Models\Support;
use App\Models\Webinar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use DashboardTrait;

    public function index(Request $request)
    {
        $user = auth()->user();

        $data = [
            'pageTitle' => trans('panel.dashboard'),
        ];

        if ($user->isUser()) {
            $data = array_merge($data, $this->getStudentDashboardData($request, $user));
        } else {
            $data = array_merge($data, $this->getInstructorDashboardData($request, $user));
        }

        // Upcoming Events
        $data = array_merge($data, $this->handleDashboardUpcomingEvents($user));


        return view('design_1.panel.dashboard.index', $data);
    }

    private function getStudentDashboardData(Request $request, $user): array
    {
        $data = [];

        $data['activeSubscribe'] = Subscribe::getActiveSubscribe($user->id);
        $data['authUserBalanceCharge'] = $user->getAccountingCharge();
        $data['authUserReadyPayout'] = $user->getPayout();


        $userBoughtWebinarsIds = $user->getPurchasedCoursesIds();

        // hello_box
        $data['helloBox'] = $this->getStudentHelloBoxData($user, $userBoughtWebinarsIds);

        // Courses Overview
        $data['coursesOverview'] = $this->getStudentCoursesOverviewData($user, $userBoughtWebinarsIds);

        // My Assignments
        $data['myAssignments'] = $this->getStudentMyAssignmentsData($user, $userBoughtWebinarsIds);

        // Learning Activity
        $data['learningActivity'] = $this->getStudentLearningActivityData($user, $userBoughtWebinarsIds);

        // Noticeboard
        $data['unreadNoticeboards'] = $user->getUnreadNoticeboards();

        // Support Messages
        $data['supportMessages'] = $this->getStudentSupportMessagesData($user, $userBoughtWebinarsIds);

        // My quizzes
        $data['myQuizzes'] = $this->getStudentMyQuizzesData($user, $userBoughtWebinarsIds);

        // Upcoming Live Sessions
        $data['upcomingLiveSessions'] = $this->getStudentUpcomingLiveSessionsData($user, $userBoughtWebinarsIds);

        // Open Meetings
        $data['openMeetings'] = $this->getStudentOpenMeetingsData($user, $userBoughtWebinarsIds);

        return $data;
    }

    private function getInstructorDashboardData(Request $request, $user): array
    {
        $data = [];

        $userWebinars = Webinar::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('webinars.creator_id', $user->id);
                $query->orWhere('webinars.teacher_id', $user->id);
            })
            ->leftJoin('sales', function ($join) use ($user) {
                $join->on('sales.webinar_id', '=', 'webinars.id');
                $join->whereNull('sales.refund_at');
                //$join->where('sales.amount', '>', '0');
            })
            ->select('webinars.*',
                DB::raw('count(sales.webinar_id) as sales_count'),
                DB::raw('sum(sales.total_amount) as sales_amount')
            )
            ->groupBy('webinars.id')
            ->orderBy('sales_count', 'desc')
            ->get();

        $userWebinarsIds = $userWebinars->pluck('id')->toArray();

        $meetingIds = Meeting::where('creator_id', $user->id)->pluck('id');


        // hello_box
        $data['helloBox'] = $this->getInstructorHelloBoxData($user, $meetingIds, $userWebinars);

        // Courses Overview
        $data['coursesOverview'] = $this->getInstructorCoursesOverviewData($user, $userWebinars);

        // Sales Overview
        $data['salesOverview'] = $this->getInstructorSalesOverviewData($user, $userWebinarsIds);

        // Pending Student Assignments
        $data['pendingStudentAssignments'] = $this->getInstructorStudentAssignmentsData($user, $userWebinarsIds);

        // Registration Plan
        $userPackage = new UserPackage($user);
        $data['registrationPlan'] = $userPackage->getPackage();

        // Current Balance
        $data['authUserBalanceCharge'] = $user->getAccountingCharge();
        $data['authUserReadyPayout'] = $user->getPayout();

        // Noticeboard
        $data['unreadNoticeboards'] = $user->getUnreadNoticeboards();

        // Support Messages
        $data['supportMessages'] = $this->getInstructorSupportMessagesData($user, $userWebinarsIds);

        // Visitors Statistics
        $data['visitorsStatistics'] = $this->getInstructorVisitorsStatisticsData($user, $userWebinarsIds);

        if ($user->isTeacher()) {
            // Upcoming Live Sessions
            $data['upcomingLiveSessions'] = $this->getInstructorUpcomingLiveSessionsData($user, $userWebinarsIds);

            // Review Student Quizzes
            $data['reviewStudentQuizzes'] = $this->getInstructorReviewStudentQuizzes($user, $userWebinarsIds);

            // Open Meetings
            $data['openMeetings'] = $this->getInstructorOpenMeetingsData($user, $userWebinarsIds);

        } else { // Organization
            // Top Instructors
            $data['topInstructors'] = $this->getOrganizationTopInstructorsData($user);

            // Top Students
            $data['topStudents'] = $this->getOrganizationTopStudentsData($user);
        }


        return $data;
    }

    private function handleDashboardUpcomingEvents($user)
    {
        $eventsController = (new EventsController());
        $eventsController->user = $user;
        $eventsController->userBoughtWebinarsIds = $user->getPurchasedCoursesIds();

        $eventsWithTimestamp = $eventsController->getAllEventsReturnWithTimestamp();
        $getUpcomingEvents = $eventsController->getUpcomingEvents(2);
        $upcomingEvents = $getUpcomingEvents['upcomingEvents'];
        $totalEvents = $getUpcomingEvents['total'];

        return [
            'upcomingEvents' => $upcomingEvents,
            'totalEvents' => $totalEvents,
            'eventsWithTimestamp' => $eventsWithTimestamp,
        ];
    }

    public function dashboard()
    {
        $user = auth()->user();

        $nextBadge = $user->getBadges(true, true);

        $data = [
            'pageTitle' => trans('panel.dashboard'),
            'nextBadge' => $nextBadge
        ];

        if (!$user->isUser()) {
            $meetingIds = Meeting::where('creator_id', $user->id)->pluck('id')->toArray();
            $pendingAppointments = ReserveMeeting::whereIn('meeting_id', $meetingIds)
                ->whereHas('sale')
                ->where('status', ReserveMeeting::$pending)
                ->count();

            $userWebinarsIds = $user->webinars->pluck('id')->toArray();
            $supports = Support::whereIn('webinar_id', $userWebinarsIds)->where('status', 'open')->get();

            $comments = Comment::whereIn('webinar_id', $userWebinarsIds)
                ->where('status', 'active')
                ->whereNull('viewed_at')
                ->get();

            $time = time();
            $firstDayMonth = strtotime(date('Y-m-01', $time));// First day of the month.
            $lastDayMonth = strtotime(date('Y-m-t', $time));// Last day of the month.

            $monthlySales = Sale::where('seller_id', $user->id)
                ->whereNull('refund_at')
                ->whereBetween('created_at', [$firstDayMonth, $lastDayMonth])
                ->get();

            $data['pendingAppointments'] = $pendingAppointments;
            $data['supportsCount'] = count($supports);
            $data['commentsCount'] = count($comments);
            $data['monthlySalesCount'] = count($monthlySales) ? $monthlySales->sum('total_amount') : 0;
            $data['monthlyChart'] = $this->getMonthlySalesOrPurchase($user);
        } else {
            $webinarsIds = $user->getPurchasedCoursesIds();

            $webinars = Webinar::whereIn('id', $webinarsIds)
                ->where('status', 'active')
                ->get();

            $reserveMeetings = ReserveMeeting::where('user_id', $user->id)
                ->whereHas('sale', function ($query) {
                    $query->whereNull('refund_at');
                })
                ->where('status', ReserveMeeting::$open)
                ->get();

            $supports = Support::where('user_id', $user->id)
                ->whereNotNull('webinar_id')
                ->where('status', 'open')
                ->get();

            $comments = Comment::where('user_id', $user->id)
                ->whereNotNull('webinar_id')
                ->where('status', 'active')
                ->get();

            $data['webinarsCount'] = count($webinars);
            $data['supportsCount'] = count($supports);
            $data['commentsCount'] = count($comments);
            $data['reserveMeetingsCount'] = count($reserveMeetings);
            $data['monthlyChart'] = $this->getMonthlySalesOrPurchase($user);
        }

        $data['giftModal'] = $this->showGiftModal($user);

        return view(getTemplate() . '.panel.dashboard.index', $data);
    }

    private function showGiftModal($user)
    {
        $gift = Gift::query()->where('email', $user->email)
            ->where('status', 'active')
            ->where('viewed', false)
            ->where(function ($query) {
                $query->whereNull('date');
                $query->orWhere('date', '<', time());
            })
            ->whereHas('sale')
            ->first();

        if (!empty($gift)) {
            $gift->update([
                'viewed' => true
            ]);

            $data = [
                'gift' => $gift
            ];

            $result = (string)view()->make('design_1.web.gift.modal.show_to_receipt', $data);
            $result = str_replace(array("\r\n", "\n", "  "), '', $result);

            return $result;
        }

        return null;
    }

    private function getMonthlySalesOrPurchase($user)
    {
        $months = [];
        $data = [];

        // all 12 months
        for ($month = 1; $month <= 12; $month++) {
            $date = Carbon::create(date('Y'), $month);

            $start_date = $date->timestamp;
            $end_date = $date->copy()->endOfMonth()->timestamp;

            $months[] = trans('panel.month_' . $month);

            if (!$user->isUser()) {
                $monthlySales = Sale::where('seller_id', $user->id)
                    ->whereNull('refund_at')
                    ->whereBetween('created_at', [$start_date, $end_date])
                    ->sum('total_amount');

                $data[] = round($monthlySales, 2);
            } else {
                $monthlyPurchase = Sale::where('buyer_id', $user->id)
                    ->whereNull('refund_at')
                    ->whereBetween('created_at', [$start_date, $end_date])
                    ->count();

                $data[] = $monthlyPurchase;
            }
        }

        return [
            'months' => $months,
            'data' => $data
        ];
    }
}
