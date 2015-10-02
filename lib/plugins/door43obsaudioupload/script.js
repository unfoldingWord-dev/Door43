/// <reference path="d/jquery.d.ts" />
/// <reference path="d/jqueryui.d.ts" />
/// <reference path="d/jquery.fineuploader.d.ts" />
/// <reference path="d/interfaces.d.ts" />
/* DOKUWIKI:include_once private/fineuploader/s3.jquery.fine-uploader.js */
/**
 * Name: script.ts
 * Description: Script to support OBS audio upload
 *
 * Author: Phil Hopper
 * Date:   2014-12-22
 */
var Door43FileUploader = (function () {
    /**
     * Class constructor
     */
    function Door43FileUploader() {
        this.sortTimer = 0;
        var self = this;
        self.getUserInfo(self);
    }
    Door43FileUploader.prototype.getUserInfo = function (self) {
        var url = DOKU_BASE + 'lib/exe/ajax.php';
        var dataValues = {
            call: 'obsaudioupload_user_info_request'
        };
        var ajaxSettings = {
            type: 'POST',
            url: url,
            data: dataValues
        };
        jQuery.ajax(ajaxSettings).done(function (data) {
            if (!data['name']) {
                jQuery('.obsaudioupload-not-logged-in').css('display', 'block');
            }
            else {
                self.userName = data['name'];
                jQuery('.obsaudioupload-logged-in').css('display', 'block');
                self.getBucketConfig(self);
            }
        });
    };
    Door43FileUploader.prototype.getBucketConfig = function (self) {
        var url = DOKU_BASE + 'lib/exe/ajax.php';
        var dataValues = {
            call: 'obsaudioupload_bucket_config_request'
        };
        var ajaxSettings = {
            type: 'POST',
            url: url,
            data: dataValues
        };
        jQuery.ajax(ajaxSettings).done(function (data) {
            self.initUploader(self, data);
        });
    };
    Door43FileUploader.prototype.initUploader = function (self, bucketInfo) {
        var sigEndpoint = DOKU_BASE + 'doku.php?do=obsaudioupload_signature_request';
        self.uploader = jQuery('#obs-fine-uploader').fineUploaderS3({
            debug: false,
            maxConnections: 1,
            request: {
                endpoint: bucketInfo.endPoint,
                accessKey: bucketInfo.accessKey
            },
            signature: {
                endpoint: sigEndpoint
            },
            retry: {
                enableAuto: true,
                showButton: true,
                showAutoRetryNote: true,
                autoRetryNote: LANG.plugins['door43obsaudioupload']['autoRetryNote']
            },
            text: {
                failUpload: LANG.plugins['door43obsaudioupload']['failUpload'],
                formatProgress: LANG.plugins['door43obsaudioupload']['formatProgress'],
                paused: LANG.plugins['door43obsaudioupload']['paused'],
                waitingForResponse: LANG.plugins['door43obsaudioupload']['waitingForResponse']
            },
            template: "qq-template",
            autoUpload: false,
            validation: { allowedExtensions: ['mp3'] },
            editFilename: { enabled: false },
            button: document.getElementById('obsaudioupload-selectButton')
        });
        // self.uploader.on('statusChange', function(event: Event, id: number, oldStatus: string, newStatus: string) {
        self.uploader.on('statusChange', function () {
            // This will fire at least once for each file that is dropped or selected,
            // so we are using a delay timer to wait for the operation to finish before
            // the list is padded.
            self.delayPadFileList();
        });
        self.sorting = jQuery('.obsaudioupload-sortable').sortable({
            start: function () {
                this.style.cursor = 'move';
            },
            stop: function () {
                this.style.cursor = '';
            }
        });
        jQuery('#obsaudioupload-uploadButton').on('click', function () {
            self.sorting.sortable('disable');
            Door43FileUploader.uploadNow(self);
        });
        Door43FileUploader.initializeChapters();
    };
    Door43FileUploader.uploadNow = function (self) {
        var langText = document.getElementById('obsaudioupload-selectLanguageCode').value;
        if (!langText)
            return;
        var langCodes = langText.match(/\(([a-z]+)\)/i);
        if (langCodes.length !== 2)
            return;
        var userText = jQuery('.user').first().text();
        var userNames = userText.match(/\((.+)\)/i);
        if (userNames.length !== 2)
            return;
        var chapterVerses = jQuery('#obsaudioupload-select-chapter').val();
        if (!chapterVerses)
            return;
        var chapter = chapterVerses.split(':')[0];
        var ulFiles = jQuery('#obsaudioupload-files');
        var allItems = ulFiles.find('li');
        var items = ulFiles.find('[qq-file-id]');
        // target directory
        // requested structure: media/[langCode]/mp3/[door43userName]/[batches]/chapter_01/
        var targetDir = 'media/' + langCodes[1] + '/mp3/' + userNames[1] + '/' + Date.now().toString() + '/chapter_' + chapter + '/';
        for (var i = 0; i < items.length; i++) {
            var pageId = allItems.index(items[i]) + 1;
            var fileId = parseInt(items[i].getAttribute('qq-file-id'));
            var file = self.uploader.fineUploaderS3('getUploads', { id: fileId });
            var ext = file['name'].substring(file['name'].lastIndexOf('.'));
            file['uuid'] = targetDir + Door43FileUploader.formatPageNumber(pageId);
            file['name'] = file['uuid'] + ext;
        }
        self.uploader.fineUploaderS3('uploadStoredFiles');
    };
    Door43FileUploader.formatPageNumber = function (pageNum) {
        return ('00' + pageNum.toString()).slice(-2);
    };
    Door43FileUploader.initializeChapters = function () {
        // We need to get the data from the plugin because of browser Cross-Origin restrictions.
        var url = DOKU_BASE + 'lib/exe/ajax.php';
        var dataValues = {
            call: 'cross_origin_request',
            contentType: 'application/json',
            requestUrl: 'https://api.unfoldingword.org/obs/txt/1/en/obs-en.json'
        };
        var ajaxSettings = {
            type: 'POST',
            url: url,
            data: dataValues
        };
        jQuery.ajax(ajaxSettings).done(function (data) {
            // remember for images in showFrames()
            door43FileUploader.chapters = data.chapters;
            var select = jQuery('#obsaudioupload-select-chapter');
            for (var i = 0; i < door43FileUploader.chapters.length; i++) {
                var chapter = door43FileUploader.chapters[i];
                select.append('<option value="' + chapter.number + ':' + chapter.frames.length + '">' + chapter.title + '</option>');
            }
            select.on('change', function () {
                Door43FileUploader.showFrames(this.value);
            });
        });
    };
    Door43FileUploader.showFrames = function (chapterData) {
        var values = chapterData.split(':');
        var ul = jQuery('#obsaudioupload-pages');
        ul.empty();
        for (var i = 1; i <= parseInt(values[1]); i++) {
            var imgUrl = DOKU_BASE + 'doku.php?do=obsaudioupload_frame_thumbnail&img=' + door43FileUploader.chapters[parseInt(values[0]) - 1]['frames'][i - 1]['img'];
            ul.append('<li><img src="' + imgUrl + '">' + Door43FileUploader.formatPageNumber(i) + '</li>');
        }
        Door43FileUploader.padFileList();
    };
    Door43FileUploader.prototype.delayPadFileList = function () {
        if (this.sortTimer) {
            clearTimeout(this.sortTimer);
            this.sortTimer = 0;
        }
        this.sortTimer = setTimeout(function () {
            Door43FileUploader.padFileList();
        }, 1);
    };
    /**
     * Ensures the file list is the same length as the chapter list
     */
    Door43FileUploader.padFileList = function () {
        var ulFiles = jQuery('#obsaudioupload-files');
        var ulPages = jQuery('#obsaudioupload-pages');
        var numPages = ulPages.children('li').length;
        var numFiles = ulFiles.children('li').length;
        var numPlaceHolders = ulFiles.children('li.obsaudioupload-placeholder').length;
        if (numPages > numFiles) {
            // add more place holders
            Door43FileUploader.addPlaceHolders(ulFiles, numPages - numFiles);
        }
        else if (numFiles > numPages) {
            // remove place holders and/or files
            var toRemove = numFiles - numPages;
            var placeHoldersToRemove = (numPlaceHolders > toRemove) ? toRemove : numPlaceHolders;
            toRemove -= placeHoldersToRemove;
            var filesToRemove = (toRemove > 0) ? toRemove : 0;
            if (placeHoldersToRemove > 0)
                Door43FileUploader.replacePlaceHolders(ulFiles, 'li.obsaudioupload-placeholder', numPages);
            if (filesToRemove > 0)
                Door43FileUploader.removeListItems(ulFiles, 'li.obsaudioupload-draggable', filesToRemove);
        }
        jQuery('#obsaudioupload-page-list-div').height(ulPages.height());
    };
    Door43FileUploader.addPlaceHolders = function (ulFiles, numberToAdd) {
        for (var i = 0; i < numberToAdd; i++) {
            ulFiles.append('<li class="obsaudioupload-placeholder">&nbsp;</li>');
        }
    };
    Door43FileUploader.removeListItems = function (ul, selector, numberToRemove) {
        var items = ul.children(selector);
        var index, count;
        for (index = items.length - 1, count = 0; index >= 0 && count < numberToRemove; index--, count++) {
            jQuery(items[index]).remove();
        }
    };
    Door43FileUploader.replacePlaceHolders = function (ul, placeHolderSelector, finalListLength) {
        var placeHolders = ul.children(placeHolderSelector);
        placeHolders.remove();
        Door43FileUploader.addPlaceHolders(ul, finalListLength - ul.children().length);
    };
    return Door43FileUploader;
})();
var door43FileUploader;
jQuery().ready(function () {
    door43FileUploader = new Door43FileUploader();
});
//# sourceMappingURL=script.js.map
