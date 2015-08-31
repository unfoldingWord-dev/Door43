<!--
* Name: obs_import_dlg.php
* Description: The html and javascript that implements the UI for the OBS doc template import dialog.
*
* This comment block will be removed by the plugin before rendering.
*
* Author: Phil Hopper
* Date:   2015-08-27
-->
<div style="display: block; margin-top: 12px; line-height: 1.8em;">
    <style>
        #upload-docx-div div { margin-bottom: 12px; }
        #upload-docx-div p { display: none; margin: 6px 0 0 8px; }
        #upload-docx-div .success { color: #050; }
        #upload-docx-div .failure { color: #900; }
        #upload-docx-div progress { width: 250px; height: 22px; border: 1px solid #ccc; background-color: #f3f3f3; }
    </style>
    <div id="upload-docx-div">
        <div>
            <label for="selectDocxNamespace">@selectNamespaceLabel@</label><br>
            <?php
/* @var $translation helper_plugin_door43translation */
$translation = &plugin_load('helper','door43translation');
if ($translation) echo $translation->renderAutoCompleteTextBox('selectDocxNamespace', 'selectDocxNamespace', 'width: 250px;');
            ?>
        </div>
        <div>
            <label for="uploadDocxFile">@selectDocxFileLabel@</label><br>
            <input id="uploadDocxFile" type="file" accept=".docx,.zip">
        </div>
        <div style="padding-top: 16px;">
            <button onclick="importSourceDocx();">@importButton@</button>
            <p id="obsDocxUploadMessage" class="success failure">&nbsp;</p>
        </div>
        <div id="progressDiv" style="display: none;">
            <label>@uploadProgress@</label><br><progress value="0"></progress>
        </div>
        <div id="processingDiv" style="display: none;">
            <label>@uploadProcessing@</label>&nbsp;
            <img src="@DOKU_BASE@lib/plugins/door43shared/images/processing.gif" style="height: 24px;" height="24">
        </div>
        <div id="publishDiv" style="display: none;">
            <button onclick="publishUploadedOBS();">@publishButton@</button>
            <p id="obsDocxUploadMessage" class="success failure">&nbsp;</p>
        </div>
    </div>
</div>
<script type="text/javascript">

    // load the obs-upload.js script file
    if (typeof importSourceDocx !== 'function') {
        jQuery.getScript('@DOKU_BASE@lib/plugins/door43obsdocupload/obs-upload.js');
    }
</script>
