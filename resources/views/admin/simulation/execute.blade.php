@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>Execute Simulation Rule #{{ $rule->id }}</h1>
            <div class="section-header-breadcrumb">
                <a href="{{ route('admin.simulation.show', $rule) }}" class="btn btn-light">Back</a>
            </div>
        </div>

        <div class="section-body">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Target Type:</strong> {{ ucfirst($rule->target_type) }}<br>
                        <strong>Offsets:</strong> {{ $rule->enrollment_offset_days }} / {{ $rule->completion_offset_days }} / {{ $rule->inter_course_gap_days }}
                    </div>

                    <div class="d-flex">
                        <button id="previewBtn" class="btn btn-outline-info mr-2">Preview</button>
                        <form action="{{ route('admin.simulation.execute', $rule) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary" onclick="return confirm('Execute this simulation rule now?');">Execute Now</button>
                        </form>
                    </div>

                    <hr>
                    <div id="previewArea" style="display:none;">
                        <h5>Preview</h5>
                        <pre class="bg-light p-3" id="previewJson"></pre>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.getElementById('previewBtn').addEventListener('click', function () {
            fetch('{{ route('admin.simulation.preview', $rule) }}')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('previewArea').style.display = 'block';
                    document.getElementById('previewJson').textContent = JSON.stringify(data, null, 2);
                })
                .catch(() => alert('Failed to load preview'));
        });
    </script>
@endsection


