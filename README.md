[![Travis](https://travis-ci.org/unfoldingWord-dev/Door43.svg)](https://travis-ci.org/unfoldingWord-dev/Door43)

# Door43 DokuWiki Code

This repo is our unified DokuWiki + plugins repo.

To setup:

1. Clone this Door43 repo onto your web server.

2. Make a virtual host that points to the Door43 directory. (If you instead have Door43 as a subdirectory in your htdocs
directory, you will need to change the RewriteBase in the .htaccess file and then run this command so it doesn't commit
it to the repo: `git update-index --assume-unchanged .htaccess`)

3. Switch to the development branch: `git checkout development`

4. Make sure that your web server's process has write access to the conf and data directories and all files and subdirectories.

5. Setup the submodules, such as enhancedindexer and pagequery, by running the following commands:
  ```
  git submodule init
  git submodule update
  ```

6. Setup your user and acl config files by copying the .dist config files to their regular names:<br/>
  ```
  cp conf/users.auth.php.dev conf/users.auth.php
  cp conf/acl.auth.php.dev conf/acl.auth.php
  ```

7. Put other config files in place (this have different settings on production):
  ```
  cp conf/local.php.dev conf/local.php
  cp conf/plugins.local.php.dev conf/plugins.local.php
  ```

8. You can now go to http://&lt;your.door43.domain&gt;/home?do=login and login as admin, password admin.

9. You can manually check out content for each language by cloning their repo into data/gitrepo/pages:
  ```
  cd Door43/data/gitrepo/pages
  git clone git@github.com:Door43/d43-<LanguageCode>.git <LanguageCode>
  (e.g: git clone git@github.com:Door43/d43-en.git en) 
  ```

Steps 5 thru 7 above will soon be replaced with a bootstrap.php file that will set up submodules and config files and content.


### Unit Testing

General instructions for unit testing in Dokuwiki can be found [here](https://www.dokuwiki.org/devel:unittesting).

Instruction for writing PHPUnit tests can be found on the [PHPUnit website](https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html).

**IMPORTANT:** You should use `extends DokuWikiTest` instead of `extends PHPUnit_Framework_TestCase` when creating new
test cases.  For examples, see the files in the `_test/tests/test` directory.

Each code change should be accompanied by unit tests that show the feature is working correctly.  Run all unit tests
before you submit a pull request to be sure all tests are passing.  If a test is not passing, you need to figure out if
something you did broke the test and fix it.

