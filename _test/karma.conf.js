// Karma configuration

module.exports = function (config) {
    config.set({

        // base path, that will be used to resolve files and exclude
        basePath: '..',

        // frameworks to use
        frameworks: ['jasmine', 'jasmine-matchers'],

        // list of files / patterns to load in the browser
        files: [
            // bootstrap
            '_test/test_bootstrap.js',

            // standard Dokuwiki scripts
            'lib/scripts/jquery/jquery.js',
            'lib/scripts/jquery/jquery-ui.js',
            'lib/scripts/jquery/jquery.cookie.js',
            'lib/scripts/jquery/jquery-migrate.js',
            'lib/scripts/tree.js',
            'lib/scripts/*.js',

            // file needed for jasmine fixtures
            '_test/jasmine-jquery.js',

            // served fixtures
            { pattern: 'lib/plugins/**/_test/fixtures/**/*.html', included: false, served: true },

            // served script files
            { pattern: 'lib/plugins/**/*.js', included: false, served: true },

            // files to include
            'lib/plugins/door43shared/script.js',
            'lib/plugins/door43translation/script.js',

            // tests to run
            'lib/plugins/**/_test/*.test.js'
        ],
        // test results reporter to use
        // possible values: 'dots', 'progress', 'junit', 'growl', 'coverage'
        reporters: ['progress'],

        // web server port
        port: 9876,

        // enable / disable colors in the output (reporters and logs)
        colors: true,

        // level of logging
        // possible values: config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
        logLevel: config.LOG_INFO,

        // enable / disable watching file and executing tests whenever any file changes
        autoWatch: true,

        // Start these browsers, currently available:
        // - Chrome IF YOU USE CHROME, NOTE THAT IF YOU MINIMIZE CHROME, IT WILL RUN TESTS SUPER SLOWLY
        // - ChromeCanary
        // - Firefox
        // - Opera
        // - Safari (only Mac)
        // - PhantomJS
        // - IE (only Windows)
        browsers: ['Chrome', 'PhantomJS'],

        // If browser does not capture in given timeout [ms], kill it
        captureTimeout: 60000,

        // Continuous Integration mode
        // if true, it capture browsers, run tests and exit
        singleRun: false
    });
};
