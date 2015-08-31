
var availableLanguages = {};

function populateAvailableOBSLanguages(includeSub3) {

    var select = jQuery('#selectObsDocxSource');

    for (var i = 0; i < availableLanguages.length; i++) {

        var langData = availableLanguages[i];

        if (!includeSub3 && (langData['status']['checking_level'] !== '3')) continue;

        select.append(jQuery('<option></option>')
                .attr('value', langData['language'])
                .text(langData['string'] + ' (' + langData['language'] + ')')
        );
    }
}

function closeExportDialog() {
    jQuery('#door43ObsExportDiv').dialog('close');
}

function exportSourceTemplate() {

    var langCode;
    var draft = document.getElementById('useDraft').checked ? 1 : 0;

    if (draft) {
        langCode = NS;
    }
    else {
        var lang = document.getElementById('selectObsDocxSource');
        if (!lang.value) return;
        langCode = lang.value;
    }

    var images = document.getElementById('includeObsDocxImages').checked ? 1 : 0;

    var url = DOKU_BASE + 'lib/exe/ajax.php?call=download_obs_template_docx&lang=' + langCode + '&img=' + images + '&draft=' + draft;

    window.open(url, '_blank');

    closeExportDialog();
}

function updateSourceLanguageList() {
    jQuery('#selectObsDocxSource').find('option[value!=""]').remove();
    populateAvailableOBSLanguages(document.getElementById('includeSub3').checked);
}

function enableDisableObsExportControls() {

    var draft = document.getElementById('useDraft').checked;

    document.getElementById('selectObsDocxSource').disabled = draft;
    document.getElementById('includeSub3').disabled = draft;
}

function closeImportDialog() {
    jQuery('#door43ObsImportDiv').dialog('close');
}

function setupExportClick() {

    var btn = jQuery('#getObsTemplateBtn');

    btn.on('click', function() {

        // if we don't remove focus, the button appears to be stuck
        jQuery(this).children().blur();

        jQuery('#door43ObsExportDiv').dialog({
            height: 360,
            width: 500,
            modal: true,
            open: function() {

                if (!document.getElementById('door43ObsExportOptions').innerHTML) {

                    var url = DOKU_BASE + 'lib/exe/ajax.php';

                    var dataValues = {
                        call: 'get_obs_doc_export_dlg'
                    };

                    var ajaxSettings = {
                        type: 'GET',
                        url: url,
                        data: dataValues
                    };

                    jQuery.ajax(ajaxSettings).done(function(data) {
                        document.getElementById('door43ObsExportOptions').innerHTML = data;

                        // We need to get the data from the plugin because of browser Cross-Origin restrictions.
                        var url = DOKU_BASE + 'lib/exe/ajax.php';

                        var dataValues = {
                            call: 'cross_origin_request',
                            contentType: 'application/json',
                            requestUrl: 'https://api.unfoldingword.org/obs/txt/1/obs-catalog.json'
                        };

                        var ajaxSettings = {
                            type: 'POST',
                            url: url,
                            data: dataValues
                        };

                        jQuery.ajax(ajaxSettings).done(function(data) {
                            if (!data) return;
                            availableLanguages = data;
                            populateAvailableOBSLanguages(false);
                        });
                    });
                }
            }
        });
    });
}
