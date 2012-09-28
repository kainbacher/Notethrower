<?php
include_once('../Includes/Init.php');

include_once('../Includes/FormUtil.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Attribute.php');
include_once('../Includes/DB/Genre.php');
include_once('../Includes/DB/Message.php');
include_once('../Includes/DB/Mood.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectAttribute.php');
include_once('../Includes/DB/ProjectFile.php');
include_once('../Includes/DB/ProjectFileAttribute.php');
include_once('../Includes/DB/ProjectGenre.php');
include_once('../Includes/DB/ProjectMood.php');
include_once('../Includes/DB/ProjectUserVisibility.php');
include_once('../Includes/DB/ReleaseContribution.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/Mailer/MailUtil.php');

// some notes on permissions:
// + a private project can only be seen and modified by invited (=associated) users
// + everyone which is logged in can upload files to a public project
// + everyone which is not logged in can view public projects and download files

$logger->set_info_level();

$loggedInUser = User::new_from_cookie();

// variables for basics form
$project = null;
$unpersistedProject = null;
$generalMessageList = '';
$projectFilesMessageList = '';
$problemOccured = false;
$errorFields = Array();

// variables for publish form
$projectFile = null;
$unpersistedProjectFile = null;
$publishMessageList = '';
$problemOccuredForPublish = false;
$errorFieldsForPublish = Array();

$projectId = get_numeric_param('pid'); // this is only set in an update scenario

$activeTab = 'basics';
if (get_param('tab')) {
    $activeTab = get_param('tab');
}

if (get_param('action') == 'create') {
    ensureUserIsLoggedIn($loggedInUser);

    $project = new Project();
    $project->user_id                   = $loggedInUser->id;
    $project->title                     = '';
    $project->currency                  = 'USD'; // TODO - take from config - check other occurences as well
    $project->visibility                = 'public';
    $project->playback_count            = 0;
    $project->download_count            = 0;
    $project->status                    = 'newborn';
    $project->sorting                   = 0;
    $project->rating_count              = 0;
    $project->rating_value              = 0;
    $project->competition_points        = 0;
    $project->save();

    // create a visibility record for this user
    $atav = new ProjectUserVisibility();
    $atav->user_id = $loggedInUser->id;
    $atav->project_id = $project->id;
    $atav->save();

} else if (get_param('action') == 'edit') {
    // this can be called by:
    //   + if the project is public:
    //     + everyone
    //   + if the project is private:
    //     + both the project owner and collaboration artists for this project

    if (!$projectId) {
        show_fatal_error_and_exit('cannot save without a project id!');
    }

    $project = Project::fetch_for_id($projectId);
    if (!$project || !$project->id) {
        show_fatal_error_and_exit('found no project with id: ' . $projectId);
    }

    if ($project->status != 'active' && (!$loggedInUser || $loggedInUser->id != $project->user_id)) {
        show_fatal_error_and_exit('project is inactive!');
    }

    if ($project->visibility == 'private') {
        ensureUserIsLoggedIn($loggedInUser);
        ensureProjectIdIsAssociatedWithUserId($project->id, $loggedInUser->id);
    }

} else if (get_param('action') == 'save') { // save project basic info. can only be called by the project owner.
    ensureUserIsLoggedIn($loggedInUser);

    $logger->info('attempting to save project data ...');
    if (!$projectId) {
        show_fatal_error_and_exit('cannot save without a project id!');
    }

    $project = Project::fetch_for_id($projectId);
    if (!$project || !$project->id) {
        show_fatal_error_and_exit('project not found for id: ' . $projectId);
    }

    ensureProjectBelongsToUserId($project, $loggedInUser->id);

    if (inputDataOk($errorFields, $project)) {
        processParams($project, $loggedInUser);

        if ($project->status == 'newborn') {
            $project->status = 'active';
        }

        $project->save();

        $generalMessageList .= processTpl('Common/message_success.html', array(
            '${msg}' => 'Successfully saved project data.'
        ));
        $activeTab = 'upload';

    } else {
        $logger->info('input data was invalid: ' . print_r($errorFields, true));
        $unpersistedProject = new Project();
        processParams($unpersistedProject, $loggedInUser);
        $generalMessageList .= processTpl('Common/message_error.html', array(
            '${msg}' => 'Please correct the highlighted problems!'
        ));
        $problemOccured = true;
    }

} else if (get_param('action') == 'delete') {
    ensureUserIsLoggedIn($loggedInUser);

    if (!$projectId) {
        show_fatal_error_and_exit('cannot delete without a project id!');
    }

    $project = Project::fetch_for_id($projectId);
    if (!$project || !$project->id) {
        show_fatal_error_and_exit('project not found for id: ' . $projectId);
    }

    ensureProjectBelongsToUserId($project, $loggedInUser->id);

    Project::delete_with_id($projectId);
    ProjectAttribute::deleteForProjectId($projectId);

    header('Location: projectList.php');
    exit;

} else if (get_param('action') == 'deleteProjectFile') { // ajax action
    ensureUserIsLoggedIn($loggedInUser);

    $logger->info('deleting project file ...');
    if (!$projectId) {
        //show_fatal_error_and_exit('cannot delete project file without a project id!');
        $jsonReponse = array(
            'result' => 'ERROR',
            'error'  => 'cannot delete project file without a project id!'
        );
        sendJsonResponseAndExit($jsonReponse);
    }

    $project = Project::fetch_for_id($projectId);
    if (!$project || !$project->id) {
        show_fatal_error_and_exit('project not found for id: ' . $projectId);
    }

    $file = ProjectFile::fetch_for_id(get_numeric_param('fid'));
    if (!$file) {
        //show_fatal_error_and_exit('project file not found!');
        $jsonReponse = array(
            'result' => 'ERROR',
            'error'  => 'project file not found'
        );
        sendJsonResponseAndExit($jsonReponse);
    }

    ensureProjectFileBelongsToProjectId($file, $projectId);

    if (
        $file->originator_user_id && $file->originator_user_id == $loggedInUser->id || // file was contributed by the person which is logged in OR
        $project->user_id == $loggedInUser->id                                         // owner is logged in
    ) {
        ProjectFile::delete_with_id(get_numeric_param('fid'));

        // if file was contributed by another user and the owner is logged in, notify that other user that his file was deleted.
        if ($file->originator_user_id && $file->originator_user_id != $loggedInUser->id) {
            $m = new Message();
            $m->sender_user_id    = $loggedInUser->id;
            $m->recipient_user_id = $file->originator_user_id;
            $m->subject           = 'One of your uploaded project files was deleted by the project owner.';
            $m->text              = 'Your project file "' . $file->orig_filename . '" was deleted from project "' . $project->title . '" by the project owner "' . $loggedInUser->name . '".';
            $m->save();
        }

        $jsonReponse = array(
            'result'    => 'OK',
            'projectId' => $projectId
        );
        sendJsonResponseAndExit($jsonReponse);

    } else {
        show_fatal_error_and_exit('user with id ' . $loggedInUser->id . ' is not allowed to delete project ' .
                'file with id: ' . $file->id . ' (originator user id: ' . $file->originator_user_id . ')');
    }

} else if (get_param('action') == 'getProjectFilesHtml') { // ajax action, called after a project file upload or delete
    ensureUserIsLoggedIn($loggedInUser);

    if (!$projectId) {
        show_fatal_error_and_exit('pid param is missing!');
    }

    $project = Project::fetch_for_id($projectId);
    if (!$project || !$project->id) {
        show_fatal_error_and_exit('project not found for id: ' . $projectId);
    }

    if ($project->visibility == 'private') {
        ensureProjectIdIsAssociatedWithUserId($project->id, $loggedInUser->id);
    }

    echo getUploadedFilesSection($project, $projectFilesMessageList, $loggedInUser);
    exit;

} else if (get_param('action') == 'downloadProjectFiles') {
    // this can be called by:
    //   + if the project is public:
    //     + everyone
    //   + if the project is private:
    //     + both the project owner and collaboration artists for this project

    deleteOldFilesMatchingPatternInDirectory('*.zip', $GLOBALS['TEMP_FILES_BASE_PATH'], 3); // cleanup old temp zip files first

    $idListStr = get_param('fileIds');
    if ($idListStr) {
        $project = Project::fetch_for_id($projectId);
        if (!$project || !$project->id) {
            show_fatal_error_and_exit('project not found for id: ' . $projectId);
        }

        if ($project->visibility == 'private') {
            ensureUserIsLoggedIn($loggedInUser);
            ensureProjectIdIsAssociatedWithUserId($project->id, $loggedInUser->id);
        }

        $idList = explode(',', $idListStr);

        foreach ($idList as $pfid) {
            $pf = ProjectFile::fetch_for_id($pfid);
            ensureProjectFileBelongsToProjectId($pf, $projectId);
        }

        $zipFilepath = putProjectFilesIntoZip($idList);
        redirectTo($GLOBALS['TEMP_FILES_BASE_URL'] . basename($zipFilepath));

    } else {
        echo 'no file ids specified';
    }

    exit;

} else if (get_param('action') == 'saveProjectFileMetadata') {
    ensureUserIsLoggedIn($loggedInUser);

    if (!$projectId) {
        show_fatal_error_and_exit('cannot save project file metadata without a project id!');
    }

    $project = Project::fetch_for_id($projectId);
    if (!$project || !$project->id) {
        show_fatal_error_and_exit('project not found for id: ' . $projectId);
    }

    if ($project->visibility == 'private') {
        ensureProjectIdIsAssociatedWithUserId($project->id, $loggedInUser->id);
    }

    $file = ProjectFile::fetch_for_id(get_numeric_param('fid'));
    if (!$file) {
        show_fatal_error_and_exit('project file not found for id: ' . get_numeric_param('fid'));
    }

    ensureProjectFileBelongsToProjectId($file, $projectId);

    // check if user has edit permissions for this file
    $loggedInUserMayEdit = false;
    if (
        $file->originator_user_id && $file->originator_user_id == $loggedInUser->id || // file was contributed by the person which is logged in
        !$file->originator_user_id && $project->user_id == $loggedInUser->id           // file was uploaded by owner and owner is logged in
    ) {
        $loggedInUserMayEdit = true;
    }

    if (!$loggedInUserMayEdit) {
        show_fatal_error_and_exit('user with id ' . $loggedInUser->id . ' must not edit metadata of project ' .
                'file with id: ' . $file->id . ' (originator user id: ' . $file->originator_user_id . ')');
    }

    if (strlen(get_param('comment')) > 500) {
        $file->comment = substr(get_param('comment'), 0, 500); // truncate if too long. the ui takes care that the input is not too long.
    } else {
        $file->comment = get_param('comment');
    }
    $file->save();

    // handle attributes
    ProjectFileAttribute::deleteForProjectFileId($file->id); // first, delete all existing attributes
    ProjectFileAttribute::addAll(explode(',', get_param('projectFileAttributesList_' . $file->id)), $file->id); // then save the selected attributes

    $activeTab = 'upload'; // jump to the correct tab when the page is reloaded

} else if (get_param('action') == 'join') {
    ensureUserIsLoggedIn($loggedInUser);

    if (!$projectId) {
        show_fatal_error_and_exit('cannot save project file metadata without a project id!');
    }

    $project = Project::fetch_for_id($projectId);
    if (!$project || !$project->id) {
        show_fatal_error_and_exit('project not found for id: ' . $projectId);
    }

    if ($project->visibility == 'public') {
        // create visibility record so that the project appears in the collaborators list of projects
        // where the user participated
        $puv = ProjectUserVisibility::fetch_for_user_id_project_id($loggedInUser->id, $projectId);
        if (!$puv || !$puv->project_id) {
            $puv = new ProjectUserVisibility();
            $puv->user_id    = $loggedInUser->id;
            $puv->project_id = $projectId;
            $puv->save();
            $logger->info('saved project/user visibility record for logged in user');
        }
    }

    $activeTab = 'upload'; // jump to the correct tab when the page is reloaded

} else if (get_param('action') == 'publishEdit') {
    ensureUserIsLoggedIn($loggedInUser);
    
    if (!$projectId) {
        show_fatal_error_and_exit('cannot save without a project id!');
    }

    $project = Project::fetch_for_id($projectId);
    if (!$project || !$project->id) {
        show_fatal_error_and_exit('found no project with id: ' . $projectId);
    }

    ensureProjectBelongsToUserId($project, $loggedInUser->id);
    
    $projectFile = ProjectFile::fetch_for_id(get_numeric_param('pfid'));
    if (!$projectFile) {
        show_fatal_error_and_exit('project file not found for id: ' . get_numeric_param('pfid'));
    }

    ensureProjectFileBelongsToProjectId($projectFile, $projectId);
    
    if ($projectFile->type != 'mix' && $projectFile->type != 'release') {
        show_fatal_error_and_exit('project file is neither a mix nor a release!');
    }
    
    $activeTab = 'publish';
    
} else if (get_param('action') == 'publishSave') { // publish a song (project file -> releas). can only be called by the project owner.
    ensureUserIsLoggedIn($loggedInUser);

    $logger->info('attempting to publish song ...');
    if (!$projectId) {
        show_fatal_error_and_exit('cannot publish without a project id!');
    }

    $project = Project::fetch_for_id($projectId);
    if (!$project || !$project->id) {
        show_fatal_error_and_exit('project not found for id: ' . $projectId);
    }

    ensureProjectBelongsToUserId($project, $loggedInUser->id);
    
    $projectFile = ProjectFile::fetch_for_id(get_numeric_param('pfid'));
    if (!$projectFile) {
        show_fatal_error_and_exit('project file not found for id: ' . get_numeric_param('pfid'));
    }

    ensureProjectFileBelongsToProjectId($projectFile, $projectId);

    if (inputDataOkForPublish($errorFieldsForPublish, $projectFile)) {
        processParamsForPublish($projectFile, $loggedInUser);
        
        if (!$projectFile->release_date) $projectFile->release_date = date('Y-m-d H:i:s'); // set release date
        $projectFile->type = 'release';  
        $projectFile->orig_filename = '1ldr-' . 
                                      $projectFile->id . '-' . 
                                      substr(sanitizeFilename($project->title), 0, 12) . '-' .
                                      substr(sanitizeFilename($projectFile->release_title), 0, 30) . 
                                      '.' . getFileExtension($projectFile->filename);        
        $projectFile->save();

        $publishMessageList .= processTpl('Common/message_success.html', array(
            '${msg}' => 'Successfully published song.'
        ));

    } else {
        $logger->info('input data was invalid: ' . print_r($errorFieldsForPublish, true));
        $unpersistedProjectFile = new ProjectFile();
        processParamsForPublish($unpersistedProjectFile, $loggedInUser);
        $publishMessageList .= processTpl('Common/message_error.html', array(
            '${msg}' => 'Please correct the highlighted problems!'
        ));
        $problemOccuredForPublish = true;        
    }
    
    $activeTab = 'publish';
}

