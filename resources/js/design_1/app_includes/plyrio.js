(function () {
    "use strict"



    $(document).ready(function () {
        const $players = $('.js-init-plyr-io');

        if ($players.length > 0) {
            const options = {

            }

            for (const plyr of $players) {
                const player = new Plyr(plyr);
            }
        }
    })
})(jQuery)
