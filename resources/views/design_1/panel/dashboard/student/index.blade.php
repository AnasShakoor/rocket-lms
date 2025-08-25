<div class="row">
    <div class="col-12 col-lg-6">
        {{-- Hello Box --}}
        @include('design_1.panel.dashboard.student.includes.hello_box')

        {{-- Courses Overview --}}
        <div class="{{ (!empty($helloBox['continueLearningCourses']) and count($helloBox['continueLearningCourses'])) ? 'mt-128' : 'mt-84' }}">
            @include('design_1.panel.dashboard.student.includes.courses_overview')
        </div>

        {{-- My Assignments --}}
        @include('design_1.panel.dashboard.student.includes.my_assignments')

        {{-- Learning Activity --}}
        @include('design_1.panel.dashboard.student.includes.learning_activity')
    </div>

    <div class="col-12 col-lg-3 mt-32 mt-lg-0">
        {{-- Noticeboard --}}
        @include('design_1.panel.dashboard.student.includes.noticeboard')

        {{-- Support Messages --}}
        @include('design_1.panel.dashboard.student.includes.support_messages')

        {{-- My Quizzes --}}
        @include('design_1.panel.dashboard.student.includes.my_quizzes')
    </div>

    <div class="col-12 col-lg-3 mt-32 mt-lg-0">
        {{-- Open Meetings --}}
        @include('design_1.panel.dashboard.student.includes.open_meetings')
    </div>
</div>
