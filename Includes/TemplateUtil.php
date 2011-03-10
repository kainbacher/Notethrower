<?php

include_once('Config.php');
include_once('Snippets.php');

function processAndPrintTpl($tplFile, $assignments = array()) {
    echo processTpl($tplFile, $assignments);
}

function processAndPrintTplData(&$data, $assignments = array()) {
    echo processTplData($data, $assignments);
}

// takes a template name (which is a filepath relative to the template root folder) and an assignments array for the
// template variables and returns the resolved template string
function processTpl($tplFile, $assignments = array()) {
    if (!file_exists($GLOBALS['TEMPLATES_BASE_PATH'] . $tplFile)) {
        show_fatal_error_and_exit('template file not found: ' . $tplFile);
    }

    $data = file_get_contents($GLOBALS['TEMPLATES_BASE_PATH'] . $tplFile);
    return processTplData($data, $assignments);
}

// takes a template string and an assignments array for the template variables and returns the resolved template string
function processTplData(&$data, $assignments = array()) {
    return str_replace(array_keys($assignments), array_values($assignments), $data);
}

?>