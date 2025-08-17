(function ($) {
    "use strict"


    var fileVideoPlayer;

    window.convertVimeoLinkToPlay = function (path) {
        path = path.trim();

        if (path.includes('player.vimeo.com/video')) return path;

        if (!/^https?:\/\//i.test(path)) path = 'https://' + path;

        try {
            const url = new URL(path);
            if (url.hostname.replace(/^www\./, '') === 'vimeo.com') {
                const id = url.pathname.split('/').filter(Boolean).pop();
                if (/^\d+$/.test(id)) return `https://player.vimeo.com/video/${id}`;
            }
        } catch {
        }

        return path;
    }

    window.makeVideoPlayerHtml = function (path, storage, height, tagId, thumbnail = null) {
        let html = '';
        let options = {
            autoplay: false,
            preload: 'auto',
            previewThumbnails: {
                enabled: !!thumbnail,
                src: thumbnail ?? ''
            }
        };

        if (storage === 'youtube') {
            html = `<div class="plyr__video-embed w-100 h-100" id="${tagId}" data-poster="${thumbnail ?? ''}">
              <iframe
                src="${path}?origin=${siteDomain}&amp;iv_load_policy=0&amp;modestbranding=0&amp;playsinline=0&amp;showinfo=0&amp;rel=0&amp;enablejsapi=0"
                allowfullscreen
                allowtransparency
                allow="autoplay"
                class="img-cover rounded-16"
                data-poster="${thumbnail ?? ''}"
              ></iframe>
            </div>`;
        } else if (storage === "vimeo") {
            let vimeoPath = convertVimeoLinkToPlay(path);

            html = `<div class="plyr__video-embed w-100 h-100" id="${tagId}" data-poster="${thumbnail ?? ''}">
              <iframe
                src="${vimeoPath}?loop=false&amp;byline=false&amp;portrait=false&amp;title=false&amp;speed=true&amp;transparent=0&amp;gesture=media"
                allowfullscreen
                allowtransparency
                allow="autoplay"
                class="img-cover rounded-16"
                data-poster="${thumbnail ?? ''}"
              ></iframe>
            </div>`;

        } else if (storage === "secure_host") {
            html = '<iframe src="' + path + '" class="img-cover bg-gray-200" frameborder="0" allowfullscreen="true" ></iframe>';
        } else {
            html = `<video id="${tagId}" class="plyr-io-video" controls preload="auto" width="100%" height="${height}" data-poster="${thumbnail ?? ''}">
                <source src="${path}" type="video/mp4"/>
            </video>`;
        }

        return {
            html: html,
            options: options,
        };
    };

    window.handleVideoByFileId = function (fileId, $contentEl, callback) {

        closeVideoPlayer();

        const height = $(window).width() > 991 ? 426 : 264;

        $.post('/course/getFilePath', {file_id: fileId}, function (result) {

            if (result && result.code === 200) {
                const storage = result.storage;

                const videoTagId = 'videoPlayer' + fileId;

                const {html, options} = makeVideoPlayerHtml(result.path, storage, height, videoTagId);

                if ($contentEl) {
                    $contentEl.html(html);
                }

                if (storage !== "secure_host") {
                    fileVideoPlayer = new Plyr(`#${videoTagId}`, options);
                }

                callback();
            } else {
                showToast("error", notAccessToastTitleLang, notAccessToastMsgLang);
            }
        }).fail(err => {
            showToast("error", notAccessToastTitleLang, notAccessToastMsgLang);
        });
    };

    window.closeVideoPlayer = function () {
        if (fileVideoPlayer !== undefined) {
            fileVideoPlayer.stop();
        }
    };

    window.pauseVideoPlayer = function () {
        if (fileVideoPlayer !== undefined) {
            fileVideoPlayer.pause();
        }
    };


})(jQuery)
