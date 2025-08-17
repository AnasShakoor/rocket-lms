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
                <div class="col-12 col-md-12">
                    <div class="card">
                        <div class="card-body">

                            <div class="empty-state mx-auto d-block"  data-width="900" >
                                <img class="img-fluid col-md-6" src="/assets/default/img/plugin.svg" alt="image"><br/>
                                <a class="mt-10 font-24 font-weight-bold">This is a separate product!</a> <br/>
                                <a class="font-16 mt-12 text-gray-500">
                                    Rocket LMS Theme & Landing Builder is not included in your current purchase. It is sold separately, and you can purchase it by clicking <strong><a class="font-16" href="https://codecanyon.net/item/rocket-lms-theme-and-landing-page-builder/59174209">this link</a></strong> <a class="font-16 text-gray-500"> on Codecanyon.
                                </a>             
                              </div>
                        </div>
                      

                    </div>
                </div>
            </div>
        </div>
    </section>





@endsection

@push('scripts_bottom')

@endpush
