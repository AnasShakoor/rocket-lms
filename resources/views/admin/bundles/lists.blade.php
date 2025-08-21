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
                            <h4>{{ trans('update.bundles') }}</h4>
                            <div class="card-header-action">
                                <a href="{{ getAdminPanelUrl() }}/bundles/create" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> {{ trans('admin/main.new') }}
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(!empty($bundles) and !$bundles->isEmpty())
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ trans('admin/main.title') }}</th>
                                                <th>{{ trans('admin/main.teacher') }}</th>
                                                <th>{{ trans('admin/main.category') }}</th>
                                                <th>{{ trans('admin/main.status') }}</th>
                                                <th>{{ trans('admin/main.price') }}</th>
                                                <th>{{ trans('admin/main.students') }}</th>
                                                <th>{{ trans('admin/main.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($bundles as $bundle)
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            @if(!empty($bundle->thumbnail))
                                                                <img src="{{ $bundle->thumbnail }}" alt="{{ $bundle->title }}" class="rounded-circle mr-3" width="40" height="40">
                                                            @else
                                                                <div class="rounded-circle bg-secondary mr-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                    <i class="fas fa-book text-white"></i>
                                                                </div>
                                                            @endif
                                                            <div>
                                                                <strong>{{ $bundle->title }}</strong>
                                                                @if(!empty($bundle->summary))
                                                                    <br><small class="text-muted">{{ Str::limit($bundle->summary, 50) }}</small>
                                                                @endif
                                                                <br><small class="text-muted">ID: {{ $bundle->id }}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if(!empty($bundle->teacher))
                                                            <span class="badge badge-info">{{ $bundle->teacher->full_name }}</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ trans('admin/main.not_assigned') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(!empty($bundle->category))
                                                            <span class="badge badge-secondary">{{ $bundle->category->title }}</span>
                                                        @else
                                                            <span class="badge badge-light">{{ trans('admin/main.no_category') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($bundle->status == 'active')
                                                            <span class="badge badge-success">{{ trans('admin/main.active') }}</span>
                                                        @elseif($bundle->status == 'pending')
                                                            <span class="badge badge-warning">{{ trans('admin/main.pending') }}</span>
                                                        @elseif($bundle->status == 'inactive')
                                                            <span class="badge badge-danger">{{ trans('admin/main.inactive') }}</span>
                                                        @elseif($bundle->status == 'is_draft')
                                                            <span class="badge badge-secondary">{{ trans('admin/main.draft') }}</span>
                                                        @else
                                                            <span class="badge badge-secondary">{{ $bundle->status }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(!empty($bundle->price) && $bundle->price > 0)
                                                            <span class="text-success font-weight-bold">{{ number_format($bundle->price, 2) }}</span>
                                                        @else
                                                            <span class="text-muted">{{ trans('admin/main.free') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if(!empty($bundle->sales) && $bundle->sales->count() > 0)
                                                            <span class="badge badge-primary">{{ $bundle->sales->count() }}</span>
                                                        @else
                                                            <span class="badge badge-light">0</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ getAdminPanelUrl() }}/bundles/{{ $bundle->id }}/edit" class="btn btn-sm btn-info" title="{{ trans('admin/main.edit') }}">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            
                                                            @if($bundle->status == 'pending')
                                                                <a href="{{ getAdminPanelUrl() }}/bundles/{{ $bundle->id }}/approve" class="btn btn-sm btn-success" title="{{ trans('admin/main.approve') }}" onclick="return confirm('{{ trans('admin/main.are_you_sure_approve') }}')">
                                                                    <i class="fas fa-check"></i>
                                                                </a>
                                                                <a href="{{ getAdminPanelUrl() }}/bundles/{{ $bundle->id }}/reject" class="btn btn-sm btn-danger" title="{{ trans('admin/main.reject') }}" onclick="return confirm('{{ trans('admin/main.are_you_sure_reject') }}')">
                                                                    <i class="fas fa-times"></i>
                                                                </a>
                                                            @endif
                                                            
                                                            @if($bundle->status == 'active')
                                                                <a href="{{ getAdminPanelUrl() }}/bundles/{{ $bundle->id }}/unpublish" class="btn btn-sm btn-warning" title="{{ trans('admin/main.unpublish') }}" onclick="return confirm('{{ trans('admin/main.are_you_sure_unpublish') }}')">
                                                                    <i class="fas fa-eye-slash"></i>
                                                                </a>
                                                            @endif
                                                            
                                                            <a href="{{ getAdminPanelUrl() }}/bundles/{{ $bundle->id }}/students" class="btn btn-sm btn-primary" title="{{ trans('admin/main.students') }}">
                                                                <i class="fas fa-users"></i>
                                                            </a>
                                                            
                                                            <a href="{{ getAdminPanelUrl() }}/bundles/{{ $bundle->id }}/delete" class="btn btn-sm btn-danger" title="{{ trans('admin/main.delete') }}" onclick="return confirm('{{ trans('admin/main.are_you_sure_delete') }}')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                @if($bundles->hasPages())
                                    <div class="d-flex justify-content-center">
                                        {{ $bundles->links() }}
                                    </div>
                                @endif
                                
                                <div class="mt-4">
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <div class="card bg-primary text-white">
                                                <div class="card-body">
                                                    <h4>{{ $totalBundles ?? 0 }}</h4>
                                                    <p class="mb-0">{{ trans('admin/main.total_bundles') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-warning text-white">
                                                <div class="card-body">
                                                    <h4>{{ $totalPendingBundles ?? 0 }}</h4>
                                                    <p class="mb-0">{{ trans('admin/main.pending_bundles') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-success text-white">
                                                <div class="card-body">
                                                    <h4>{{ $totalSales ?? 0 }}</h4>
                                                    <p class="mb-0">{{ trans('admin/main.total_sales') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card bg-info text-white">
                                                <div class="card-body">
                                                    <h4>{{ $totalAmount ?? 0 }}</h4>
                                                    <p class="mb-0">{{ trans('admin/main.total_amount') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <img src="/assets/default/img/empty.png" alt="No bundles" class="img-fluid" style="max-width: 200px;">
                                    <h4 class="mt-3">{{ trans('admin/main.no_bundles_found') }}</h4>
                                    <p class="text-muted">{{ trans('admin/main.no_bundles_found_hint') }}</p>
                                    <a href="{{ getAdminPanelUrl() }}/bundles/create" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> {{ trans('admin/main.create_first_bundle') }}
                                    </a>
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
    // Bundle management functionality
    $(document).ready(function() {
        // Add any JavaScript functionality here
        console.log('Bundle management loaded successfully!');
    });
</script>
@endpush