// form fields
$basicsFormElementsList  = '';
$publishFormElementsList = '';
$contributionsList = '';
if ($loggedInUser && $project->user_id == $loggedInUser->id) { // logged-in user is the project owner
    $basicsFormElementsList .= getFormFieldForParams(array(
        'propName'               => 'title',
        'label'                  => 'Project title',
        'mandatory'              => true,
        'maxlength'              => 255,
        'obj'                    => $project,
        'unpersistedObj'         => $unpersistedProject,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured
    ));

    // main genre
    $basicsFormElementsList .= getFormFieldForParams(array(
        'inputType'              => 'select2',
        'propName'               => 'mainGenre',
        'label'                  => 'Main genre',
        'mandatory'              => true,
        'cssClassSuffix'         => 'chzn-select chzn-modify', // this triggers a conversion to a "chosen" select field
        'obj'                    => $project,
        'unpersistedObj'         => $unpersistedProject,
        'selectOptions'          => Genre::getSelectorOptionsArray(true),
        'objValue'               => $problemOccured ? $unpersistedProject->unpersistedProjectMainGenre : ProjectGenre::getMainGenreIdForProjectId($project->id),
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured
    ));

    // sub genres
    $basicsFormElementsList .= getFormFieldForParams(array(
        'inputType'              => 'multiselect2',
        'propName'               => 'subGenres',
        'label'                  => 'Sub genres',
        'mandatory'              => false,
        'cssClassSuffix'         => 'chzn-select chzn-modify', // this triggers a conversion to a "chosen" select field
        'obj'                    => $project,
        'unpersistedObj'         => $unpersistedProject,
        'selectOptions'          => Genre::getSelectorOptionsArray(),
        'objValues'              => $problemOccured ? $unpersistedProject->unpersistedProjectSubGenres : ProjectGenre::getSubGenreIdsForProjectId($project->id),
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured
    ));

    /* DEPRECATED - handled by chosen.js
    // "create new genre"
    $basicsFormElementsList .= getFormFieldForParams(array(
        'propName'               => 'newGenre',
        'label'                  => 'Add new genre',
        'mandatory'              => false,
        'maxlength'              => 50,
        'obj'                    => $project,
        'unpersistedObj'         => $unpersistedProject,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'If you can\'t find the right genre in the selection above, you can add it here. It will be added to the sub genres list, when you click "Save".'
    ));
    */

    // mood
    $basicsFormElementsList .= getFormFieldForParams(array(
        'inputType'              => 'multiselect2',
        'propName'               => 'moods',
        'label'                  => 'Moods',
        'mandatory'              => false,
        'cssClassSuffix'         => 'chzn-select chzn-modify', // this triggers a conversion to a "chosen" select field
        'obj'                    => $project,
        'unpersistedObj'         => $unpersistedProject,
        'selectOptions'          => Mood::getSelectorOptionsArray(true),
        'objValues'              => $problemOccured ? $unpersistedProject->unpersistedProjectMoods : ProjectMood::getMoodIdsForProjectId($project->id),
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured
    ));

    /* DEPRECATED - handled by chosen.js
    // "create new mood"
    $basicsFormElementsList .= getFormFieldForParams(array(
        'propName'               => 'newMood',
        'label'                  => 'Add new mood',
        'mandatory'              => false,
        'maxlength'              => 255,
        'obj'                    => $project,
        'unpersistedObj'         => $unpersistedProject,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'If you can\'t find the right mood in the selection above, you can add it here. It will be added to the moods list, when you click "Save".'
    ));
    */
    // project attributes
    $basicsFormElementsList .= getFormFieldForParams(array(
        'inputType'              => 'multiselect2',
        'propName'               => 'attributes',
        'label'                  => 'This project needs',
        'mandatory'              => true,
        'cssClassSuffix'         => 'chzn-select chzn-modify', // this triggers a conversion to a "chosen" select field
        'obj'                    => $project,
        'unpersistedObj'         => $unpersistedProject,
        'selectOptions'          => Attribute::getIdNameMapShownFor('needs'),
        'objValues'              => $problemOccured ? $unpersistedProject->unpersistedProjectAttributes : ProjectAttribute::getAttributeIdsForProjectIdAndState($project->id, 'needs'),
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'Make a list of what\'s needed for this project to be finished. Other artists will find your project based on this information.'
    ));

    $basicsFormElementsList .= getFormFieldForParams(array(
        'inputType'              => 'textarea',
        'propName'               => 'additionalInfo',
        'label'                  => 'Additional info',
        'mandatory'              => false,
        'obj'                    => $project,
        'unpersistedObj'         => $unpersistedProject,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'Add comments, etc. about this project here.'
    ));

    // is private?
    $value            = null;
    $unpersistedValue = null;
    if ($project)            $value            = $project->visibility            == 'private' ? true : false;
    if ($unpersistedProject) $unpersistedValue = $unpersistedProject->visibility == 'private' ? true : false;
    $objValue = $problemOccured ? $unpersistedValue : $value;
    $basicsFormElementsList .= getFormFieldForParams(array(
        'inputType'              => 'checkbox',
        'propName'               => 'isPrivate',
        'objValueOverride'       => $objValue, // this is a checkbox but the value is stored as a string, thus the override
        'label'                  => 'Project is private',
        'mandatory'              => true,
        'obj'                    => $project,
        'unpersistedObj'         => $unpersistedProject,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'If you choose the project to be private, you need to manually invite other musicians on oneloudr so they can see stems and participate in your project. You can also release previous finished works using this option.'
    ));
    
    // publish form fields
    $publishFormElementsList .= getFormFieldForParams(array(
        'propName'               => 'release_title',
        'label'                  => 'Title',
        'mandatory'              => true,
        'maxlength'              => 255,
        'obj'                    => $projectFile,
        'unpersistedObj'         => $unpersistedProjectFile,
        'errorFields'            => $errorFieldsForPublish,
        'workWithUnpersistedObj' => $problemOccuredForPublish
    ));
    
    // collaborator list on publish tab
    $releaseContributionIds = ReleaseContribution::getContribProjectFileIdsForMixProjectFileId($projectFile->id);
       
    // if no release title was persisted yet then the user is here for the first time
    // (later the user can update the settings)
    $initiallyCheckCheckboxes = false;
    if (!$projectFile->release_title) { 
        $initiallyCheckCheckboxes = true;     
    }
     
    // determine which checkboxes are checked
    $checkedBoxes = array(
        'owner' => true,
        'mixer' => true
    );
    
    $allFiles = ProjectFile::fetch_all_for_project_id_and_type($project->id, 'raw', false, false);
    foreach ($allFiles as $pf) {
        $checkedBoxes[$pf->id] = $initiallyCheckCheckboxes || in_array($pf->id, $releaseContributionIds);
    }
    
    // FIXME? based on $checkedBoxes, calculation of ownership percentages can be done.
    // but it's more interesting to see this per user, not per contribution/file
        
    // start with the owner
    $contributionsList .= processTpl('Project/releaseContributionElement.html', array(
        '${pfid}'                => 'owner',
        '${checked_optional}'    => $checkedBoxes['owner'] ? ' checked="checked"' : '',
        '${disabled_optional}'   => ' disabled="disabled"',
        '${filename_optional}'   => '',           
        '${comment_optional}'    => '',           
        '${attributes}'          => 'Started by: ',
        '${name}'                => escape($project->user_name)
    ));
    
    // next is the mixer
    $comment = '';
    if ($projectFile->comment) {
        $comment = escape(strlen($projectFile->comment) > 100 ? substr($projectFile->comment, 0, 100) . '...' : $projectFile->comment) . '<br />';
    }
    
    $contributionsList .= processTpl('Project/releaseContributionElement.html', array(
        '${pfid}'                => 'mixer',
        '${checked_optional}'    => $checkedBoxes['mixer'] ? ' checked="checked"' : '',
        '${disabled_optional}'   => ' disabled="disabled"',
        '${filename_optional}'   => '',           
        '${comment_optional}'    => $comment,           
        '${attributes}'          => 'Mixed by: ',           
        '${name}'                => escape($projectFile->originator_user_id ? $projectFile->originator_user_name : $project->user_name)
    ));
    
    foreach ($allFiles as $pf) {
        $isOwner = true;
        $name    = $project->user_name;
        if ($pf->originator_user_id) {
            $isOwner = false;
            $name    = $pf->originator_user_name;
        }
        
        $attributesList = '';
        $attributes = ProjectFileAttribute::getAttributeNamesForProjectFileId($pf->id);
        if (count($attributes) > 0) $attributesList = implode(', ', $attributes) . ': ';

        $comment = '';
        if ($pf->comment) {
            $comment = escape(strlen($pf->comment) > 100 ? substr($pf->comment, 0, 100) . '...' : $pf->comment) . '<br />';
        }
        
        $contributionsList .= processTpl('Project/releaseContributionElement.html', array(
            '${pfid}'                => $pf->id,
            '${checked_optional}'    => $checkedBoxes[$pf->id] ? ' checked="checked"' : '',
            '${disabled_optional}'   => '',
            '${filename_optional}'   => escape($pf->orig_filename) . '<br />',           
            '${comment_optional}'    => $comment,           
            '${attributes}'          => $attributesList,
            '${name}'                => escape($name)
        ));
    }
}

