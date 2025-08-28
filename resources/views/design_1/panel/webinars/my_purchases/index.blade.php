@extends('design_1.panel.layouts.panel')

@push("styles_top")
    <link rel="stylesheet" href="/assets/default/vendors/chartjs/chart.min.css"/>
    <link rel="stylesheet" href="/assets/default/vendors/swiper/swiper-bundle.min.css">
@endpush

@section('content')

    {{-- Top Stats --}}
    @include('design_1.panel.webinars.my_purchases.top_stats')

    {{-- Upcoming Live Sessions --}}
    @include('design_1.panel.webinars.my_purchases.upcoming_live_sessions')

    {{-- List Table --}}
    @if(!empty($sales) and $sales->isNotEmpty())
        <div id="tableListContainer" class="" data-view-data-path="/panel/courses">
            <div class="js-page-sales-lists row mt-20">
                @foreach($sales as $saleRow)
                    <div class="col-12 col-lg-6 mb-32">
                        @include("design_1.panel.webinars.my_purchases.item_card.index", ['sale' => $saleRow])
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div id="pagination" class="js-ajax-pagination" data-container-id="tableListContainer"
                 data-container-items=".js-page-sales-lists">
                {!! $pagination !!}
            </div>
        </div>
    @else
        @include('design_1.panel.includes.no-result',[
            'file_name' => 'purchased_courses.png',
            'title' => trans('panel.no_result_purchases') ,
            'hint' => trans('panel.no_result_purchases_hint') ,
            'btn' => ['url' => '/classes?sort=newest','text' => trans('panel.start_learning')]
        ])
    @endif

@endsection

@push('scripts_bottom')
    <script>
        var undefinedActiveSessionLang = '{{ trans('webinars.undefined_active_session') }}';
        var saveSuccessLang = '{{ trans('webinars.success_store') }}';
        var selectChapterLang = '{{ trans('update.select_chapter') }}';
        var liveSessionInfoLang = '{{ trans('update.live_session_info') }}';
        var joinTheSessionLang = '{{ trans('update.join_the_session') }}';
    </script>

    <script src="/assets/default/vendors/chartjs/chart.min.js"></script>
    <script src="/assets/default/vendors/swiper/swiper-bundle.min.js"></script>
    <script src="{{ getDesign1ScriptPath("get_view_data") }}"></script>
    <script src="/assets/design_1/js/parts/swiper_slider.min.js"></script>
    <script src="/assets/design_1/js/panel/my_course_lists.min.js"></script>
    <script src="/assets/design_1/js/panel/make_next_session.min.js"></script>

    <script>
        $(document).ready(function() {
            // Handle certificate request button clicks
            $('.certificate-request-btn').on('click', function() {
                var courseId = $(this).data('course-id');
                var courseType = $(this).data('course-type');
                var courseTitle = $(this).data('course-title');

                showCertificateRequestModal(courseId, courseType, courseTitle);
            });
        });

        function showCertificateRequestModal(courseId, courseType, courseTitle) {
            var modalHtml = `
                <div class="modal fade" id="certificateRequestModal" tabindex="-1" role="dialog" aria-labelledby="certificateRequestModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="certificateRequestModalLabel">Request Certificate Without Completion</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-warning">
                                    <strong>Important:</strong> You are requesting a certificate without completing the course requirements.
                                </div>
                                <p><strong>Course:</strong> ${courseTitle}</p>
                                <p>Your request will be sent to the admin for review. Please check back later for updates.</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-warning" id="submitCertificateRequest">Submit Request</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remove existing modal if any
            $('#certificateRequestModal').remove();

            // Add modal to body
            $('body').append(modalHtml);

            // Show modal
            $('#certificateRequestModal').modal('show');

            // Handle submit button click
            $('#submitCertificateRequest').on('click', function() {
                submitCertificateRequest(courseId, courseType);
            });
        }

        function submitCertificateRequest(courseId, courseType) {
            var submitBtn = $('#submitCertificateRequest');
            var originalText = submitBtn.text();

            // Debug logging
            console.log('Submitting certificate request:', {
                courseId: courseId,
                courseType: courseType
            });

            // Disable button and show loading
            submitBtn.prop('disabled', true).text('Submitting...');

            $.ajax({
                url: '/panel/courses/purchases/certificate-request',
                method: 'POST',
                data: {
                    course_id: courseId,
                    course_type: courseType,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'success') {
                        // Show success message
                        $('#certificateRequestModal .modal-body').html(`
                            <div class="alert alert-success">
                                <strong>Success!</strong> ${response.message}
                            </div>
                            <p>Admin has received your request and will review it. Please check back in a while.</p>
                        `);

                        // Update button
                        submitBtn.removeClass('btn-warning').addClass('btn-success').text('Request Submitted');

                        // Hide modal after 3 seconds
                        setTimeout(function() {
                            $('#certificateRequestModal').modal('hide');
                        }, 3000);
                    } else {
                        showError(response.message);
                    }
                },
                error: function(xhr) {
                    var errorMessage = 'An error occurred. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    showError(errorMessage);
                },
                complete: function() {
                    // Re-enable button
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        }

        function showError(message) {
            $('#certificateRequestModal .modal-body').html(`
                <div class="alert alert-danger">
                    <strong>Error:</strong> ${message}
                </div>
            `);
        }
    </script>

@endpush
