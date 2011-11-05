<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectFile.php');
include_once('../Includes/DB/ProjectUserVisibility.php');
include_once('../Includes/DB/TranscodingJob.php');

if (get_param('action') == 'process') {
    $userId           = get_numeric_param('uid');
    $projectId        = get_numeric_param('pid');
    $filename         = get_param('filename');
    $origFilename     = get_param('origFilename');
    $isMix            = get_numeric_param('isMix');
    $originatorUserId = get_numeric_param('originatorUserId');
    $checksum         = get_param('cs');

    if (
        md5('PoopingInTheWoods' . $userId . '_' . $projectId . '_' . $isMix . '_' . $originatorUserId) !=
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

    if ($project->visibility == 'private') {
        if (!projectIdIsAssociatedWithUserId($projectId, $userId)) {
            show_fatal_error_and_exit('user with id ' . $userId . ' is not allowed to upload a file to the ' .
                    'private project with id: ' . $projectId);
        }

    } else { // public project
        if ($originatorUserId) {
            // create visibility record so that the project appears in the collaborators list of projects
            // where he participated
            $puv = ProjectUserVisibility::fetch_for_user_id_project_id($originatorUserId, $projectId);
            if (!$puv || !$puv->project_id) {
                $puv = new ProjectUserVisibility();
                $puv->user_id    = $originatorUserId;
                $puv->project_id = $projectId;
                $puv->save();
                $logger->info('saved project/user visibility record for originator user');
            }
        }
    }

    handleNewFileUpload($projectId, $project->user_id, $filename, $origFilename, $isMix, $originatorUserId);
}

// END

// functions
function handleNewFileUpload($projectId, $userId, $filename, $origFilename, $isMix, $originatorUserId) {
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

    if ($originatorUserId) {
        $newProjectFile->originator_user_id = $originatorUserId;
    }
    
    $newProjectFile->save();
    
        
    // create transcoding job
    $fileExt = pathinfo($upload_filename, PATHINFO_EXTENSION);
   // if (strtolower($fileExt) == 'wav') {
        $logger->info('creating transcoding job');
        $job = new TranscodingJob();
        $job->projectFileId = $newProjectFile->id;
        $job->status = 'PENDING';
        $job->save();
    // }
}

?>