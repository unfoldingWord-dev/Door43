/**
 * Our implementation fo the DokuCookie for cross-domain support
 */
var Door43Cookie = {
    data: {},
    name: 'DOOR43_PREFS',

    /**
     * Save a value to the cookie
     */
    setValue: function(key,val){
        var text = [],
            _this = this;
        this.init();
        this.data[key] = val;

        //save the whole data array
        jQuery.each(_this.data, function (key, val) {
            if (_this.data.hasOwnProperty(key)) {
                text.push(encodeURIComponent(key)+'#'+encodeURIComponent(val));
            }
        });

        if (window.location.host.indexOf('localhost') > -1) {
            jQuery.cookie(this.name, text.join('#'), {expires: 365, path: DOKU_BASE});
        }
        else if (DOKU_BASE === '/') {
            jQuery.cookie(this.name, text.join('#'), {expires: 365, path: DOKU_BASE, domain: '.door43.org'});
        }
        else {
            jQuery.cookie(this.name, text.join('#'), {expires: 365, path: DOKU_BASE});
        }

    },

    /**
     * Get a Value from the Cookie
     */
    getValue: function(key){
        this.init();
        return this.data[key];
    },

    /**
     * Loads the current set cookie
     */
    init: function(){
        var text, parts, i;
        if(!jQuery.isEmptyObject(this.data)) {
            return;
        }
        text = jQuery.cookie(this.name);

        if(text){
            parts = text.split('#');
            for(i = 0; i < parts.length; i += 2){
                this.data[decodeURIComponent(parts[i])] = decodeURIComponent(parts[i+1]);
            }
        }
    }
};

function saveNamespace(langIso, langText) {

    // Save in the recent languages list
    var cookie = Door43Cookie.getValue('recentNamespaceCodes');
    var recentList = (cookie) ? cookie.split(';') : [];

    // is this language already in the list?
    var already = recentList.some(function(item) {
        if (item.length < langIso.length) return false;
        return item.substr(0, langIso.length + 1) === langIso + ':';
    });

    // in not already in the list, add it now
    if (!already) {
        recentList.push(langIso + ':' + langText);

        // limit length of the list
        while (recentList.length > 6) {
            recentList.shift();
        }

        // save in a cookie
        Door43Cookie.setValue('recentNamespaceCodes', recentList.join(';'));
    }
}

function buildRecentLanguagesList() {

    // get the url for language links
    var action = jQuery('#namespace-auto-complete-action').val();

    // if the syntax plugin is not loaded, return now
    if (typeof action === 'undefined') return;

    var currentNS = '';
    var passedLangCode = '';
    var nsDescription = '';

    // save the current namespace, if not selected using the translation control
    var passedNS = jQuery('#door43CurrentLanguage').val();
    if (passedNS) {
        var passedParts = passedNS.split(':');
        saveNamespace(passedParts[0], passedParts[1] + ' (' + passedParts[0] + ')');

        // show the current namespace description
        passedLangCode = passedParts[0];
    }

    var cookie = Door43Cookie.getValue('recentNamespaceCodes');

    if (cookie) {
        var cookies = cookie.split(';');
        for (var i = 0; i < cookies.length; i++) {

            var val = cookies[i].split(':');
            if ((val.length > 1) && (val[0] === passedLangCode)) {

                currentNS = val[0];
                nsDescription = val[1];
                break;
            }
        }

        // remove the namespace
        if (currentNS) {
            if (action === currentNS) {
                action = '';
            }
            else {
                var pos = action.indexOf(currentNS + ':');
                if (pos === 0)
                    action = action.substr(currentNS.length + 1);
            }
        }

        action = action.replace(/:/g, '/');

        // get the list of recent languages
        var ul = jQuery('#door43RecentLanguageList');
        for (var j = cookies.length - 1; j > -1; j--) {

            // format = code:language description
            var values = cookies[j].split(':');

            ul.append('<li style="float: none;"><a href="' + DOKU_BASE + values[0] + '/' + action + '"><span lang="' + values[0] + '">' + values[1] + '</span></a></li>');
        }
    }
    jQuery('#namespace-auto-complete').val(nsDescription);
}
/**
 * Remove go button from translation dropdown
 */
jQuery(function(){
    var $frm = jQuery('#translation__dropdown');
    if(!$frm.length) return;

    var dropdown = $frm.find('select[name=id]');
    if (!dropdown.length) return;

    $frm.find('input[name=go]').hide();
    dropdown.change(function() {

        var id = jQuery(this).val();

        // this should hopefully detect rewriting good enough:
        var action = $frm.attr('action');

        window.location.href = (action.substr(action.length - 1) == '/')
            ? action + id : action + '?id=' + id;
    });
});

jQuery().ready(function() {
    buildRecentLanguagesList();
});
