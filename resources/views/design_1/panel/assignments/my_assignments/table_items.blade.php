<tr>
    <td class="text-left">
        <span class="d-block font-16 text-dark">{{ $assignment->title }}</span>
        <span class="d-block font-12 text-gray-500">{{ $assignment->webinar->title }}</span>
    </td>

    <td class="text-center">
        <span class="">{{ !empty($assignment->deadline) ? dateTimeFormat($assignment->deadlineTime, 'j M Y') : '-' }}</span>
    </td>

    <td class="text-center">
        <span class="">{{ !empty($assignment->first_submission) ? dateTimeFormat($assignment->first_submission, 'j M Y | H:i') : '-' }}</span>
    </td>

    <td class="text-center">
        <span class="">{{ !empty($assignment->last_submission) ? dateTimeFormat($assignment->last_submission, 'j M Y | H:i') : '-' }}</span>
    </td>

    <td class="text-center">
        <span class="">{{ !empty($assignment->attempts) ? "{$assignment->usedAttemptsCount}/{$assignment->attempts}" : '-' }}</span>
    </td>

    <td class="text-center">
        <span>{{ (!empty($assignment->assignmentHistory) and !empty($assignment->assignmentHistory->grade)) ? $assignment->assignmentHistory->grade : '-' }}</span>
    </td>

    <td class="text-center">
        <span>{{ $assignment->pass_grade }}</span>
    </td>

    <td class="text-center">
        @if(empty($assignment->assignmentHistory) or ($assignment->assignmentHistory->status == \App\Models\WebinarAssignmentHistory::$notSubmitted))
            <span class="text-danger ">{{ trans('update.assignment_history_status_not_submitted') }}</span>
        @else
            @switch($assignment->assignmentHistory->status)
                @case(\App\Models\WebinarAssignmentHistory::$passed)
                    <span class="text-primary ">{{ trans('quiz.passed') }}</span>
                    @break
                @case(\App\Models\WebinarAssignmentHistory::$pending)
                    <span class="text-warning ">{{ trans('public.pending') }}</span>
                    @break
                @case(\App\Models\WebinarAssignmentHistory::$notPassed)
                    <span class=" text-danger">{{ trans('quiz.failed') }}</span>
                    @break
            @endswitch
        @endif
    </td>


    <td class="text-right">

        <div class="actions-dropdown position-relative d-flex justify-content-end align-items-center">
            <button type="button" class="d-flex-center size-36 bg-gray border-gray-200 rounded-10">
                <x-iconsax-lin-more class="icons text-gray-500" width="18"/>
            </button>

            <div class="actions-dropdown__dropdown-menu dropdown-menu-width-220 dropdown-menu-top-32">
                <ul class="my-8">

                    <li class="actions-dropdown__dropdown-menu-item">
                        @if($assignment->webinar->checkUserHasBought())
                            <a href="{{ "{$assignment->webinar->getLearningPageUrl()}?type=assignment&item={$assignment->id}" }}" target="_blank"
                               class="">{{ trans('update.view_assignment') }}</a>
                        @else
                            <a href="#!" class="not-access-toast ">{{ trans('update.view_assignment') }}</a>
                        @endif
                    </li>

                </ul>
            </div>
        </div>

    </td>

</tr>
