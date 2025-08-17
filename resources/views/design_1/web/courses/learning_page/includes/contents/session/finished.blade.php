<div class="">
    <img src="/assets/design_1/img/courses/learning_page/session_finished.svg" alt="" class="img-fluid" width="285px" height="212px">
</div>

<h3 class="font-16 mt-12">{{ trans('update.session_end') }}</h3>
<div class="mt-8 text-gray-500">{!! nl2br(trans('update.session_ended_hint_for_users')) !!}</div>

<div class="d-flex align-items-center gap-20 gap-lg-40 mt-16">
    <div class="d-flex align-items-center">
        <div class="d-flex-center size-40 bg-gray-100 rounded-circle">
            <x-iconsax-lin-calendar-2 class="icons text-gray-500" width="20px" height="20px"/>
        </div>
        <div class="ml-8 text-left">
            <span class="d-block font-12 text-gray-400">{{ trans('public.start_date') }}</span>
            <div class="font-weight-bold text-gray-500">{{ dateTimeFormat($session->date, 'j M Y H:i') }}</div>
        </div>
    </div>

    <div class="d-flex align-items-center">
        <div class="d-flex-center size-40 bg-gray-100 rounded-circle">
            <x-iconsax-lin-clock-1 class="icons text-gray-500" width="20px" height="20px"/>
        </div>
        <div class="ml-8 text-left">
            <span class="d-block font-12 text-gray-400">{{ trans('public.duration') }}</span>
            <div class="font-weight-bold text-gray-500">{{ $session->duration }} {{ trans('public.minutes') }}</div>
        </div>
    </div>
</div>
