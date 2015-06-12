var namespaceAutoComplete = jQuery('#namespace-auto-complete');

namespaceAutoComplete.on('autocompleteclose', function() {

    var langText = jQuery(this).val();
    if (!langText) return;

    // if closed without picking from the list, do nothing
    if (langText.indexOf(')') < 3) return;

    var langCodes = langText.match(/\(([a-z0-9-]+)\)$/i);

    if ((!langCodes) || (langCodes.length !== 2)) return;

    translationSelectNamespace(langCodes[1], langText);
});

namespaceAutoComplete.on('keyup', function(event) {

    if (event.which !== 13) return;

    var $this = jQuery(this);
    var id = $this.val();

    // look up the language description from the auto-complete list
    var source = $this.autocomplete('option', 'source');
    if (source) {
         var matches = jQuery.grep(source, function (s) {
            return s.indexOf('(' + id + ')') > -1;
        });

        if (matches.length > 0) {
            translationSelectNamespace(id, matches[0]);
        }
    }
});

/**
 *
 * @param langIso The official ISO code for the language
 * @param langText The user-friendly text description of the language
 */
function translationSelectNamespace(langIso, langText) {

    // if the namespace didn't change, do nothing
    if (NS && (langIso === NS)) return;

    saveNamespace(langIso, langText)

    var action = jQuery('#namespace-auto-complete-action').val();
    var pos = action.indexOf(':');
    if (pos > -1) action = action.substr(pos + 1);

    window.location.href = DOKU_BASE + langIso + '/' + action;
}