$projectGenreList = array();
if ($project) {
    $projectGenreList = ProjectGenre::getGenreNamesForProjectId($project->id);
}

$projectMoodList = array();
if ($project) {
    $projectMoodList = ProjectMood::getMoodNamesForProjectId($project->id);
}

$projectNeedsList = array();
if ($project) {
    $projectNeedsList = ProjectAttribute::getAttributeNamesForProjectIdAndState($project->id, 'needs');
}

$projectRecommendedArtistsList = '';
if ($loggedInUser && $project->user_id == $loggedInUser->id) { // logged-in user is the project owner
    $projectRecommendedArtists = User::fetchAllThatOfferSkillsForProjectId($loggedInUser->id, $project->id);

    foreach($projectRecommendedArtists as $projectRecommendedArtist){
        //attribute list
        $recommendedArtistAttributes = implode(',', $projectRecommendedArtist->offersAttributeNamesList);

        //userimagepath
        $recommendedArtistImage = (!empty($projectRecommendedArtist->image_filename) ? $GLOBALS['BASE_URL'] . 'Content/UserImages/'.$projectRecommendedArtist->image_filename : $GLOBALS['BASE_URL'] . 'Images/testimages/profile-testimg-75x75.png' );
        $projectRecommendedArtistsList .= processTpl('Project/recommendedArtistElement.html', array(
            '${recommendedArtistId}'             => $projectRecommendedArtist->id,
            '${recommendedArtistName}'           => $projectRecommendedArtist->name,
            '${recommendedArtistAttributes}'     => $recommendedArtistAttributes,
            '${recommendedArtistProfileImage}'   => $recommendedArtistImage
        ));
    }
}

$tabBasicHtml   = '';
$tabInviteHtml  = '';
$tabPublishHtml = '';

