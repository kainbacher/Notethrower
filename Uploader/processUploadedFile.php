<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectFile.php');

if (get_param('action') == 'process') {
    $projectId    = get_numeric_param('pid');
    $filename     = get_param('filename');
    $origFilename = get_param('origFilename');
    $isMix        = get_numeric_param('isMix');
    $checksum     = get_param('cs');

    if (
        md5('PoopingInTheWoods' . $projectId . '_' . $isMix) !=
        $checksum
    ) {
        show_fatal_error_and_exit('checksum failure!');
    }

    if (!$projectId) {
        show_fatal_error_and_exit('pid param is missing!');
    }

    if (!$filename) {
        show_fatal_error_and_exit('filename param is missing!');
    }

    if (!$origFilename) {
        show_fatal_error_and_exit('origFilename param is missing!');
    }

    $project = Project::fetch_for_id($projectId);
    if (!$project || !$project->id) {
        show_fatal_error_and_exit('project not found for id: ' . $projectId);
    }

    handleNewFileUpload($projectId, $project->user_id, $filename, $origFilename, $isMix);
}

// END

// functions
function handleNewFileUpload($projectId, $userId, $filename, $origFilename, $isMix) {
    global $logger;

    $logger->info('processing new project file upload: ' . $filename . ' (orig filename: ' . $origFilename . ')');

    // TODO - check for allowed file extensions here

    $userSubdir = null;
    if (ini_get('safe_mode')) {
        $userSubdir = '/'; // in safe mode we're not allowed to create directories
    } else {
        $userSubdir = md5('Wuizi' . $userId) . '/';
    }
    $target_dir = $GLOBALS['CONTENT_BASE_PATH'] . $userSubdir;

    if (!file_exists($target_dir)) create_directory($target_dir);

    $upload_filename = $projectId . '_' . time() . '_' . $filename;
    
    move_file($GLOBALS['TMP_UPLOAD_PATH'] . $filename, $target_dir . $upload_filename, false);

    $newProjectFile = new ProjectFile();
    $newProjectFile->project_id    = $projectId;
    $newProjectFile->filename      = $userSubdir . $upload_filename;
    $newProjectFile->orig_filename = $origFilename;
    $newProjectFile->type          = $isMix == 1 ? 'mix' : 'raw';
    $newProjectFile->status        = 'active';
    $newProjectFile->save();
}

?>