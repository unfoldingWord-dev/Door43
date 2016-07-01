/// <reference path="../../../door43shared/ts.d/jquery.d.ts" />
/// <reference path="d/interfaces.d.ts" />

/**
 * Created by phil on 11/18/15.
 *
 * DO NOT MODIFY THIS JAVASCRIPT FILE. It is generated from door43counts/private/ts/word-count-plugin.ts.  Make all
 * changes in the typescript file (word-count-plugin.ts) and then run tsc.sh to regenerate the javascript.
 *
 */
function word_count_plugin_script_loaded(): boolean {
    return true;
}

var reload_timer;

function load_word_counts(): void {

    // use this timer to reload in case of timeout generating counts
    reload_timer = setTimeout(function () { document.location.reload(true); }, 3 * 60 * 1000);

    // We need to get the data from the plugin because of browser Cross-Origin restrictions.
    var url: string = DOKU_BASE + 'lib/exe/ajax.php';

    var dataValues = {
        call: 'door43_word_counts'
    };

    var ajaxSettings = {
        type: 'POST',
        url: url,
        data: dataValues
    };

    jQuery.ajax(ajaxSettings).done(function (data: string) {

        // stop the reload timer
        if (typeof reload_timer !== 'undefined') {
            window.clearTimeout(reload_timer);
            reload_timer = undefined;
        }

        jQuery('#loading-h3').hide();

        var $div: JQuery = jQuery('#count-results-div');

        if (data.substr(0, 1) === '{') {
            // an object was returned
            var obj: Object = JSON.parse(data);
            var $ul: JQuery = $div.find('ul');

            // ta counts
            var ta_data: Object[] = obj['ta'];
            var $ta: JQuery = jQuery('<ul></ul>');
            var ta_count = 0;

            for (var i = 0; i < ta_data.length; i++) {

                $ta.append('<li>' + ta_data[i][0] + ': ' + ta_data[i][1] + '</li>');
                ta_count += ta_data[i][1];
            }
            $li = jQuery('<li>tA: ' + ta_count + '</li>');
            $li.append($ta);
            $ul.append($li);

            // OBS counts
            var obs: any[] = obj['obs'];
            for (var i = 0; i < obs.length; i++) {
                $ul.append('<li>' + obs[i][0] + ': ' + obs[i][1] + '</li>');
            }

            $ul.append('<li>Bible tW: ' + obj['terms'] + '</li>');

            // Bible counts
            var $ulb: JQuery = jQuery('<ul></ul>');
            var ulb_count = 0;
            var $udb: JQuery = jQuery('<ul></ul>');
            var udb_count = 0;
            var bible: Object[] = obj['bible'];
            var $notes: JQuery = jQuery('<ul></ul>');
            var note_count = 0;
            var $cq: JQuery = jQuery('<ul></ul>');
            var cq_count = 0;

            for (var i = 1; i < 68; i++) {

                if (!bible[i]) continue;

                // ULB counts
                $ulb.append('<li>' + bible[i][0] + ': ' + bible[i][1]['ulb'] + '</li>');
                ulb_count += bible[i][1]['ulb'];

                // UDB counts
                $udb.append('<li>' + bible[i][0] + ': ' + bible[i][1]['udb'] + '</li>');
                udb_count += bible[i][1]['udb'];

                // note counts
                $notes.append('<li>' + bible[i][0] + ': ' + bible[i][1]['notes'] + '</li>');
                note_count += bible[i][1]['notes'];

                // checking question counts
                $cq.append('<li>' + bible[i][0] + ': ' + bible[i][1]['questions'] + '</li>');
                cq_count += bible[i][1]['questions'];
            }

            var $li: JQuery = jQuery('<li>Bible tN: ' + note_count + '</li>');
            $li.append($notes);
            $ul.append($li);

            $li = jQuery('<li>Bible tQ: ' + cq_count + '</li>');
            $li.append($cq);
            $ul.append($li);

            $li = jQuery('<li>ULB: ' + ulb_count + '</li>');
            $li.append($ulb);
            $ul.append($li);

            $li = jQuery('<li>UDB: ' + udb_count + '</li>');
            $li.append($udb);
            $ul.append($li);
        }
        else {
            // this is probably an error message
            $div.html(data);
        }

        $div.show();
    });
}
