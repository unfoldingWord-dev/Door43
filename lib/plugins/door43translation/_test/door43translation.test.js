
describe("door43translation plugin tests", function() {
    jasmine.getFixtures().fixturesPath = 'base';

    beforeEach(function() {
        LANG.plugins['door43translation'] = {selectLanguage: 'Select a language'};
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
});
