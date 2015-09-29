
describe("recent languages plugin tests", function() {
    jasmine.getFixtures().fixturesPath = 'base';

    beforeEach(function() {

        // load language strings
        LANG.plugins['door43translation'] = {selectLanguage: 'Select a language'};

        // reset the cookie
        Door43Cookie.setValue('recentNamespaceCodes', '');
    });

    it("recent languages list test", function() {

        loadFixtures('lib/plugins/door43translation/_test/fixtures/recent_languages_fixture.html');

        // verify the fixture loaded successfully
        expect(jQuery('#jasmine-fixtures')).toBeTruthy();
        var $hidden = jQuery('#door43CurrentLanguage');
        expect($hidden).toBeTruthy();

        // set the current language and action
        $hidden.val('jbb:Jibberish');
        document.getElementById('namespace-auto-complete-action').value = 'jbb:obs:01';
        expect(document.getElementById('door43CurrentLanguage').value).toEqual('jbb:Jibberish');

        // set the cookie - list of languages
        saveNamespace('en', 'English');
        saveNamespace('fr', 'French');
        saveNamespace('es', 'Spanish');
        saveNamespace('jbb', 'Jibberish');
        expect(Door43Cookie.getValue('recentNamespaceCodes')).toEqual('en:English;fr:French;es:Spanish;jbb:Jibberish');

        // run the code to build the list
        buildRecentLanguagesList();

        // verify the list
        var $items = jQuery('#door43RecentLanguageList').find('li');
        expect($items.length).toEqual(4);
        expect(jQuery($items[0]).find('a').attr('href')).toEqual('/jbb/obs/01');
        expect(jQuery($items[1]).find('a').attr('href')).toEqual('/es/obs/01');
        expect(jQuery($items[2]).find('a').attr('href')).toEqual('/fr/obs/01');
        expect(jQuery($items[3]).find('a').attr('href')).toEqual('/en/obs/01');
    });

    it("recent languages no namespace test", function() {

        loadFixtures('lib/plugins/door43translation/_test/fixtures/recent_languages_fixture.html');

        // verify the fixture loaded successfully
        expect(jQuery('#jasmine-fixtures')).toBeTruthy();
        var $hidden = jQuery('#door43CurrentLanguage');
        expect($hidden).toBeTruthy();

        // set the current language and action
        $hidden.val('');
        document.getElementById('namespace-auto-complete-action').value = 'home';
        expect(document.getElementById('door43CurrentLanguage').value).toEqual('');

        // set the cookie - list of languages
        saveNamespace('en', 'English');
        saveNamespace('fr', 'French');
        saveNamespace('es', 'Spanish');
        saveNamespace('jbb', 'Jibberish');
        expect(Door43Cookie.getValue('recentNamespaceCodes')).toEqual('en:English;fr:French;es:Spanish;jbb:Jibberish');

        // run the code to build the list
        buildRecentLanguagesList();

        // verify the list
        var $items = jQuery('#door43RecentLanguageList').find('li');
        expect($items.length).toEqual(4);
        expect(jQuery($items[0]).find('a').attr('href')).toEqual('/jbb/home');
        expect(jQuery($items[1]).find('a').attr('href')).toEqual('/es/home');
        expect(jQuery($items[2]).find('a').attr('href')).toEqual('/fr/home');
        expect(jQuery($items[3]).find('a').attr('href')).toEqual('/en/home');
    });

    it("recent languages no namespace longer action test", function() {

        loadFixtures('lib/plugins/door43translation/_test/fixtures/recent_languages_fixture.html');

        // verify the fixture loaded successfully
        expect(jQuery('#jasmine-fixtures')).toBeTruthy();
        var $hidden = jQuery('#door43CurrentLanguage');
        expect($hidden).toBeTruthy();

        // set the current language and action
        $hidden.val('');
        document.getElementById('namespace-auto-complete-action').value = 'asdfg:home:01';
        expect(document.getElementById('door43CurrentLanguage').value).toEqual('');

        // set the cookie - list of languages
        saveNamespace('en', 'English');
        saveNamespace('fr', 'French');
        saveNamespace('es', 'Spanish');
        saveNamespace('jbb', 'Jibberish');
        expect(Door43Cookie.getValue('recentNamespaceCodes')).toEqual('en:English;fr:French;es:Spanish;jbb:Jibberish');

        // run the code to build the list
        buildRecentLanguagesList();

        // verify the list
        var $items = jQuery('#door43RecentLanguageList').find('li');
        expect($items.length).toEqual(4);
        expect(jQuery($items[0]).find('a').attr('href')).toEqual('/jbb/asdfg/home/01');
        expect(jQuery($items[1]).find('a').attr('href')).toEqual('/es/asdfg/home/01');
        expect(jQuery($items[2]).find('a').attr('href')).toEqual('/fr/asdfg/home/01');
        expect(jQuery($items[3]).find('a').attr('href')).toEqual('/en/asdfg/home/01');
    });
});
