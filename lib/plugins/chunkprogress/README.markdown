% Chunk Status Activity Reports


This document describes how to run the status activity reports in DokuWiki.

Each report is set up by adding a special marker to an existing DokuWiki page
while in edit mode.  When the page is then put into view mode, the report will
run and display the results on the page.

An example marker looks like this:

~~~ 
{{chunkprogress>report=activity_by_namespace&namespace=en:bible:notes:rom&start_date=7/01&end_date=7/31}}
~~~

Two reports are available, the Activity by User report and the Activity by
Namespace report.


Activity By User Report
-----------------------

This report shows how many chunks were changed by each user in the given time period.

![Activity by User report](doc/activity_by_user_example_1.png)


### Activity by User Report Options {style="page-break-before:always;"}

report
:    *Required.*  Always `activity_by_user`.

namespace
:    *Required.* The namespace in which to count the user activity.  Should be
    in colon format, e.g. `en:bible:notes:rom`.

    Example: `{{chunkprogress>report=activity_by_user&namespace=en:bible:notes:rom}}`

start_date
:    *Optional.*  The date from which to start counting changes.  If left out,
    all changes since the beginning will be counted.

    Example: `{{chunkprogress>report=activity_by_user&namespace=en:bible:notes:rom&start_date=7/01}}`

end_date
:    *Optional.*  The date after which to stop counting changes.  If left out, 
    all changes up to the present will be counted.

    Example: `{{chunkprogress>report=activity_by_user&namespace=en:bible:notes:rom&end_date=7/31}}`

users
:    *Optional.*  The space-separated list of users to show, for example
    `users=cnewton theologyjohn`.  If left out, all users will be shown.

    Example: `{{chunkprogress>report=activity_by_user&namespace=en:bible:notes:rom&start_date=7/01&end_date=7/31&users=cnewton theologyjohn&debug=true}}`


Activity By Namespace Report {style="page-break-before:always;"}
----------------------------

This shows how many chunks were changed in a namespace for the given time period.

![Activity by Namespace report](doc/activity_by_namespace_example_1.png)


### Activity by Namespace Report Options {style="page-break-before:always;"}

report
:    *Required.*  Always `activity_by_namespace`.

namespace
:    *Required.* The namespace in which to count the user activity.  Should be
    in colon format, e.g. `en:bible:notes:rom`.

    Example: `{{chunkprogress>report=activity_by_namespace&namespace=en:bible:notes:rom}}`

start_date
:    *Optional.*  The date from which to start counting changes.  If left out,
    all changes since the beginning will be counted.

    Example: `{{chunkprogress>report=activity_by_namespace&namespace=en:bible:notes:rom&start_date=7/01}}`

end_date
:    *Optional.*  The date after which to stop counting changes.  If left out, 
    all changes up to the present will be counted.

    Example: `{{chunkprogress>report=activity_by_namespace&namespace=en:bible:notes:rom&end_date=7/31}}`

