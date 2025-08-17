@extends('design_1.web.layouts.app')

@push('styles_top')
    <link rel="stylesheet" href="/assets/vendors/leaflet/leaflet.css">
    <link rel="stylesheet" href="/assets/vendors/leaflet/leaflet.markercluster/markerCluster.css">
    <link rel="stylesheet" href="/assets/vendors/leaflet/leaflet.markercluster/markerCluster.Default.css">
    <link rel="stylesheet" href="/assets/default/vendors/swiper/swiper-bundle.min.css">
    <link rel="stylesheet" href="/assets/vendors/wrunner-html-range-slider-with-2-handles/css/wrunner-default-theme.css">
    <link rel="stylesheet" href="{{ getDesign1StylePath("instructor_finder") }}">
@endpush

@section('content')

 <div class=" text-center d-block mt-104"  data-width="50%" >
                                <img class="col-md-3" src="/assets/default/img/plugin.svg" alt="image">
                                <h3 class="mt-3 mb-10">This is a paid plugin!</h3>
                                <h5 class="lead">
                                    You can purchase it by <strong><a class="text-danger" href="https://codecanyon.net/item/universal-plugins-bundle-for-rocket-lms/33297004">this link</a></strong> on Codecanyon.
                                </h5>             
                              </div>

@endsection


@push('scripts_bottom')
    <script>
        var leafletApiPath = '{{ getLeafletApiPath() }}';
        var selectRegionDefaultVal = '';
        var selectStateLang = '{{ trans('update.choose_a_state') }}';
        var selectCityLang = '{{ trans('update.choose_a_city') }}';
        var selectDistrictLang = '{{ trans('update.all_districts') }}';
        var bookAMeetingLang = '{{ trans('public.book_a_meeting') }}';
        var profileLang = '{{ trans('public.profile') }}';
        var hourLang = '{{ trans('update.hour') }}';
        var freeLang = '{{ trans('public.free') }}';
        var noResultTitle = '{{ trans('update.instructor_finder_no_result') }}';
        var noResultHint = '{!! trans('update.instructor_finder_no_result_hint') !!}';
        var currency = '{{ $currency }}';
        var mapUsers = JSON.parse(@json($mapUsers->toJson()));

        var starIcon = `<x-iconsax-bol-star-1 class="icons" width="14" height="14"/>`;
        var frameIcon = `<x-iconsax-bol-frame class="icons text-gray-500" width="20px" height="20px"/>`;
        var calendarIcon = `<x-iconsax-bol-calendar-2 class="icons text-white" width="20px" height="20px"/>`;
    </script>

    <script src="/assets/vendors/leaflet/leaflet.min.js"></script>
    <script src="/assets/vendors/leaflet/leaflet.markercluster/leaflet.markercluster-src.js"></script>
    <script src="{{ getDesign1ScriptPath("leaflet_map") }}"></script>
    <script src="/assets/default/vendors/swiper/swiper-bundle.min.js"></script>
    <script src="/assets/vendors/wrunner-html-range-slider-with-2-handles/js/wrunner-jquery.js"></script>

    <script src="{{ getDesign1ScriptPath("get_regions") }}"></script>
    <script src="{{ getDesign1ScriptPath("swiper_slider") }}"></script>
    <script src="{{ getDesign1ScriptPath("range_slider_helpers") }}"></script>

    <script src="{{ getDesign1ScriptPath("instructor_finder") }}"></script>
@endpush
