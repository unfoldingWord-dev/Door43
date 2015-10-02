
function obs_plugin_script_loaded() {
    return true;
}

function initialize_obs_button() {

    jQuery('#obsCreateNowButton').on('click', function() {

        var div = jQuery('#obs-plugin-create-now-container');
        var src = jQuery('#selectObsSource').val();
        var dest = jQuery('#selectObsDestination').val();

        if (dest) {

            // find the iso code in the text
            var found = dest.match(/\([^\(\)]+\)$/);
            if (found && found.length === 1) {
                dest = found[0].replace(/\(|\)/g, '');
            }
        }

        // check data before submitting
        var msg = '';
        if (!src)
            msg += LANG.plugins['door43obs']['sourceRequired'] + '<br>\n';

        if (!dest)
            msg += LANG.plugins['door43obs']['destinationRequired'] + '<br>\n';

        if (msg) {
            div.find('#obsCreateValidateMessage').html(msg).show();
            return;
        }
        else {
            div.find('#obsCreateValidateMessage').html('&nbsp;').hide();
        }

        // figure out what to do
        var operations = [];
        if (div.find('#obs-stories').prop('checked')) operations.push('obs-stories-div');
        if (div.find('#obs-notes').prop('checked')) operations.push('obs-notes-div');
        if (div.find('#obs-words').prop('checked')) operations.push('obs-words-div');
        if (div.find('#obs-questions').prop('checked')) operations.push('obs-questions-div');

        // disable the page so the user doesn't click anything while the server is processing the request
        disable_page();

        // now do it
        do_obs_create_operation(src, dest, operations);
    });
}

/**
 * Called recursively to execute the requested operations one at a time.
 * @param src string
 * @param dest string
 * @param operations string[] An array of the checked options
 */
function do_obs_create_operation(src, dest, operations) {

    // ajax call to create/copy the files
    var url = DOKU_BASE + 'lib/exe/ajax.php';
    var div = jQuery('#' + operations[0]);

    // the POST values
    var dataValues = {
        call: div.attr('data-operation'),
        sourceLang: src,
        destinationLang: dest
    };

    // the ajax settings
    var ajaxSettings = {
        type: 'POST',
        url: url,
        data: dataValues
    };

    // remove the current operation from the list
    operations.shift();

    // layout of object returned by the server
    //   data['result'] => 'OK' if success, anything else indicates failure
    //   data['msg']    => message to display to user
    jQuery.ajax(ajaxSettings).done(function (data) {

        var msg;
        var result = div.find('p');

        if (typeof data === 'string') {

            // most likely an error has occurred
            msg = data;
            result.attr('class', 'failure');
        }
        else {

            // the ajax call succeeded, display the results
            msg = data['htmlMessage'];
            if (data['result'] === 'OK')
                result.attr('class', 'success');
            else
                result.attr('class', 'failure');
        }

        result.html(msg).show();

        if (operations.length) {
            do_obs_create_operation(src, dest, operations);
        }
        else {
            var para = jQuery('#obs-plugin-create-now-container').find('#obsCreateValidateMessage');
            msg = para.html() + '<span class="success">' + LANG.plugins['door43obs']['finished'] + '</span><br>\n';
            para.html(msg).show();

            // re-enable the page now that we're finished
            enable_page();
        }
    });
}