$tabContentBasicHtml   = '';
$tabContentInviteHtml  = '';
$tabContentPublishHtml = '';
if ($loggedInUser && $project->user_id == $loggedInUser->id) { // logged-in user is the project owner
    $tabBasicHtml   = processTpl('Project/tabBasic.html', array());
    $tabInviteHtml  = processTpl('Project/tabInvite.html', array());
    
    if ($projectFile) {
        $tabPublishHtml = processTpl('Project/tabPublish.html', array());
    }

    $tabContentBasicHtml   = processTpl('Project/tabContentBasic.html', array(
        '${tabcontentAct_basics}'       => $activeTab == 'basics' ? ' tabcontentAct' : '',
        '${Common/message_choice_list}' => $generalMessageList,
        '${formAction}'                 => '', // self
        '${projectId}'                  => $project->id,
        '${Common/formElement_list}'    => $basicsFormElementsList,
        '${submitButtonValue}'          => 'Save'
    ));

    $tabContentInviteHtml  = processTpl('Project/tabContentInvite.html', array(
        '${tabcontentAct_invite}'          => $activeTab == 'invite' ? ' tabcontentAct' : '',
        '${recommendedArtistElement_list}' => $projectRecommendedArtistsList
    ));

    if ($projectFile) {
        $tweetAboutReleaseButton = '';
        $facebookShareButton = '';
        if ($projectFile->release_date && false) { // FIXME ############### deactivated because release page is not done yet
            $releaseUrl = getReleaseUrl($projectFile->id, $projectFile->release_title);
            $logger->info('release url: ' . $releaseUrl);
        
            $tweetText = 'I just released "' . $projectFile->release_title . '" from my project "' .
                         $project->title . '" at:';
            
            $tweetAboutReleaseButton = processTpl('Project/tweetAboutReleaseButton.html', array(
                '${urlEscaped}'  => str_replace('"', '\'', $releaseUrl),
                '${textEscaped}' => str_replace('"', '\'', $tweetText)
            ));
            
            $facebookShareButton = processTpl('Project/facebookShareButton.html', array(
                '${releaseUrl}'  => urlencode($releaseUrl)
            ));
        }
        
        
        $releasedTimeStr = 'now';
        if ($projectFile->release_date) {
            if (time() - strtotime($projectFile->release_date) > 0) {
                $releasedTimeStr = make_nice_duration(time() - strtotime($projectFile->release_date)) . ' ago';
            }
        }
        
        $tabContentPublishHtml = processTpl('Project/tabContentPublish.html', array(
            '${tabcontentAct_publish}'                    => $activeTab == 'publish' ? ' tabcontentAct' : '',
            '${projectName}'                              => escape($project->title),
            //'${released}'                                 => reformat_sql_date($projectFile->release_date ? $projectFile->release_date : date('Y-m-d H:i:s'), true),
            '${released}'                                 => $releasedTimeStr,
            '${Common/message_choice_list}'               => $publishMessageList,
            '${formAction}'                               => '', // self,
            '${projectId}'                                => $project->id,
            '${projectFileId}'                            => $projectFile->id,
            '${Common/formElement_list}'                  => $publishFormElementsList,
            '${Project/releaseContributionElement_list}'  => $contributionsList,
            '${Project/tweetAboutReleaseButton_optional}' => $tweetAboutReleaseButton,
            '${Project/facebookShareButton_optional}'     => $facebookShareButton,
            '${publishButtonValue}'                       => $projectFile->release_date ? 'Update' : 'Publish this song'
        ));
    }

    $uploadBackNavigation = '<a class="tab-1" href="#">&larr; back to project basic settings</a>';

} else {
    $activeTab = 'upload';
    $uploadBackNavigation = '<a href="' . $GLOBALS['BASE_URL'] . 'projectList">&larr; back to project list</a>';
}

// collaborator info
$collaboratorsHtml = '';
$collaboratorsList = ProjectUserVisibility::fetch_all_for_project_id($project->id);
$ac = count($collaboratorsList);
foreach ($collaboratorsList as $puv) {
    $collaboratorsHtml .= processTpl('Project/collaboratorIcon.html', array(
        '${artistImgUrl}' => getUserImageUri($puv->user_image_filename, 'tiny'),
        '${userId}'       => $puv->user_id,
        '${title}'        => escape($puv->user_name)
    ));
}
//echo '<a href="javascript:showSelectFriendsPopup();">Select the artists you want to have access to this project</a>' . "\n";
//echo '</div>' . "\n";

$joinProjectLink = '';
if (
    $project->visibility == 'public' && (
        !$loggedInUser     ||
        !$loggedInUser->id ||
        !projectIdIsAssociatedWithUserId($project->id, $loggedInUser->id)
    )
) {
    $joinProjectLink = processTpl('Project/joinThisProjectLink.html', array(
        '${projectId}' => $project->id
    ));
}

processAndPrintTpl('Project/index.html', array(
    '${Common/pageHeader}'                      => buildPageHeader(($projectId ? 'Edit project' : 'Create project'), true, false, true),
    '${Common/bodyHeader}'                      => buildBodyHeader($loggedInUser),
    '${Project/tabBasic_optional}'              => $tabBasicHtml,
    '${Project/tabInvite_optional}'             => $tabInviteHtml,
    '${Project/tabPublish_optional}'            => $tabPublishHtml,
    '${Project/tabContentBasic_optional}'       => $tabContentBasicHtml,
    '${Project/tabContentInvite_optional}'      => $tabContentInviteHtml,
    '${Project/tabContentPublish_optional}'     => $tabContentPublishHtml,
    '${tabcontentAct_upload}'                   => $activeTab == 'upload'  ? ' tabcontentAct' : '',
    '${tabsAct_basics}'                         => $activeTab == 'basics'  ? ' tabsAct' : '',
    '${tabsAct_invite}'                         => $activeTab == 'invite'  ? ' tabsAct' : '',
    '${tabsAct_upload}'                         => $activeTab == 'upload'  ? ' tabsAct' : '',
    '${tabsAct_publish}'                        => $activeTab == 'publish' ? ' tabsAct' : '',
    '${projectId}'                              => $project && $project->id ? $project->id : '',
    '${projectTitle}'                           => $project && $project->title ? $project->title : '(No title)',
    '${projectOwnerUserId}'                     => $project->user_id,
    '${projectOwner}'                           => escape($project->user_name),
    '${projectGenres}'                          => escape(implode(', ', $projectGenreList)),
    '${projectMoods}'                           => escape(implode(', ', $projectMoodList)),
    '${projectNeeds}'                           => escape(implode(', ', $projectNeedsList)),
    '${projectAdditionalInfo}'                  => escape($project->additionalInfo),
    '${originatorUserId}'                       => $loggedInUser ? $loggedInUser->id : '',
    '${uploaderChecksum}'                       => md5('PoopingInTheWoods' . $project->id . '_' . ($loggedInUser ? $loggedInUser->id : '')),
    '${baseUrl}'                                => $GLOBALS['BASE_URL'],
    '${Project/uploadBackNavigation}'           => $uploadBackNavigation,
    '${Project/uploadedFilesSection}'           => getUploadedFilesSection($project, $projectFilesMessageList, $loggedInUser),
    '${Project/collaboratorIcon_list}'          => $collaboratorsHtml,
    '${Project/joinThisProjectLink_optional}'   => $joinProjectLink,
    '${Common/bodyFooter}'                      => buildBodyFooter(),
    '${Common/pageFooter}'                      => buildPageFooter()
));

