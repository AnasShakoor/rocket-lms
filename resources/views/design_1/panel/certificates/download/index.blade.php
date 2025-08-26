@extends('design_1.panel.layouts.panel')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-certificate me-2"></i>
                            {{ trans('panel.download_certificate') }}
                        </h4>
                        <p class="card-text text-muted">
                            {{ trans('panel.download_certificate_description') }}
                        </p>
                    </div>
                    <div class="card-body">
                        @if ($purchasedCourses->count() > 0)
                            <div class="row">
                                @foreach ($purchasedCourses as $course)
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100 border">
                                            <div class="card-body text-center">
                                                @if (!empty($course->image))
                                                    <img src="{{ $course->image }}" alt="{{ $course->title }}"
                                                        class="img-fluid rounded mb-3" style="max-height: 120px;">
                                                @else
                                                    <div class="bg-light rounded mb-3 d-flex align-items-center justify-content-center"
                                                        style="height: 120px;">
                                                        <i class="fas fa-book fa-3x text-muted"></i>
                                                    </div>
                                                @endif

                                                <h6 class="card-title">{{ $course->title }}</h6>
                                                <p class="text-muted small mb-3">
                                                    {{ trans('panel.purchased_on') }}:
                                                    {{ !empty($course->created_at) ? \Carbon\Carbon::createFromTimestamp($course->created_at)->format('M d, Y') : 'N/A' }}
                                                </p>

                                                <form action="{{ url('/panel/certificates/download/' . $course->id) }}"
                                                    method="GET">
                                                    <button type="submit" class="btn btn-primary btn-sm w-100">
                                                        <i class="fas fa-download me-2"></i>
                                                        {{ trans('panel.generate_and_download_certificate') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-certificate fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">{{ trans('panel.no_certificate_courses') }}</h5>
                                <p class="text-muted">{{ trans('panel.no_certificate_courses_description') }}</p>
                                <a href="{{ url('/courses') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-search me-2"></i>
                                    {{ trans('panel.browse_courses') }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
