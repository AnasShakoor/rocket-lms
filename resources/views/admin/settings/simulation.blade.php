@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>Simulation Settings</h1>
            <div class="section-header-breadcrumb">
                <a href="{{ getAdminPanelUrl('/settings') }}" class="btn btn-light">Back</a>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <form action="{{ getAdminPanelUrl('/settings') }}" method="POST">
                        @csrf
                        <input type="hidden" name="name" value="simulation">
                        <input type="hidden" name="page" value="simulation">

                        <div class="form-group form-check">
                            <input type="checkbox" class="form-check-input" id="simEnabled" name="value[enabled]" value="1" {{ (data_get($settings, 'simulation.value.enabled') ? 'checked' : '') }}>
                            <label class="form-check-label" for="simEnabled">Enable Simulation Module</label>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Default Enrol Offset (days)</label>
                                <input type="number" class="form-control" name="value[default_enrol_offset_days]" value="{{ data_get($settings, 'simulation.value.default_enrol_offset_days', -12) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Default Complete Offset (days)</label>
                                <input type="number" class="form-control" name="value[default_complete_offset_days]" value="{{ data_get($settings, 'simulation.value.default_complete_offset_days', 2) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Default Sequence Gap (days)</label>
                                <input type="number" class="form-control" name="value[default_sequence_gap_days]" value="{{ data_get($settings, 'simulation.value.default_sequence_gap_days', 1) }}">
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection


