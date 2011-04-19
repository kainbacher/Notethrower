<?php

include_once('Config.php');
include_once('Snippets.php');

function processAndPrintTpl($tplFile, $assignments = array(), $useMobileVersion = false) {
    echo processTpl($tplFile, $assignments, $useMobileVersion);
}

function processAndPrintTplData(&$data, $assignments = array(), $useMobileVersion = false) {
    echo processTplData($data, $assignments, $useMobileVersion);
}

// takes a template name (which is a filepath relative to the template root folder) and an assignments array for the
// template variables and returns the resolved template string
function processTpl($tplFile, $assignments = array(), $useMobileVersion = false) {
    if ($useMobileVersion) {
        $path = dirname($tplFile);
        $file = basename($tplFile);
        $tplFile = $path . '/mobile/' . $file;
    }

    if (!file_exists($GLOBALS['TEMPLATES_BASE_PATH'] . $tplFile)) {
        show_fatal_error_and_exit('template file not found: ' . $tplFile);
    }

    $data = file_get_contents($GLOBALS['TEMPLATES_BASE_PATH'] . $tplFile);
    return processTplData($data, $assignments, $useMobileVersion);
}

// takes a template string and an assignments array for the template variables and returns the resolved template string
function processTplData(&$data, $assignments = array(), $useMobileVersion = false) {
    return str_replace(array_keys($assignments), array_values($assignments), $data);
}

?>