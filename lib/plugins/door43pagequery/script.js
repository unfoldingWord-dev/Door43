
var door43pagequery = [];

function door43pagequery_load_next() {

    if (door43pagequery.length === 0) return;

    var obj = door43pagequery.shift();
    var $div = jQuery('#' + obj['div_id']);
    $div.find('.waiting').html(LANG.plugins['door43pagequery']['loading']).removeClass('waiting').addClass('loading');

    var url = DOKU_BASE + 'lib/exe/ajax.php';

    var dataValues = {
        call: 'get_door43pagequery_async',
        data: obj
    };

    var ajaxSettings = {
        type: 'POST',
        url: url,
        data: dataValues
    };

    jQuery.ajax(ajaxSettings)
        .done(function(data) {

            // display the returned value
            $div.html(data);

            // get the next door43pagequery
            setTimeout(door43pagequery_load_next, 50);
        })
        .fail(function($xhr, status, err) {

            // alert the user
            $div.html(status + ': ' + err);
        });
}

jQuery().ready(function(){
    setTimeout(door43pagequery_load_next, 50);
});