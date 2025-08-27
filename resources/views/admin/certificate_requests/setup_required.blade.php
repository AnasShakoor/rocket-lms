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
                        <i class="fas fa-database fa-3x text-warning"></i>
                    </div>

                    <h4 class="text-warning mb-3">Database Setup Required</h4>

                    <p class="text-muted mb-4">{{ $message }}</p>

                    <div class="alert alert-info">
                        <h6>To complete the setup, you need to:</h6>
                        <ol class="text-left">
                            <li>Create the <code>certificate_requests</code> table in your database</li>
                            <li>Add the required permissions to your system</li>
                        </ol>
                    </div>

                    <div class="mt-4">
                        <h6>SQL Commands to Run:</h6>
                        <div class="bg-light p-3 rounded text-left">
                            <code>
                                -- Run the contents of create_certificate_requests_table.sql<br>
                                -- Then run the contents of add_certificate_requests_permissions.sql
                            </code>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ getAdminPanelUrl() }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
