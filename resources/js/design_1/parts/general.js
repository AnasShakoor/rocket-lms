(function () {
    "use strict"

    if (jQuery().summernote) {
        const $mainSummernote = $(".main-summernote");

        if ($mainSummernote.length) {
            makeSummernote($mainSummernote)
        }
    }

    if (jQuery().tagsinput) {
        var input_tags = $('.inputtags');
        input_tags.tagsinput({
            tagClass: 'bg-primary px-8 py-4 rounded-5',
            //maxTags: (input_tags.data('max-tag') ? input_tags.data('max-tag') : 10),
        });
    }

    // date & time piker
    resetDatePickers();

})(jQuery)
