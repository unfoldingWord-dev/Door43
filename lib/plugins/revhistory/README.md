Revision History Plugin
=======================

An action based [DokuWiki plugin](https://www.dokuwiki.org/devel:plugins) that provides an API endpoint to request revision history.  To get the revision history, just sent a **GET** request to the following url: 

`/?do=revhistory&start=[the-start-date]&end=[the-end-date]&ns=[the-namespace]&media=[0-or-1]&order=[desc-or-asc]`

Parameters
----------

* *do*      - The action that is called.  **This must be set to revhistory**.
* *start*   - Get all changes on and after the starting date.
* *end*     - Get all changes prior and equal to the ending date.
* *ns*      - The DokuWiki namespace to limit the changes to.
* *media*   - (0 or 1) Get media file changes as well.  Will default to system setting. (Default 1)
* *order*   - (desc or asc) The date order, descending or ascending, you want the returned data in. (Default desc)

Response
--------

The response you receive back is a JSON object.  This object contains three keys: 

* *success*         - (success or error) Did the request successfully process?
* *error_message*   - If there was an error, this message will let you know what happened.
* *changes*         - An array of JSON objects each containing a single change.

Each change JSON object is constructed like the following:

```
{
    "date":1440525038,
    "ip":"65.129.89.43",
    "type":"E",
    "id":"en:obs:38",
    "user":"superdav42",
    "sum":"",
    "extra":"",
    "file_type":"file"
}
```

Here is the definition for each field:

* *date*        - A [unix timestamp](http://www.unixtimestamp.com) representing the date and time of the change.
* *extra*       - Extra information regarding the change.
* *file_type*   - (file or media) The type of file changed.
* *id*          - The namespace or id representing the changed DokuWiki page or media item.
* *ip*          - The ip address of the individual who made the change.
* *sum*         - A summary of why the action was taken.
* *type*        - The type of change implemented.
    * *C*       - Create action.
    * *D*       - Delete action.
    * *E*       - Edit action.
    * *e*       - Minor edit action.
    * *R*       - Revert action.
* *user*        - The username of the person who made the change.

Configuration Manager
---------------------

If you have installed the [Configuration Manager](https://www.dokuwiki.org/plugin:config), you can set some settings for this plugin.  Here is how you can do that:

1. Log into your admin panel.
2. Click on the **Configuration Settings** link in the Admin area.
3. On the right, in the Plugin section, click on **Revhistory**.
4. Make your changes.
5. Scroll to the bottom, and click the **Save** button.

License
-------

Copyright (c) 2015 unfoldingWord released under the [MIT Creative Commons License](http://creativecommons.org/licenses/MIT/).