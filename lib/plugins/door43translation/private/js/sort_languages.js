function sortLanguages(langA, langB, text) {

    // SORT THE LIST:
    // 3. exact language code match
    // 2. language code starts-with match
    // 1. language description starts-with
    // 0. sort the rest alphabetically

    var valA = 0, valB = 0;

    // look for exact language code match
    var regex = new RegExp('\\(' + text + '\\)$', 'i');
    if (regex.test(langA)) valA = 3;
    if (regex.test(langB)) valB = 3;

    // look for language code starts-with match
    regex = new RegExp('\\(' + text + '[a-z0-9-]+\\)$', 'i');
    if ((valA === 0) && (regex.test(langA))) valA = 2;
    if ((valB === 0) && (regex.test(langB))) valB = 2;

    // look for language description starts-with
    regex = new RegExp('^' + text + '.*', 'i');
    if ((valA === 0) && (regex.test(langA))) valA = 1;
    if ((valB === 0) && (regex.test(langB))) valB = 1;

    var compare = valB - valA;

    if (compare !== 0) return compare;
    if (langA > langB) return 1;
    if (langA < langB) return -1;
    return 0;
}

// used to prevent searching while backspacing
var previousSearch = '';

function setupLanguageSelector() {

    jQuery('#id').attr('placeholder', LANG.plugins['door43translation']['selectLanguage']).on('keyup', function(event) {

        // the Enter key press must be handled by the plugin
        if (event.which === 13) return;

        var textBox = jQuery('#id');
        var textVal = textBox.val();
        var languages = [];

        // if the text length = 2, refresh the list of languages
        if ((textVal.length === 2) && (textVal !== previousSearch)) {

            previousSearch = textVal;

            textBox.attr('placeholder', LANG.plugins['door43translation']['loading']);

            var request = {type: 'GET', url: 'https://door43.org:9096/?q=' + encodeURIComponent(textVal)};

            jQuery.ajax(request).done(function(data) {

                if (!data.results) return;

                for (var i = 0; i < data.results.length; i++) {

                    var langData = data.results[i];
                    languages.push(langData['ln'] + ' (' + langData['lc'] + ')');
                }

                if (!textBox.hasClass('ui-autocomplete-input')) {
                    textBox.autocomplete({ source: languages.sort(function(a, b) { return sortLanguages(a, b, textVal); }) });
                }
                else {
                    textBox.autocomplete('option', 'source', languages.sort(function(a, b) { return sortLanguages(a, b, textVal); }));
                }

                textBox.autocomplete('search', textVal);
                textBox.attr('placeholder', LANG.plugins['door43translation']['selectLanguage']);
            });
        }
        else if (previousSearch.length > 0) {

            languages = textBox.autocomplete('option', 'source');
            textBox.autocomplete('option', 'source', languages.sort(function(a, b) { return sortLanguages(a, b, textVal); }));
        }
    });
}
