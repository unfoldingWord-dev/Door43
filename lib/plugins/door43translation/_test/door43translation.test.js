
describe("door43translation plugin tests", function() {
    jasmine.getFixtures().fixturesPath = 'base';

    var originalTimeout;

    beforeEach(function() {
        LANG.plugins['door43translation'] = {selectLanguage: 'Select a language'};
        originalTimeout = jasmine.DEFAULT_TIMEOUT_INTERVAL;
        jasmine.DEFAULT_TIMEOUT_INTERVAL = 5000;
    });

    afterEach(function() {
        jasmine.DEFAULT_TIMEOUT_INTERVAL = originalTimeout;
    });

    it("Namespace Autocomplete test", function() {

        loadFixtures('lib/plugins/door43translation/_test/fixtures/auto_complete_language_fixture.html');

        // verify the fixture loaded successfully
        expect(jQuery('#jasmine-fixtures')).toBeTruthy();
        var $input = jQuery('#id');
        expect($input).toBeTruthy();

        // verify the placeholder was set
        expect($input.attr('placeholder')).toEqual(LANG.plugins['door43translation']['selectLanguage']);
    });

    it("Search for English en test", function(done) {

        loadFixtures('lib/plugins/door43translation/_test/fixtures/auto_complete_language_fixture.html');

        // search for 'en'
        var languages = [];
        var $input = jQuery('#id');
        $input.val('en');

        getLanguageListItems($input, languages, function() {

            // the first item should be 'English (en)'
            expect(languages.length).toBeGreaterThan(0);
            expect(languages[0]['lc']).toEqual('en');

            // each language code should begin with
            var ul = jQuery('ul.ui-autocomplete')[0];
            jQuery(ul).find('li').each(function() {

                expect(this.innerHTML.toLowerCase()).toContain(' (en');
            });
            done();
        });
    });

    it("Search for English engl test", function(done) {

        loadFixtures('lib/plugins/door43translation/_test/fixtures/auto_complete_language_fixture.html');

        // search for 'engl'
        var languages = [];
        var $input = jQuery('#id');
        $input.val('engl');

        getLanguageListItems($input, languages, function() {

            // the first item should be 'English (en)'
            expect(languages.length).toBeGreaterThan(0);
            expect(languages[0]['value']).toEqual('English (en)');

            // each item should contain engl
            var ul = jQuery('ul.ui-autocomplete')[0];
            jQuery(ul).find('li').each(function() {

                expect(this.innerHTML.toLowerCase()).toContain('engl');
            });
            done();
        });
    });

    it("Search for 'th' test", function(done) {

        loadFixtures('lib/plugins/door43translation/_test/fixtures/auto_complete_language_fixture.html');

        // search for 'en'
        var languages = [];
        var $input = jQuery('#id');
        $input.val('th');

        getLanguageListItems($input, languages, function() {

            // the first item should be '(th)'
            expect(languages.length).toBeGreaterThan(0);
            expect(languages[0]['lc']).toEqual('th');

            // should have 'Tahaggart Tamahaq (thv)' in the list
            var filtered = languages.filter(function(e){ return e['lc'] === 'thv'; });
            expect(filtered[0]['value']).toEqual('Tahaggart Tamahaq (thv)');

            // each language code should begin with 'th'
            var ul = jQuery('ul.ui-autocomplete')[0];
            jQuery(ul).find('li').each(function() {

                expect(this.innerHTML.toLowerCase()).toContain(' (th');
            });
            done();
        });
    });

    it("Search for Tahaggart Tamahaq taha test", function(done) {

        loadFixtures('lib/plugins/door43translation/_test/fixtures/auto_complete_language_fixture.html');

        // search for 'taha'
        var languages = [];
        var $input = jQuery('#id');
        $input.val('taha');

        getLanguageListItems($input, languages, function() {

            // the first item should be 'English (en)'
            expect(languages.length).toBeGreaterThan(0);
            var filtered = languages.filter(function(e){ return e['lc'] === 'thv'; });
            expect(filtered[0]['value']).toEqual('Tahaggart Tamahaq (thv)');

            // each item should contain taha
            var ul = jQuery('ul.ui-autocomplete')[0];
            jQuery(ul).find('li').each(function() {

                expect(this.innerHTML.toLowerCase()).toContain('taha');
            });
            done();
        });
    });

    it("Switch from 'ta' to 'taha' test", function(done) {

        loadFixtures('lib/plugins/door43translation/_test/fixtures/auto_complete_language_fixture.html');

        // search for 'en'
        var languages = [];
        var $input = jQuery('#id');
        $input.val('ta');

        getLanguageListItems($input, languages, function() {

            var ul = jQuery('ul.ui-autocomplete')[0];
            jQuery(ul).find('li').each(function() {
                expect(this.innerHTML.toLowerCase().indexOf('tahaggart')).toEqual(-1);
            });

            languages = [];
            $input.val('taha');
            getLanguageListItems($input, languages, function() {

                var ul = jQuery('ul.ui-autocomplete')[0];
                var $items = jQuery(ul).find('li');

                // should return something
                expect($items.length).toBeGreaterThan(0);

                if ($items.length) {

                    // first item should be 'Tahaggart Tamahaq (thv)'
                    expect($items[0].innerHTML).toStartWith('Tahaggart Tamahaq (thv)');

                    // each item should contain taha
                    $items.each(function() {
                        expect(this.innerHTML.toLowerCase()).toContain('taha');
                    });
                }

                done();
            });
        });
    });
});
