<!--
* Name: word_counts.php
* Description: Template to show the word counts to the user
*
* This comment block will be removed by the plugin before rendering.
*
* Author: Phil Hopper
* Date:   2015-11-18
-->
<script type="text/javascript">
    // load the word-count-plugin.js script file
    if (typeof word_count_plugin_script_loaded !== 'function') {
        jQuery.getScript('@DOKU_BASE@lib/plugins/door43counts/word-count-plugin.js', function() { load_word_counts(); });
    }
    else {
        load_word_counts();
    }
</script>
<div id="word-count-div" style="display: block; margin-top: 12px;">
    <h3 id="loading-h3">@loading@</h3>
    <div id="count-results-div" style="display: none;">
        <h3>@wordCounts@</h3>
        <ul></ul>
    </div>

</div>
