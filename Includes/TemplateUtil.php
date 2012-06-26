<?php

include_once('Config.php');
include_once('Snippets.php');

function processAndPrintTpl($tplFile, $assignments = array(), $showMobileVersion = false) {
    echo processTpl($tplFile, $assignments, $showMobileVersion);
}

function processAndPrintTplData(&$data, $assignments = array(), $showMobileVersion = false) {
    echo processTplData($data, $assignments, $showMobileVersion);
}

// takes a template name (which is a filepath relative to the template root folder) and an assignments array for the
// template variables and returns the resolved template string
function processTpl($tplFile, $assignments = array(), $showMobileVersion = false) {
    global $logger;
    
    if ($showMobileVersion) {
        $path = dirname($tplFile);
        $file = basename($tplFile);
        $tplFile = $path . '/mobile/' . $file;
        
        if (!file_exists($GLOBALS['TEMPLATES_BASE_PATH'] . $tplFile)) {
            $logger->warn('mobile template file not found (' . $tplFile . '), using regular version as fallback (' . $path . '/' . $file . ')');
            $tplFile = $path . '/' . $file;
        }
    }

    if (!file_exists($GLOBALS['TEMPLATES_BASE_PATH'] . $tplFile)) {
        show_fatal_error_and_exit('template file not found: ' . $tplFile);
    }

    $data = file_get_contents($GLOBALS['TEMPLATES_BASE_PATH'] . $tplFile);
    return processTplData($data, $assignments, $showMobileVersion);
}

// takes a template string and an assignments array for the template variables and returns the resolved template string
function processTplData(&$data, $assignments = array(), $showMobileVersion = false) {
    if (
        !isset($assignments['${baseUrl}']) &&
        isset($GLOBALS['BASE_URL'])
    ) {
        $assignments['${baseUrl}'] = $GLOBALS['BASE_URL']; // this is something that is needed in many places
    }
        
    return str_replace(array_keys($assignments), array_values($assignments), $data);
}

?>
