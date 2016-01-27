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

function sortLanguages(langA, langB, text) {

    // SORT THE LIST:
    // 4. exact language code match
    // 3. language code starts-with match
    // 2. language description starts-with
    // 1. language description contains
    // 0. sort the rest alphabetically

    var valA = 0, valB = 0;
    text = text.toLowerCase();
    var langA_code = langA['lc'].toLowerCase();
    var langB_code = langB['lc'].toLowerCase();

    // look for exact language code match
    if (langA_code === text) valA = 4;
    if (langB_code === text) valB = 4;

    // look for language code starts-with match
    if ((valA === 0) && langA_code.startsWith(text)) valA = 3;
    if ((valB === 0) && langB_code.startsWith(text)) valB = 3;

    // look for language description starts-with
    var langA_name = langA['ln'].toLowerCase();
    var langB_name = langB['ln'].toLowerCase();
    if ((valA === 0) && langA_name.startsWith(text)) valA = 2;
    if ((valB === 0) && langB_name.startsWith(text)) valB = 2;

    // look for language description contains
    var regex = new RegExp('.+' + text + '.*', 'i');
    if ((valA === 0) && (regex.test(langA_name))) valA = 1;
    if ((valB === 0) && (regex.test(langB_name))) valB = 1;

    var compare = valB - valA;
    if (compare !== 0) return compare;

    // if none of the above applies, try to sort alphabetically
    return langA_name.localeCompare(langB_name);
}

function setupLanguageSelectors() {

    jQuery('body').find("[data-language-selector='1']").attr('placeholder', LANG.plugins['door43translation']['selectLanguage']).on('keyup', function(event) {
        languageSelectorKeyUp(event);
    });
}

var languageSelectorTimer;

/**
 *
 * @param event
 */
function languageSelectorKeyUp(event) {

    // the Enter key press must be handled by the plugin
    if (event.which === 13) return;

    // if the timer is currently running, reset it
    if (languageSelectorTimer) {
        clearTimeout(languageSelectorTimer);
    }

    var $textBox = jQuery(event.target);
    var textVal = $textBox.val();
    var lastSearch = $textBox.attr('data-last-search');

    // should we clear the list to avoid showing the wrong list?
    if (lastSearch) {
        if (textVal.length === 1
        || ((lastSearch.length > 2) && (textVal.length < 3))
        || ((lastSearch.length < 3) && (textVal.length > 2))
        || ((textVal.length < lastSearch.length) && !lastSearch.startsWith(textVal))) {
            $textBox.autocomplete('option', 'source', []);
        }
    }

    languageSelectorTimer = setTimeout(languageSelectorTimeout, 500, event.target);
}

/**
 *
 * @param textBox
 */
function languageSelectorTimeout(textBox) {

    // reset the timer flag
    languageSelectorTimer = 0;

    var $textBox = jQuery(textBox);
    var textVal = $textBox.val();

    // don't search for nothing
    if (textVal.length < 2) return;

    var languages = [];
    var lastSearch = $textBox.attr('data-last-search');

    // limit the search to the first 4 characters
    var thisSearch = (textVal.length > 4) ? textVal.substr(0, 4) : textVal;

    // if the search text has changed, refresh the list of languages
    if (thisSearch != lastSearch) {

        $textBox.attr('data-last-search', thisSearch);
        $textBox.attr('placeholder', LANG.plugins['door43translation']['loading']);
        getLanguageListItems($textBox, languages);
    }
}

/**
 *
 * @param $textBox   jQuery
 * @param languages  string[]
 * @param [callback] function Initially added for unit testing
 */
function getLanguageListItems($textBox, languages, callback) {

    var textVal = $textBox.val().toLowerCase();
    var request = {type: 'GET', url: 'https://door43.org:9096/?q=' + encodeURIComponent(textVal)};

    jQuery.ajax(request).done(function(data) {

        if (!data.results) return;

        for (var i = 0; i < data.results.length; i++) {

            var langData = data.results[i];
            if ((textVal.length > 2) || (langData['lc'].toLowerCase().startsWith(textVal))) {
                langData['value'] = langData['ln'] + (langData['ang']&&langData['ang']!=langData['ln']?' - '+langData['ang']:'') + ' (' + langData['lc'] +')';
                langData['label'] = langData['value'] + ' ' + langData['lr'];
                languages.push(langData);
            }
        }

        if (!$textBox.hasClass('ui-autocomplete-input')) {
            $textBox.autocomplete({
                minLength: 0
            }).autocomplete('instance')._renderItem = function( ul, item ) {
                return jQuery('<li style="font-size: 0.9em;">')
                    .append(item['ln'] + (item['ang']&&item['ang']!=item['ln']?' - '+item['ang']:'') + ' (' + item['lc'] + ')<br><span style="font-size: 0.9em;">Region: ' + item['lr'] + '</span>')
                    .appendTo(ul);
            };
        }

        $textBox.autocomplete('option', 'source', languages.sort(function(a, b) { return sortLanguages(a, b, textVal); }));
        $textBox.autocomplete('search', textVal);
        $textBox.attr('placeholder', LANG.plugins['door43translation']['selectLanguage']);

        if (typeof callback === 'function') {
            callback();
        }
    });
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
    setupLanguageSelectors();
});