// END

// functions
// -----------------------------------------------------------------------------
function getUploadedFilesSection(&$project, $messageList, &$loggedInUser) {
    global $logger;

    $masterFileFoundHtml    = '';
    $masterFileNotFoundHtml = '';

    $projectFilesStemsHtml            = '';
    $projectFilesNotFoundStemsHtml    = '';
    $projectFilesReleasesHtml         = '';
    $projectFilesNotFoundReleasesHtml = '';
    $projectFilesMixesHtml            = '';
    $projectFilesNotFoundMixesHtml    = '';

    $projectFiles = ProjectFile::fetch_all_for_project_id($project->id, true);

    $logger->info(count($projectFiles) . ' project files found');

    $uploaders = array(); // cache

    $fileCount = count($projectFiles);
    for ($i = 0; $i < $fileCount; $i++) {
        $file = $projectFiles[$i];

        // skip autocreaed siblings, they are grouped with their originals
        if ($file->autocreated_from) continue;

        // find out if the next file in the list is the autocreated mp3 of this file
        $autocreatedSibling = null;
        foreach ($projectFiles as $tmpPf) {
            if ($tmpPf->autocreated_from == $file->id) {
                $autocreatedSibling = $tmpPf;
                break;
            }
        }

        // check if user has edit permissions for this file
        $loggedInUserMayEdit = false;
        if (
            userIsLoggedIn($loggedInUser) &&
            (
                $file->originator_user_id && $file->originator_user_id == $loggedInUser->id || // file was contributed by the person which is logged in OR
                $project->user_id == $loggedInUser->id                                         // owner is logged in
            )
        ) {
            $loggedInUserMayEdit = true;
        }

        // get the uploader user obj
        if ($file->originator_user_id) { // file was uploaded by collaborator
            if (!isset($uploaders[$file->originator_user_id])) { // if not in cache
                $uploaders[$file->originator_user_id] = User::fetch_for_id($file->originator_user_id);
            }
            $uploader = $uploaders[$file->originator_user_id];

        } else { // file was uploaded by project owner
            if (!isset($uploaders[$project->user_id])) { // if not in cache
                $uploaders[$project->user_id] = User::fetch_for_id($project->user_id);
            }
            $uploader = $uploaders[$project->user_id];
        }

        $uploaderUserImg = getUserImageHtml($uploader->image_filename, $uploader->name, 'tiny');

        // checkbox(es) and icon
        $checkbox = '';
        $fileIcon = '';
        if ($file->type == 'mix') {
            $fileIcon = processTpl('Project/fileIcon_mix.html', array());

        } else if ($file->type == 'release') {
            $fileIcon = processTpl('Project/fileIcon_release.html', array());

        } else { // stem
            $checkbox = processTpl('Project/projectFileElementCheckbox.html', array(
                '${id}'    => 'selectedStems',
                '${name}'  => 'selectedStems',
                '${value}' => $file->id
            ));
            $fileIcon = processTpl('Project/fileIcon_stem.html', array());
        }

        $fileAttributeNames = ProjectFileAttribute::getAttributeNamesForProjectFileId($file->id);

        $metadataBlock = '';
        $metadataForm  = '';
        if ($file->type != 'release') {
            if (
                $loggedInUserMayEdit &&                              // user has edit permissions and
                (!$file->comment || count($fileAttributeNames) == 0) // no data was entered so far
            ) {
                $selectedAttribs = ProjectFileAttribute::getAttributeIdsForProjectFileId($file->id);
                $logger->info(print_r($selectedAttribs, true));
                $attribs = Attribute::getIdNameMapShownFor('both');
                $attribList = '';
                foreach ($attribs as $attribId => $attribName) {
                    $selected = '';
                    if (in_array($attribId, $selectedAttribs)) $selected = ' selected';
                    $attribList .= '<option value="' . $attribId . '"' . $selected . '>' . escape($attribName) . '</option>' . "\n";
                }
    
                $metadataForm = processTpl('Project/projectFileMetadataForm.html', array(
                    '${formAction}'              => '', // self
                    '${projectId}'               => $project->id,
                    '${projectFileId}'           => $file->id,
                    '${commentText_optional}'    => escape($file->comment),
                    '${attributesList}'          => $attribList
                ));
    
            } else { // data already entered or no permissions to see the form
                $metadataBlock = processTpl('Project/projectFileMetadata.html', array(
                    '${comment}' => escape($file->comment),
                    '${skills}'  => escape(join(', ', $fileAttributeNames))
                ));
            }
        }

        $deleteFileLinkHtml = '';
        if ($loggedInUserMayEdit && $file->type != 'release') { // FIXME - an "unrelease" button could be useful, right?
            $deleteFileLinkHtml = processTpl('Project/deleteFileLink.html', array(
                '${projectFileId}'   => $file->id,
                '${filenameEscaped}' => escape_and_rewrite_single_quotes($file->orig_filename),
                '${projectId}'       => $project->id
            ));
        }
        
        $publishFileLinkHtml = '';
        if ($file->type == 'mix' && userIsLoggedIn($loggedInUser) && $project->user_id == $loggedInUser->id) { // owner is logged in
            $publishFileLinkHtml = processTpl('Project/publishFileLink.html', array(
                '${projectFileId}'   => $file->id,
                '${projectId}'       => $project->id,
                '${linkLabel}'       => 'Publish'
            ));
            
        } else if ($file->type == 'release' && userIsLoggedIn($loggedInUser) && $project->user_id == $loggedInUser->id) { // owner is logged in
            $publishFileLinkHtml = processTpl('Project/publishFileLink.html', array(
                '${projectFileId}'   => $file->id,
                '${projectId}'       => $project->id,
                '${linkLabel}'       => 'Update'
            ));
        }

        $fileDownloadUrl = $GLOBALS['BASE_URL'] . 'Backend/downloadFile.php?mode=download&project_id=' . $project->id . '&atfid=' . $file->id;

        $playerHtml = '';
        if (
            getFileExtension($file->filename) == 'mp3' ||
            $autocreatedSibling
        ) {
            $prelisteningUrl = $fileDownloadUrl;
            if ($autocreatedSibling) $prelisteningUrl = $GLOBALS['BASE_URL'] . 'Backend/downloadFile.php?mode=download&project_id=' . $project->id . '&atfid=' . $autocreatedSibling->id;

            $playerHtml = processTpl('Common/player.html', array(
                '${projectFileId}'   => $file->id,
                '${prelisteningUrl}' => $prelisteningUrl,
            ));

        } else if (
            getFileExtension($file->filename) == 'wav' &&
            strtotime($file->entry_date) > strtotime('2011-11-10 17:00:00') // auto-transcoding was added at this time
        ) {
            $playerHtml = '<i>(Converting to mp3 ...)</i>';
        }

        $snippet = processTpl('Project/projectFileElement.html', array(
            '${formAction}'                               => '', // self
            '${projectFileId}'                            => $file->id,
            '${projectFileElementCheckbox_optional}'      => $checkbox,
            '${fileIcon_choice}'                          => $fileIcon,
            '${filename}'                                 => escape($file->orig_filename),
            '${Project/deleteFileLink_optional}'          => $deleteFileLinkHtml,
            '${Project/publishFileLink_optional}'         => $publishFileLinkHtml,
            '${fileDownloadUrl}'                          => $fileDownloadUrl,
            '${Common/player_optional}'                   => $playerHtml,
            '${status}'                                   => $file->status == 'active' ? 'Active' : 'Inactive', // TODO - currently not used
            '${projectId}'                                => $project->id,
            '${uploadedByName}'                           => $uploader->name,
            '${uploaderUserImg}'                          => $uploaderUserImg,
            '${Project/projectFileMetadataForm_optional}' => $metadataForm,
            '${Project/projectFileMetadata_optional}'     => $metadataBlock
        ));

        if ($file->type == 'mix') {
            $projectFilesMixesHtml .= $snippet;
        } else if ($file->type == 'release') {
            $projectFilesReleasesHtml .= $snippet;
        } else {
            $projectFilesStemsHtml .= $snippet;
        }
    }

    if (!$projectFilesStemsHtml) {
        $projectFilesNotFoundStemsHtml = processTpl('Project/projectFilesNotFound.html', array());
    }
    if (!$projectFilesReleasesHtml) {
        $projectFilesNotFoundReleasesHtml = processTpl('Project/projectFilesNotFound.html', array());
    }
    if (!$projectFilesMixesHtml) {
        $projectFilesNotFoundMixesHtml = processTpl('Project/projectFilesNotFound.html', array());
    }

    $uploadStemsButtonHtml    = '';
    $uploadMixFilesButtonHtml = '';
    if (
        userIsLoggedIn($loggedInUser) && (
            $project->visibility == 'public' ||
            $project->visibility == 'private' && projectIdIsAssociatedWithUserId($project->id, $loggedInUser->id)
        )
    ) {
        $uploadStemsButtonHtml    = processTpl('Project/uploadStemsButton.html',    array());
        $uploadMixFilesButtonHtml = processTpl('Project/uploadMixFilesButton.html', array());
    }

    return processTpl('Project/uploadedFilesSection.html', array(
        '${Common/message_choice_list}'                      => $messageList,
        '${Project/projectFileElement_list_stems}'           => $projectFilesStemsHtml,
        '${Project/projectFilesNotFound_optional_stems}'     => $projectFilesNotFoundStemsHtml,
        '${Project/projectFileElement_list_releases}'        => $projectFilesReleasesHtml,
        '${Project/projectFilesNotFound_optional_releases}'  => $projectFilesNotFoundReleasesHtml,
        '${Project/projectFileElement_list_mixes}'           => $projectFilesMixesHtml,
        '${Project/projectFilesNotFound_optional_mixes}'     => $projectFilesNotFoundMixesHtml,
        '${Project/uploadStemsButton_optional}'              => $uploadStemsButtonHtml,
        '${Project/uploadMixFilesButton_optional}'           => $uploadMixFilesButtonHtml,
        '${projectId}'                                       => $project->id
    ));
}

