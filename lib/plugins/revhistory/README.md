Revision History Plugin
=======================

An action based [DokuWiki plugin](https://www.dokuwiki.org/devel:plugins) that provides an API endpoint to request revision history.  To get the revision history, just use the following url: 

`/do=revhistory&start=[the-start-date]&end=[the-end-date]&ns=[the-namespace]`

Parameters
----------

* *do* - The action that is called.  This must be set to **revhistory**
* *start* - The starting date of the date range
* *end* - The ending date of the date range
* *ns* - The DokuWiki namespace to get revision history for

License
-------

Copyright (c) 2015 unfoldingWord released under the [MIT Creative Commons License](http://creativecommons.org/licenses/MIT/).