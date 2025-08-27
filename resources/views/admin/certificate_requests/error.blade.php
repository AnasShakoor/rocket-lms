@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ $pageTitle }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item">{{ $pageTitle }}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger"></i>
                    </div>

                    <h4 class="text-danger mb-3">Error Loading Certificate Requests</h4>

                    <p class="text-muted mb-4">{{ $message }}</p>

                    <div class="alert alert-warning">
                        <h6>Possible causes:</h6>
                        <ul class="text-left">
                            <li>Database connection issues</li>
                            <li>Missing or corrupted table structure</li>
                            <li>Permission issues</li>
                            <li>Model relationship problems</li>
                        </ul>
                    </div>

                    <div class="mt-4">
                        <a href="{{ getAdminPanelUrl() }}" class="btn btn-primary mr-2">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Dashboard
                        </a>

                        <a href="{{ getAdminPanelUrl() }}/certificate-requests" class="btn btn-warning">
                            <i class="fas fa-redo mr-2"></i>
                            Try Again
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
