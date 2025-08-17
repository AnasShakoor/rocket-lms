<div class="d-flex-center flex-column text-center my-16">
    <div class="size-64 rounded-circle bg-gray-100">
        <img src="{{ $userInfo->getAvatar(64) }}" alt="" class="img-cover rounded-circle">
    </div>

    <h4 class="font-14 mt-12">{{ $userInfo->full_name }}</h4>
    <p class="font-12 mt-4 text-gray-500">{{ ($userInfo->role_name == \App\Models\Role::$teacher) ? trans('public.instructor') : trans('quiz.student') }}</p>
</div>

<div class="p-16 my-16 rounded-12 border-gray-200">
    <div class="d-flex align-items-center justify-content-between">
        <span class="text-gray-500">{{ trans('update.meeting_date') }}</span>
        <span class="">{{ dateTimeFormat($ReserveMeeting->start_at, 'j M Y H:i') }}</span>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-12">
        <span class="text-gray-500">{{ trans('site.phone_number') }}</span>
        <span class="">{{ $userInfo->mobile ?? '-' }}</span>
    </div>

    <div class="d-flex align-items-center justify-content-between mt-12">
        <span class="text-gray-500">{{ trans('public.email') }}</span>
        <span class="">{{ $userInfo->email ?? '-' }}</span>
    </div>

</div>
