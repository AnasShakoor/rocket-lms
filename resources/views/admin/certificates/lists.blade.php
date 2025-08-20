@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ $pageTitle }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="/admin/">{{trans('admin/main.dashboard')}}</a>
                </div>
                <div class="breadcrumb-item">{{ $pageTitle}}</div>
            </div>
        </div>

        <div class="section-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>{{ trans('admin/main.course_certificates') }}</h4>
                            <div class="card-header-action">
                                <a href="{{ getAdminPanelUrl() }}/certificates/templates" class="btn btn-info">
                                    <i class="fas fa-palette"></i> {{ trans('admin/main.certificate_templates') }}
                                </a>
                                <a href="{{ getAdminPanelUrl() }}/certificates/settings" class="btn btn-secondary">
                                    <i class="fas fa-cog"></i> {{ trans('admin/main.settings') }}
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(!empty($certificates) and !$certificates->isEmpty())
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ trans('admin/main.student') }}</th>
                                                <th>{{ trans('admin/main.course') }}</th>
                                                <th>{{ trans('admin/main.teacher') }}</th>
                                                <th>{{ trans('admin/main.issue_date') }}</th>
                                                <th>{{ trans('admin/main.status') }}</th>
                                                <th>{{ trans('admin/main.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($certificates as $certificate)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            @if(!empty($certificate->student))
                                                                <div class="avatar mr-3">
                                                                    @if(!empty($certificate->student->avatar))
                                                                        <img src="{{ $certificate->student->avatar }}" alt="{{ $certificate->student->full_name }}" class="rounded-circle" width="40" height="40">
                                                                    @else
                                                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                            <i class="fas fa-user text-white"></i>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <div>
                                                                    <strong>{{ $certificate->student->full_name }}</strong>
                                                                    <br><small class="text-muted">{{ $certificate->student->email }}</small>
                                                                </div>
                                                            @else
                                                                <span class="text-muted">{{ trans('admin/main.student_not_found') }}</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if(!empty($certificate->course))
                                                            <div>
                                                                <strong>{{ $certificate->course->title }}</strong>
                                                                <br><small class="text-muted">{{ trans('admin/main.course') }} ID: {{ $certificate->course->id }}</small>
                                                            </div>
                                                        @else
                                                            <span class="text-muted">{{ trans('admin/main.course_not_found') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(!empty($certificate->teacher))
                                                            <span class="badge badge-info">{{ $certificate->teacher->full_name }}</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ trans('admin/main.teacher_not_found') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(!empty($certificate->created_at))
                                                            <span class="text-muted">{{ $certificate->created_at->format('M d, Y') }}</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($certificate->status == 'active')
                                                            <span class="badge badge-success">{{ trans('admin/main.active') }}</span>
                                                        @elseif($certificate->status == 'pending')
                                                            <span class="badge badge-warning">{{ trans('admin/main.pending') }}</span>
                                                        @elseif($certificate->status == 'revoked')
                                                            <span class="badge badge-danger">{{ trans('admin/main.revoked') }}</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ $certificate->status }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ getAdminPanelUrl() }}/certificates/{{ $certificate->id }}/view" class="btn btn-sm btn-info" title="{{ trans('admin/main.view') }}" target="_blank">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            
                                                            <a href="{{ getAdminPanelUrl() }}/certificates/{{ $certificate->id }}/download" class="btn btn-sm btn-success" title="{{ trans('admin/main.download') }}">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                            
                                                            @if($certificate->status == 'active')
                                                                <a href="{{ getAdminPanelUrl() }}/certificates/{{ $certificate->id }}/revoke" class="btn btn-sm btn-warning" title="{{ trans('admin/main.revoke') }}" onclick="return confirm('{{ trans('admin/main.are_you_sure_revoke') }}')">
                                                                    <i class="fas fa-ban"></i>
                                                                </a>
                                                            @endif
                                                            
                                                            <a href="{{ getAdminPanelUrl() }}/certificates/{{ $certificate->id }}/delete" class="btn btn-sm btn-danger" title="{{ trans('admin/main.delete') }}" onclick="return confirm('{{ trans('admin/main.are_you_sure_delete') }}')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                @if($certificates->hasPages())
                                    <div class="d-flex justify-content-center">
                                        {{ $certificates->links() }}
                                    </div>
                                @endif
                                
                                <div class="mt-4">
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <div class="card bg-primary text-white">
                                                <div class="card-body">
                                                    <h4>{{ $totalCertificates ?? 0 }}</h4>
                                                    <p class="mb-0">{{ trans('admin/main.total_certificates') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-success text-white">
                                                <div class="card-body">
                                                    <h4>{{ $activeCertificates ?? 0 }}</h4>
                                                    <p class="mb-0">{{ trans('admin/main.active_certificates') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-warning text-white">
                                                <div class="card-body">
                                                    <h4>{{ $pendingCertificates ?? 0 }}</h4>
                                                    <p class="mb-0">{{ trans('admin/main.pending_certificates') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-info text-white">
                                                <div class="card-body">
                                                    <h4>{{ $totalTemplates ?? 0 }}</h4>
                                                    <p class="mb-0">{{ trans('admin/main.certificate_templates') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <img src="/assets/default/img/empty.png" alt="No certificates" class="img-fluid" style="max-width: 200px;">
                                    <h4 class="mt-3">{{ trans('admin/main.no_certificates_found') }}</h4>
                                    <p class="text-muted">{{ trans('admin/main.no_certificates_found_hint') }}</p>
                                    <div class="mt-3">
                                        <a href="{{ getAdminPanelUrl() }}/certificates/templates" class="btn btn-info">
                                            <i class="fas fa-palette"></i> {{ trans('admin/main.manage_templates') }}
                                        </a>
                                        <a href="{{ getAdminPanelUrl() }}/certificates/settings" class="btn btn-secondary">
                                            <i class="fas fa-cog"></i> {{ trans('admin/main.configure_certificates') }}
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts_bottom')
<script>
    $(document).ready(function() {
        // Certificates management functionality
        console.log('Certificates management loaded successfully!');
    });
</script>
@endpush