function inputDataOk(&$errorFields, &$project) {
    global $logger;

    $result = true;

    if (strlen(get_param('title')) < 1) {
        $errorFields['title'] = 'Title is missing!';
        $result = false;
    }
    
    if (strpos(get_param('mainGenre'), 'new_') === false) { // if no new value was added
        if (!get_numeric_param('mainGenre')) {
            $errorFields['mainGenre'] = 'Please choose a main genre here!';
            $result = false;
        }
    }

    if (strpos(get_param('projectSubGenresList'), 'new_') === false) { // if no new value was added
        if (preg_match('/[^0-9,]/', get_param('projectSubGenresList'))) {
            $errorFields['subGenres'] = 'Invalid genres list'; // can only happen when someone plays around with the post data
            $result = false;
        }
    }

    if (get_numeric_param('mainGenre') && get_param('projectSubGenresList')) {
        $subGenres = explode(',', get_param('projectSubGenresList'));
        if (in_array(get_numeric_param('mainGenre'), $subGenres)) {
            $errorFields['subGenres'] = 'Please don\'t include the main genre in the sub genres.';
            $result = false;
        }
    }

    if (strpos(get_param('projectSubGenresList'), 'new_') === false) { // if no new value was added
        if (preg_match('/[^0-9,]/', get_param('projectMoodsList'))) {
            $errorFields['moods'] = 'Invalid moods list'; // can only happen when someone plays around with the post data
            $result = false;
        }
    }

    if (strlen(get_param('projectAttributesList')) < 1) {
        $errorFields['attributes'] = 'Please choose at least one element here!';
        $result = false;

    } else if(strpos(get_param('projectAttributesList'), 'new_') === false){ //no new value added
        if (preg_match('/[^0-9,]/', get_param('projectAttributesList'))){
            $errorFields['attributes'] = 'Invalid attributes list'; // can only happen when someone plays around with the post data
            $result = false;
        }
        
    }

    return $result;
}

