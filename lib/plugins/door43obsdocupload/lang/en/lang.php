<?php
/**
 * Name: lang.php
 * Description: The English language localization file for door43obsdocupload plugin.
 *
 * Author: Phil Hopper
 * Date:   2015-05-20
 */

// menu entry for admin plugins
// $lang['menu'] = 'Your menu entry';

// custom language strings for the plugin
$lang['getTemplate'] = 'Get template';
$lang['exportTitle'] = 'Export OBS Template';
$lang['draftLabel'] = 'Use the current language as a DRAFT template';
$lang['sourceLabel'] = 'Use checked source language';
$lang['includeImagesLabel'] = 'Include all images in the file';
$lang['includeSub3Label'] = 'Include Level 1 and 2 languages';
$lang['selectDocxFileLabel'] = 'Choose a DOCX file to upload';
$lang['importDocx'] = 'Import DOCX file';
$lang['importTitle'] = 'Import DOCX file';
$lang['selectNamespaceLabel'] = 'Importing for language';
$lang['docxImportSucceeded'] = 'Import of OBS from DOCX completed successfully. <a href="%1$s" target="_blank">You can preview the stories here.</a> If you are satisfied, click the Publish button below.';
$lang['loginRequired'] = 'You must be logged in before you can upload a file.';
$lang['uploadProgress'] = 'Upload progress';
$lang['uploadProcessing'] = 'Processing the uploaded file';
$lang['publishSucceeded'] = 'OBS has been published successfully.';
$lang['backMatterHeader'] = 'Back Matter';

// error messages
$lang['templateFileCreateError'] = 'Error creating the %1$s file';
$lang['fileNotUploaded'] = 'The file was not successfully uploaded';
$lang['fileTypeNotSupported'] = 'Only .docx and .zip files are supported.';
$lang['zipNoDocxFiles'] = 'No .docx files were found in the zip file.';
$lang['zipMultipleDocxFiles'] = 'More than one .docx file was found in the zip file.';
$lang['pandocError'] = 'An error occurred converting the file to Dokuwiki format:';
$lang['obsNotInitialized'] = 'Open Bible Stories has not been initialized for %1$s. <a href="%2$s">Click here to go to the initialization page.</a>';
$lang['previewNotFound'] = 'The preview directory was not found.';
$lang['notAbleToCopy'] = 'Door43 was not able to copy %1$s.';
$lang['sourceFileNotFound'] = 'Source file not found: %1$s';

// localized strings for JavaScript
// js example: var text = LANG.plugins['door43register']['translate'];
//$lang['js']['translate'] = 'Translate';

