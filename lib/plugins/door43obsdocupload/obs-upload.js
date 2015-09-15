
function importSourceDocx() {

    // check for a selected file
    var $docx = jQuery('#uploadDocxFile');
    if (!$docx.length) return;
    if (!$docx[0].files.length) return;

    // check for a selected language
    var langCode = document.getElementById('selectDocxNamespace').value;
    if (!langCode) return;

    // build the ajax request data
    var formData = new FormData();
    formData.append('call', 'upload_obs_docx');
    formData.append('file', $docx[0].files[0]);
    formData.append('lang', langCode);

    disable_page();

    var $progress = jQuery('#progressDiv').show();

    // send to the server
    // layout of object returned by the server
    //   data['result'] => 'OK' if success, anything else indicates failure
    //   data['msg']    => message to display to user
    jQuery.ajax({
        url: DOKU_BASE + 'lib/exe/ajax.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {

            // this adds the event listener to process upload progress
            var thisXhr = jQuery.ajaxSettings.xhr();
            if (thisXhr.upload) {
                thisXhr.upload.addEventListener('progress', showObsUploadProgress, false);
            }
            return thisXhr;
        }
    }).done(function(data) {

        var msg;
        var $result = jQuery('#obsDocxUploadMessage');

        if (typeof data === 'string') {

            // most likely an error has occurred
            msg = data;
            $result.attr('class', 'failure');
        }
        else {

            // the ajax call succeeded, display the results
            msg = data['htmlMessage'];
            if (data['result'] === 'OK') {

                // the uploaded stories are ready to review before publishing
                $result.attr('class', 'success');
                jQuery('#publishDiv').show();
            }
            else {
                $result.attr('class', 'failure');
            }
        }

        $result.html(msg).show();
        enable_page();

        // we need to do this in 2 steps because the progress bar does not reset if we do it all on the same line
        $progress.val(0);
        $progress.hide();
        jQuery('#processingDiv').hide();
    });
}

function showObsUploadProgress(e) {

    //noinspection JSUnresolvedVariable
    if (e.lengthComputable) {
        jQuery('progress').prop('max', e.total).val(e.loaded);

        // hide the progress bar when finished
        if ((e.loaded / e.total) > 0.985) {
            jQuery('#progressDiv').hide();
            jQuery('#processingDiv').show();
        }
    }
}

function publishUploadedOBS() {
    alert('todo: Publish is not yet implemented');
}
