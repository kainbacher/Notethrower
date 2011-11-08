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
include_once('../Includes/DB/User.php');

// some notes on permissions:
// + a private project can only be seen and modified by invited (=associated) users
// + everyone which is logged in can upload files to a public project
// + everyone which is not logged in can view public projects and download files

$logger->set_info_level();

$loggedInUser = User::new_from_cookie();

$project = null;
$unpersistedProject = null;
$generalMessageList = '';
$projectFilesMessageList = '';
$problemOccured = false;
$errorFields = Array();

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

} else if (get_param('action') == 'save') { // can only be called by the project owner
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

    deleteOldTempFiles('zip'); // cleanup old temp zip files first

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
}

// form fields
$formElementsList == '';
if ($project->user_id == $loggedInUser->id) { // logged-in user is the project owner
    $formElementsList .= getFormFieldForParams(array(
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
    $formElementsList .= getFormFieldForParams(array(
        'inputType'              => 'select2',
        'propName'               => 'mainGenre',
        'label'                  => 'Main genre',
        'mandatory'              => true,
        'cssClassSuffix'         => 'chzn-select', // this triggers a conversion to a "chosen" select field
        'obj'                    => $project,
        'unpersistedObj'         => $unpersistedProject,
        'selectOptions'          => Genre::getSelectorOptionsArray(true),
        'objValue'               => $problemOccured ? $unpersistedProject->unpersistedProjectMainGenre : ProjectGenre::getMainGenreIdForProjectId($project->id),
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured
    ));

    // sub genres
    $formElementsList .= getFormFieldForParams(array(
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
    $formElementsList .= getFormFieldForParams(array(
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
    $formElementsList .= getFormFieldForParams(array(
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
    $formElementsList .= getFormFieldForParams(array(
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
    $formElementsList .= getFormFieldForParams(array(
        'inputType'              => 'multiselect2',
        'propName'               => 'attributes',
        'label'                  => 'This project needs',
        'mandatory'              => true,
        'cssClassSuffix'         => 'chzn-select', // this triggers a conversion to a "chosen" select field
        'obj'                    => $project,
        'unpersistedObj'         => $unpersistedProject,
        'selectOptions'          => Attribute::getIdNameMapShownFor('needs'),
        'objValues'              => $problemOccured ? $unpersistedProject->unpersistedProjectAttributes : ProjectAttribute::getAttributeIdsForProjectIdAndState($project->id, 'needs'),
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'Make a list of what\'s needed for this project to be finished. Other artists will find your project based on this information.'
    ));

    $formElementsList .= getFormFieldForParams(array(
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
    $formElementsList .= getFormFieldForParams(array(
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
if ($project->user_id == $loggedInUser->id) { // logged-in user is the project owner
    $projectRecommendedArtists = User::fetchAllThatOfferSkillsForProjectId($loggedInUser->id, $project->id);

    foreach($projectRecommendedArtists as $projectRecommendedArtist){
        //attribute list
        $recommendedArtistAttributes = implode(',', $projectRecommendedArtist->offersAttributeNamesList);

        //userimagepath
        $recommendedArtistImage = (!empty($projectRecommendedArtist->image_filename) ? '../Content/UserImages/'.$projectRecommendedArtist->image_filename : '../Images/testimages/profile-testimg-75x75.png' );
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
if ($project->user_id == $loggedInUser->id) { // logged-in user is the project owner
    $tabBasicHtml   = processTpl('Project/tabBasic.html', array());
    $tabInviteHtml  = processTpl('Project/tabInvite.html', array());
    $tabPublishHtml = processTpl('Project/tabPublish.html', array());

    $tabContentBasicHtml   = processTpl('Project/tabContentBasic.html', array(
        '${tabcontentAct_basics}'       => $activeTab == 'basics'  ? ' tabcontentAct' : '',
        '${Common/message_choice_list}' => $generalMessageList,
        '${formAction}'                 => $_SERVER['PHP_SELF'],
        '${projectId}'                  => $project->id,
        '${Common/formElement_list}'    => $formElementsList,
        '${submitButtonValue}'          => 'Save'
    ));

    $tabContentInviteHtml  = processTpl('Project/tabContentInvite.html', array(
        '${tabcontentAct_invite}'          => $activeTab == 'invite'  ? ' tabcontentAct' : '',
        '${recommendedArtistElement_list}' => $projectRecommendedArtistsList
    ));

    $tabContentPublishHtml = processTpl('Project/tabContentPublish.html', array(
        '${tabcontentAct_publish}' => $activeTab == 'publish' ? ' tabcontentAct' : '',
        '${submitButtonValue}'     => 'Save'
    ));

    $uploadBackNavigation = '<a class="tab-1" href="#">&larr; back to project basic settings</a>';

} else {
    $activeTab = 'upload';
    $uploadBackNavigation = '<a href="projectList.php">&larr; back to project list</a>';
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
        !$loggedInUser ||
        !projectIdIsAssociatedWithUserId($project->id, $loggedInUser->id)
    )
) {
    $joinProjectLink = processTpl('Project/joinThisProjectLink.html', array(
        '${projectId}' => $project->id
    ));
}

processAndPrintTpl('Project/index.html', array(
    '${Common/pageHeader}'                      => buildPageHeader(($projectId ? 'Edit project' : 'Create project'), false, false, true),
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
    '${originatorUserId}'                       => $loggedInUser->id,
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

//    $masterFound = ProjectFile::master_mp3_file_found_for_project_id($project->id);
//    if ($masterFound) {
//        if ($project->status == 'inactive') $project->status = 'active';
//        $project->save();
//
//    } else {
//        if ($project->status == 'active') $project->status = 'inactive';
//        $project->save();
//        $messageList .= processTpl('Common/message_notice.html', array(
//            '${msg}' => 'Please upload a mix MP3 file to make sure your project is activated.<br />' .
//                        'Without a mix MP3 file the project will not be visible for other users.<br />' .
//                        'Please make sure the file is of high quality, at least 128kbps at 44.1kHz'
//        ));
//    }

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

    foreach ($projectFiles as $file) {
        // check if user has edit permissions for this file
        $loggedInUserMayEdit = false;
        if (
            userIsLoggedIn($loggedInUser) &&
            $file->originator_user_id && $file->originator_user_id == $loggedInUser->id || // file was contributed by the person which is logged in OR
            $project->user_id == $loggedInUser->id                                         // owner is logged in
        ) {
            $loggedInUserMayEdit = true;
        }

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
        if (
            $loggedInUserMayEdit &&                              // user has edit permissions and
            (!$file->comment || count($fileAttributeNames) == 0) // no data was entered so far
        ) {
            $pfFormElementsList = getFormFieldForParams(array(
                'inputType'                => 'textarea',
                'propName'                 => 'comment',
                'label'                    => 'Comment',
                'maxlength'                => 500,
                'customStyleForInputField' => 'height:60px;', // FIXME - should not be needed
                'mandatory'                => true,
                'obj'                      => $file,
                'unpersistedObj'           => null,
                'errorFields'              => array(),
                'workWithUnpersistedObj'   => false,
                'infoText'                 => 'Add a comment about the file here.'
            ));

            $pfFormElementsList .= getFormFieldForParams(array(
                'inputType'              => 'multiselect2',
                'propName'               => 'fileAttributes_' . $file->id,
                'label'                  => 'Instrument/Skills',
                'mandatory'              => true,
                'cssClassSuffix'         => 'chzn-select', // this triggers a conversion to a "chosen" select field
                'obj'                    => $file,
                'unpersistedObj'         => null,
                'selectOptions'          => Attribute::getIdNameMapShownFor('both'),
                'objValues'              => ProjectFileAttribute::getAttributeIdsForProjectFileId($file->id),
                'errorFields'            => array(),
                'workWithUnpersistedObj' => false,
                'infoText'               => 'List here what this file consists of. Eg. "Bass" for a bass track.'
            ));

            $metadataForm = processTpl('Project/projectFileMetadataForm.html', array(
                '${formAction}'              => $_SERVER['PHP_SELF'],
                '${projectId}'               => $project->id,
                '${projectFileId}'           => $file->id,
                '${Common/formElement_list}' => $pfFormElementsList,
            ));

        } else { // data already entered or no permissions to see the form
            $metadataBlock = processTpl('Project/projectFileMetadata.html', array(
                '${comment}' => escape($file->comment),
                '${skills}'  => escape(join(', ', $fileAttributeNames))
            ));
        }

        $deleteFileLinkHtml = '';
        if ($loggedInUserMayEdit) {
            $deleteFileLinkHtml = processTpl('Project/deleteFileLink.html', array(
                '${projectFileId}'   => $file->id,
                '${filenameEscaped}' => escape_and_rewrite_single_quotes($file->orig_filename),
                '${projectId}'       => $project->id
            ));
        }

        $snippet = processTpl('Project/projectFileElement.html', array(
            '${formAction}'                               => $_SERVER['PHP_SELF'],
            '${projectFileId}'                            => $file->id,
            '${projectFileElementCheckbox_optional}'      => $checkbox,
            '${fileIcon_choice}'                          => $fileIcon,
            '${filename}'                                 => escape($file->orig_filename),
            '${Project/deleteFileLink_optional}'          => $deleteFileLinkHtml,
            '${fileDownloadUrl}'                          => '../Backend/downloadFile.php?mode=download&project_id=' . $project->id . '&atfid=' . $file->id,
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

    if (!get_numeric_param('mainGenre')) {
        $errorFields['mainGenre'] = 'Please choose a main genre here!';
        $result = false;
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

    } else if (preg_match('/[^0-9,]/', get_param('projectAttributesList'))) {
        $errorFields['attributes'] = 'Invalid attributes list'; // can only happen when someone plays around with the post data
        $result = false;
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
//            $email_sent = send_email($originator->email_address, $loggedInUser->name . ' has created a remix using one of your tracks',
//                    'Hey ' . $originator->name . ',' . "\n\n" .
//                    $loggedInUser->name . ' has just started creating a new remix using one of your tracks.' . "\n\n" .
//                    'You may want to check out the "Remixed by others" section in your oneloudr Widget or on your public user page: ' .
//                    $GLOBALS['BASE_URL'] . 'Site/artist.php?aid=' . $project->originating_user_id . "\n\n" .
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

    // handle project main & sub genres
    $projectSubGenresList = array_merge($projectSubGenresList, $newGenreList);
    $projectSubGenresList = array_unique($projectSubGenresList);

    if ($project->id) {
        ProjectGenre::deleteForProjectId($project->id); // first, delete all existing genres
        if (get_numeric_param('mainGenre')) ProjectGenre::addAll(array(get_numeric_param('mainGenre')), $project->id, 1); // then save the selected main genre
        ProjectGenre::addAll($projectSubGenresList, $project->id, 0); // and the selected sub genres

    } else { // work with unpersisted obj
        $project->unpersistedProjectMainGenre = get_numeric_param('mainGenre');
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
    if ($project->id) {
        ProjectAttribute::deleteForProjectId($project->id); // first, delete all existing attributes
        ProjectAttribute::addAll(explode(',', get_param('projectAttributesList')), $project->id, 'needs'); // then save the selected attributes
//        $project->containsOthers = get_param('containsOthers');
//        $project->needsOthers = get_param('needsOthers');

    } else { // work with unpersisted obj
        $project->unpersistedProjectAttributes = explode(',', get_param('projectAttributesList'));
    }
}

// currently hidden, but maybe a candidate for pro users
//function showVisibilityField($label, $inputType, $propName, $helpTextHtml, $mandatory, $maxlength, &$project, &$unpersistedProject, $problemOccured, &$errorFields, $selectOptions) {
//    global $logger;
//
//    $classSuffix = isset($errorFields[$propName]) ? ' formFieldWithProblem' : '';
//
//    echo '<tr class="standardRow1' . $classSuffix . '"' .
//         ' onmouseover="this.className=\'highlightedRow' . $classSuffix . '\';" onmouseout="this.className=\'standardRow1' . $classSuffix . '\';"' .
//         '>' . "\n";
//
//    echo '<td class="formLeftCol">' . ($mandatory ? '<b>' : '') . $label . ':' . ($mandatory ? '</b>' : '') . '</td>' . "\n";
//
//    echo '<td class="formMiddleCol ' . ($mandatory ? 'mandatoryFormField' : 'optionalFormField') . '">';
//
//    $normalClass  = 'inputTextField';
//    $problemClass = 'inputTextFieldWithProblem';
//    $size = 40;
//
//    $projectVal            = null;
//    $unpersistedProjectVal = null;
//    eval('if ($project) $projectVal = $project->' . $propName . ';');
//    eval('if ($unpersistedProject) $unpersistedProjectVal = $unpersistedProject->' . $propName . ';');
//    $selectedValue = $unpersistedProject ? $unpersistedProjectVal : $projectVal;
//
//    echo '<select onChange="visibilityHasChanged(this);" class="' . (isset($errorFields[$propName]) ? $problemClass : $normalClass) . '" name="' . $propName . '">' . "\n";
//    foreach (array_keys($selectOptions) as $optVal) {
//        //$logger->debug('#### ' . $selectedValue  . ' === ' . $optVal);
//        $selected = ((string) $selectedValue === (string) $optVal) ? ' selected' : '';
//        echo '<option value="' . $optVal . '"' . $selected . '>' . escape($selectOptions[$optVal]) . '</option>' . "\n";
//    }
//    echo '</select>' . "\n";
//
//    echo '</td>' . "\n";
//
//    echo '<td style="vertical-align:top">';
//    if ($helpTextHtml) {
//        echo '<span style="font-size:11px">' . $helpTextHtml . '</span>';
//    } else {
//        echo '&nbsp;';
//    }
//    echo '</td>';
//
//    if (isset($errorFields[$propName])) {
//        echo '<tr class="formFieldWithProblem"><td colspan="3">';
//        echo $errorFields[$propName];
//        echo '<br><br></td></tr>';
//    }
//
//    echo '</tr>' . "\n";
//}

?>