function processParams(&$project, &$loggedInUser) {
    global $logger;

    $project->user_id                 = $loggedInUser->id;
    $project->title                   = get_param('title');
    $project->visibility              = get_numeric_param('isPrivate') ? 'private' : 'public';
    //$project->status                  = 'active';
    //$project->sorting                 = get_numeric_param('sorting');
    //$project->rating_count            = 0; // read-only field on this page
    //$project->rating_value            = 0; // read-only field on this page
    $project->additionalInfo          = get_param('additionalInfo');

// FIXME - make something similar to that! the originator_modified field was dropped
//    if ($project->type == 'remix' && $project->originating_user_id && !$project->originator_notified) {
//        $logger->info('new remix detected, sending notification mail to originator');
//
//        $originator = User::fetch_for_id($project->originating_user_id);
//        if ($originator) {
//            // send notification mail to originator
//            $email_sent = sendEmail($originator->email_address, $loggedInUser->name . ' has created a remix using one of your tracks',
//                    'Hey ' . $originator->name . ',' . "\n\n" .
//                    $loggedInUser->name . ' has just started creating a new remix using one of your tracks.' . "\n\n" .
//                    'You may want to check out the "Remixed by others" section in your oneloudr Widget or on your public user page: ' .
//                    $GLOBALS['BASE_URL'] . 'artist?aid=' . $project->originating_user_id . "\n\n" .
//                    'Please note that you might not see the new track until the remixer puts it online.');
//
//            if (!$email_sent) {
//                $logger->error('Failed to send "new remix" notification email to originator!');
//
//            } else {
//                $logger->info('marking track as "originator was modified"');
//                $project->originator_notified = true;
//            }
//
//        } else {
//            $logger->error('no originator found for user id: ' . $project->originating_user_id);
//        }
//    }

    // check if main genre is a existing genre, otherwise add it
    $mainGenreCheck = strstr(get_param('mainGenre'), 'new_');
    $mainGenre = 0;
    if($mainGenreCheck){
        $newMainGenre = new Genre();
        $newMainGenre->name = substr($mainGenreCheck,4,strlen($mainGenreCheck));
        $newMainGenre->insert();
        $mainGenre = $newMainGenre->id;
    } else {
        $mainGenre = get_param('mainGenre');
    }
    
    // create genre list and save new genre, if one was entered
    $subgenres = explode(',', get_param('projectSubGenresList'));
    $newGenreList = array();
    $projectSubGenresList = array();
    foreach($subgenres as $subgenre){
        $newCheck = strstr($subgenre, 'new_');
        if($newCheck){

            $newGenre = Genre::fetchForName($subgenre);
            if (!$newGenre || !$newGenre->id) {
                $newGenre = new Genre();
                $newGenre->name = substr($newCheck,4,strlen($newCheck));
                $newGenre->insert();
                $newGenreList[] = $newGenre->id;
            }
        }
        else {
            $projectSubGenresList[] = $subgenre;
        }
    }

    // create moods list and save new mood, if one was entered
    $moods = explode(',', get_param('projectMoodsList')); // FIXME - commas in new values make problems (fix all occurences in project.php and account.php)
    $newMoodsList = array();
    $projectMoodsList = array();
    foreach($moods as $mood){
        $newCheck = strstr($mood, 'new_');
        if($newCheck){

            $newMood = Mood::fetchForName($mood);
            if (!$newMood || !$newMood->id) {
                $newMood = new Mood();
                $newMood->name = substr($newCheck,4,strlen($newCheck));
                $newMood->insert();
                $newMoodsList[] = $newMood->id;
            }
        }
        else {
            $projectMoodsList[] = $mood;
        }
    }
    
    //create attributes list and save new attribute if one was entered
    $attributes = explode(',', get_param('projectAttributesList'));
    $newAttributesList = array();
    $projectAttributesList = array(); 
    foreach($attributes as $attribute){
        $newCheck = strstr($attribute, 'new_');
        if($newCheck){
            $newAttribute = Attribute::fetchForName($attribute);
            if(!$newAttribute || !$newAttribute->id){
                $newAttribute = new Attribute();
                $newAttribute->name = substr($newCheck,4,strlen($newCheck));
                $newAttribute->shown_for = 'both';
                $newAttribute->insert();
                $newAttributesList[] = $newAttribute->id;
            }
        }
        else {
            $projectAttributesList[] = $attribute;
        }
    }

    // handle project main & sub genres
    $projectSubGenresList = array_merge($projectSubGenresList, $newGenreList);
    $projectSubGenresList = array_unique($projectSubGenresList);

    if ($project->id) {
        ProjectGenre::deleteForProjectId($project->id); // first, delete all existing genres
        if ($mainGenre) ProjectGenre::addAll(array($mainGenre), $project->id, 1); // then save the selected main genre
        ProjectGenre::addAll($projectSubGenresList, $project->id, 0); // and the selected sub genres

    } else { // work with unpersisted obj
        $project->unpersistedProjectMainGenre = $mainGenre;
        $project->unpersistedProjectSubGenres = $projectSubGenresList;
    }

    // handle project moods
    $projectMoodsList = array_merge($projectMoodsList, $newMoodsList);
    $projectMoodsList = array_unique($projectMoodsList);

    if ($project->id) {
        ProjectMood::deleteForProjectId($project->id); // first, delete all existing moods
        ProjectMood::addAll($projectMoodsList, $project->id); // and the selected moods

    } else { // work with unpersisted obj
        $project->unpersistedProjectMoods = $projectMoodsList;
    }

    // handle project attributes
    $projectAttributesList = array_merge($projectAttributesList, $newAttributesList);
    $projectAttributesList = array_unique($projectAttributesList);
    
    if ($project->id) {
        ProjectAttribute::deleteForProjectId($project->id); // first, delete all existing attributes
        ProjectAttribute::addAll($projectAttributesList, $project->id, 'needs'); // then save the selected attributes
//        $project->containsOthers = get_param('containsOthers');
//        $project->needsOthers = get_param('needsOthers');

    } else { // work with unpersisted obj
        $project->unpersistedProjectAttributes = explode(',', get_param('projectAttributesList'));
    }
}

function inputDataOkForPublish(&$errorFieldsForPublish, &$projectFile) {
    global $logger;

    $result = true;

    if (strlen(get_param('release_title')) < 1) {
        $errorFieldsForPublish['release_title'] = 'Title is missing!';
        $result = false;
    }

    return $result;
}

function processParamsForPublish(&$projectFile, &$loggedInUser) {
    global $logger;

    $projectFile->release_title = get_param('release_title');    
    
    if ($projectFile->id) { // if persisted, we may also save the release contribution data
        ReleaseContribution::deleteForMixProjectFileId($projectFile->id);
        
        $contribProjectFileIds = array();
        foreach ($_POST as $key => $value) {
            if (
                $key != 'contribution_owner' &&
                $key != 'contribution_mixer' &&
                strpos($key, 'contribution_') === 0
            ) {
                if ($value) {
                    $contribProjectFileIds[] = substr($key, 13);
                }
            }
        }
        
        ReleaseContribution::addAll($contribProjectFileIds, $projectFile->id);
    }
}
?>
