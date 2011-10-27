<?php
include_once('../Includes/Init.php');

include_once('../Includes/FormUtil.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Attribute.php');
include_once('../Includes/DB/Genre.php');
include_once('../Includes/DB/Mood.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectAttribute.php');
include_once('../Includes/DB/ProjectFile.php');
include_once('../Includes/DB/ProjectGenre.php');
include_once('../Includes/DB/ProjectMood.php');
include_once('../Includes/DB/ProjectUserVisibility.php');
include_once('../Includes/DB/User.php');

$logger->set_debug_level();

$loggedInUser = User::new_from_cookie();
ensureUserIsLoggedIn($loggedInUser); // FIXME - open this page for non-logged in users?

$project = null;
$unpersistedProject = null;
$generalMessageList = '';
$projectFilesMessageList = '';
$problemOccured = false;
$errorFields = Array();

$projectId = get_numeric_param('pid'); // this is only set in an update scenario

$activeTab = 'basics';

if (get_param('action') == 'create') {
    $project = new Project();
    $project->user_id                   = $loggedInUser->id;
    $project->title                     = '(New project)';
    //$project->type                      = 'original';
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

} else if (get_param('action') == 'edit') { // can be called by both the project owner and collaboration artists for this project
    if (!$projectId) {
        show_fatal_error_and_exit('cannot save without a project id!');
    }

    $project = Project::fetch_for_id($projectId);
    //ensureProjectBelongsToUserId($project, $loggedInUser->id);
    ensureProjectIdIsAssociatedWithUserId($project->id, $loggedInUser->id);

} else if (get_param('action') == 'save') { // can only be called by the project owner
    $logger->info('attempting to save project data ...');
    if (!$projectId) {
        show_fatal_error_and_exit('cannot save without a project id!');
    }

    $project = Project::fetch_for_id($projectId);
    ensureProjectBelongsToUserId($project, $loggedInUser->id);

    if (inputDataOk($errorFields, $project)) {
        processParams($project, $loggedInUser);

        if ($project->status == 'newborn') {
            $project->status = 'active';
        }

        $project->save();

        // if the project is private, make sure that the owner can see it
        if ($project->visibility == 'private') {
            $atav = ProjectUserVisibility::fetch_for_user_id_project_id($loggedInUser->id, $project->id);
            if (!$atav) {
                $atav = new ProjectUserVisibility();
                $atav->user_id = $loggedInUser->id;
                $atav->project_id = $project->id;
                $atav->save();
            }
        }

        $generalMessageList .= processTpl('Common/message_success.html', array(
            '${msg}' => 'Successfully saved project data.'
        ));

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
    if (!$projectId) {
        show_fatal_error_and_exit('cannot delete without a project id!');
    }

    $project = Project::fetch_for_id($projectId);
    ensureProjectBelongsToUserId($project, $loggedInUser->id);

    Project::delete_with_id($projectId);
    ProjectAttribute::deleteForProjectId($projectId);

    header('Location: projectList.php');
    exit;

} else if (get_param('action') == 'toggleTrackState') { // not used currently - projects are always active
    $logger->info('changing project state ...');
    if (!$projectId) {
        show_fatal_error_and_exit('cannot modify project state without a project id!');
    }

    $msg = '';

    $project = Project::fetch_for_id($projectId);
    ensureProjectBelongsToUserId($project, $loggedInUser->id);

    if ($project->status == 'active') {
        $project->status = 'inactive';

    } else {
//        $masterFound = ProjectFile::master_mp3_file_found_for_project_id($project->id);
//        if ($masterFound) {
            $project->status = 'active';

//        } else {
//            $msg = 'The project status cannot be set to \'Active\' because no mix MP3 file was uploaded yet! ' .
//                   'Without a mix MP3 file the project will not be visible for other users. ' .
//                   'Please make sure the file is of high quality, at least 128kbps at 44.1kHz';
//        }
    }

    $project->save();

    header('Location: projectList.php?msg=' . urlencode($msg));
    exit;

} else if (get_param('action') == 'toggleFileState') { // not used currently - project files are always active
    $logger->info('changing project file state ...');
    if (!$projectId) {
        show_fatal_error_and_exit('cannot modify file state without a project id!');
    }

    $project = Project::fetch_for_id($projectId);
    ensureProjectBelongsToUserId($project, $loggedInUser->id);

    $file = ProjectFile::fetch_for_id(get_numeric_param('fid'));
    if (!$file) {
        show_fatal_error_and_exit('project file not found!');
    }

    ensureProjectFileBelongsToProjectId($file, $projectId);

    if ($file->status == 'active') $file->status = 'inactive';
    else $file->status = 'active';

    $file->save();

} else if (get_param('action') == 'deleteProjectFile') { // ajax action
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
    ensureProjectBelongsToUserId($project, $loggedInUser->id);

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

    ProjectFile::delete_with_id(get_numeric_param('fid'));

    $jsonReponse = array(
        'result'    => 'OK',
        'projectId' => $projectId
    );
    sendJsonResponseAndExit($jsonReponse);

} else if (get_param('action') == 'getProjectFilesHtml') {
    if (!$projectId) {
        show_fatal_error_and_exit('pid param is missing!');
    }

    $project = Project::fetch_for_id($projectId);
    //ensureProjectBelongsToUserId($project, $loggedInUser->id);
    ensureProjectIdIsAssociatedWithUserId($project->id, $loggedInUser->id);

    echo getUploadedFilesSection($project, $projectFilesMessageList);
    exit;

} else if (get_param('action') == 'downloadProjectFiles') {
    deleteOldTempFiles('zip'); // cleanup old temp zip files first

    $idListStr = get_param('fileIds');
    if ($idListStr) {
        ensureProjectIdIsAssociatedWithUserId($projectId, $loggedInUser->id);

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
    if (!$projectId) {
        show_fatal_error_and_exit('cannot save project file metadata without a project id!');
    }

    $project = Project::fetch_for_id($projectId);
    ensureProjectIdIsAssociatedWithUserId($project->id, $loggedInUser->id);

    $file = ProjectFile::fetch_for_id(get_numeric_param('fid'));
    if (!$file) {
        show_fatal_error_and_exit('project file not found for id: ' . get_numeric_param('fid'));
    }

    ensureProjectFileBelongsToProjectId($file, $projectId);

    $file->comment = get_param('comment');
    $file->save();

    $activeTab = 'upload'; // jump to the correct tab when the page was reloaded
}

// form fields
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

// currently hidden, but maybe a candidate for pro users
//$formElementsList .= getFormFieldForParams(array(
//    'inputType'              => 'select',
//    'propName'               => 'visibility',
//    'label'                  => 'Visibility',
//    'mandatory'              => true,
//    'selectOptions'          => array('public' => 'Public', 'private' => 'Private'),
//    'obj'                    => $project,
//    'unpersistedObj'         => $unpersistedProject,
//    'errorFields'            => $errorFields,
//    'workWithUnpersistedObj' => $problemOccured,
//    'infoText'               => 'If you only want certain oneloudr artist\'s to have access to your track choose PRIVATE.  If you want to make music with the world, choose; you guessed it, PUBLIC.  Your choice. You can change this at any time.'
//));

// FIXME - start
$hidden = true;

// currently hidden, but maybe a candidate for pro users
//if ($errorFields['visibility'] || ($project && $project->visibility == 'private') || ($unpersistedProject && $unpersistedProject->visibility == 'private')) $hidden = false;

echo '<div id="associated_users_row"' . ($hidden ? ' style="display:none";' : '') . '>' . "\n";
//echo '<td>Artists who have access to this project:</td>' . "\n";
//echo '<td>&nbsp;</td>' . "\n";

//$usersWithAccessListStr = '';
//$usersWithAccessList = ProjectUserVisibility::fetch_all_for_project_id($project->id);
//$ac = count($usersWithAccessList);
//if ($ac > 20) {
//    for ($ai = 0; $ai < 20; $ai++) {
//        if ($usersWithAccessList[$ai]->user_id != $loggedInUser->id) {
//            $usersWithAccessListStr .= '<a href="artist.php?aid=' . $usersWithAccessList[$ai]->user_id . '" target="_blank">' .
//                    escape($usersWithAccessList[$ai]->user_name) . '</a>, ';
//        }
//    }
//    $usersWithAccessListStr .= 'and some more ...';
//
//} else if ($ac > 1) {
//    foreach ($usersWithAccessList as $a) {
//        if ($a->user_id != $loggedInUser->id) {
//            $usersWithAccessListStr .= '<a href="artist.php?aid=' . $a->user_id . '" target="_blank">' .
//                    escape($a->user_name) . '</a>, ';
//        }
//    }
//    $usersWithAccessListStr = substr($usersWithAccessListStr, 0, -2);
//
//} else {
//    $usersWithAccessListStr = '(none)';
//}
//echo '<td>' . $usersWithAccessListStr . '<br><a href="javascript:showSelectFriendsPopup();">Select artists</a></td>' . "\n";
echo '<a href="javascript:showSelectFriendsPopup();">Select the artists you want to have access to this project</a>' . "\n";
echo '</div>' . "\n";
// FIXME - end

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

$projectRecommendedArtists = User::fetchAllThatOfferSkillsForProjectId($loggedInUser->id, $project->id);

$projectRecommendedArtistsList = null;
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

processAndPrintTpl('Project/index.html', array(
    '${Common/pageHeader}'                      => buildPageHeader(($projectId ? 'Edit project' : 'Create project'), false, false, true),
    '${Common/bodyHeader}'                      => buildBodyHeader($loggedInUser),
    '${tabcontentAct_basics}'                   => $activeTab == 'basics'  ? ' tabcontentAct' : '',
    '${tabcontentAct_invite}'                   => $activeTab == 'invite'  ? ' tabcontentAct' : '',
    '${tabcontentAct_upload}'                   => $activeTab == 'upload'  ? ' tabcontentAct' : '',
    '${tabcontentAct_publish}'                  => $activeTab == 'publish' ? ' tabcontentAct' : '',
    '${tabsAct_basics}'                         => $activeTab == 'basics'  ? ' tabsAct' : '',
    '${tabsAct_invite}'                         => $activeTab == 'invite'  ? ' tabsAct' : '',
    '${tabsAct_upload}'                         => $activeTab == 'upload'  ? ' tabsAct' : '',
    '${tabsAct_publish}'                        => $activeTab == 'publish' ? ' tabsAct' : '',
    '${headline}'                               => $projectId ? 'Edit project' : 'Create project',
    '${Common/message_choice_list}'             => $generalMessageList,
    '${formAction}'                             => $_SERVER['PHP_SELF'],
    '${projectId}'                              => $project && $project->id ? $project->id : '',
    '${projectTitle}'                           => $project && $project->title ? $project->title : '(No title)',
    '${projectGenres}'                          => escape(implode(', ', $projectGenreList)),
    '${projectMoods}'                           => escape(implode(', ', $projectMoodList)),
    '${projectNeeds}'                           => escape(implode(', ', $projectNeedsList)),
    '${projectAdditionalInfo}'                  => escape($project->additionalInfo),
    //'${type}'                                   => get_param('type') == 'remix' ? 'remix' : 'original',
    '${recommendedArtistElement_list}'          => $projectRecommendedArtistsList,
    '${uploaderChecksum}'                       => md5('PoopingInTheWoods' . $project->id),
    '${submitButtonValue}'                      => 'Save',
    '${Common/formElement_list}'                => $formElementsList,
    '${Project/uploadedFilesSection}'           => getUploadedFilesSection($project, $projectFilesMessageList),
    '${Common/bodyFooter}'                      => buildBodyFooter(),
    '${Common/pageFooter}'                      => buildPageFooter()
));

// END

// functions
// -----------------------------------------------------------------------------
function getUploadedFilesSection(&$project, $messageList) {
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

    foreach ($projectFiles as $file) {
        $uploaderUserImg = getUserImageHtml($file->userImageFilename, $file->userName, 'tiny');

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

        $metadataBlock = '';
        $metadataForm  = '';
        if ($file->comment) {
            $metadataBlock = processTpl('Project/projectFileMetadata.html', array(
                '${comment}' => escape($file->comment)
            ));

        } else {
            $metadataForm = processTpl('Project/projectFileMetadataForm.html', array(
                '${formAction}'    => $_SERVER['PHP_SELF'],
                '${projectId}'     => $project->id,
                '${projectFileId}' => $file->id,
                '${comment}'       => escape($file->comment)
            ));
        }

        $snippet = processTpl('Project/projectFileElement.html', array(
            '${formAction}'                               => $_SERVER['PHP_SELF'],
            '${projectFileId}'                            => $file->id,
            '${projectFileElementCheckbox_optional}'      => $checkbox,
            '${fileIcon_choice}'                          => $fileIcon,
            '${filename}'                                 => escape($file->orig_filename),
            '${filenameEscaped}'                          => escape_and_rewrite_single_quotes($file->orig_filename),
            '${fileDownloadUrl}'                          => '../Backend/downloadFile.php?mode=download&project_id=' . $project->id . '&atfid=' . $file->id,
            '${status}'                                   => $file->status == 'active' ? 'Active' : 'Inactive', // TODO - currently not used
            '${projectId}'                                => $project->id,
            '${projectFileId}'                            => $file->id,
            '${uploadedByName}'                           => $file->userName,
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

    return processTpl('Project/uploadedFilesSection.html', array(
        '${Common/message_choice_list}'                      => $messageList,
        '${Project/projectFileElement_list_stems}'           => $projectFilesStemsHtml,
        '${Project/projectFilesNotFound_optional_stems}'     => $projectFilesNotFoundStemsHtml,
        '${Project/projectFileElement_list_releases}'        => $projectFilesReleasesHtml,
        '${Project/projectFilesNotFound_optional_releases}'  => $projectFilesNotFoundReleasesHtml,
        '${Project/projectFileElement_list_mixes}'           => $projectFilesMixesHtml,
        '${Project/projectFilesNotFound_optional_mixes}'     => $projectFilesNotFoundMixesHtml,
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
    /*
    if (preg_match('/[^0-9,]/', get_param('projectSubGenresList'))) {
        $errorFields['subGenres'] = 'Invalid genres list'; // can only happen when someone plays around with the post data
        $result = false;
    }
    */

    if (get_numeric_param('mainGenre') && get_param('projectSubGenresList')) {
        $subGenres = explode(',', get_param('projectSubGenresList'));
        if (in_array(get_numeric_param('mainGenre'), $subGenres)) {
            $errorFields['subGenres'] = 'Please don\'t include the main genre in the sub genres.';
            $result = false;
        }
    }

    /*
    if (preg_match('/[^0-9,]/', get_param('projectMoodsList'))) {
        $errorFields['moods'] = 'Invalid moods list'; // can only happen when someone plays around with the post data
        $result = false;
    }
    */

// currently hidden, but maybe a candidate for pro users
//    if (strlen(get_param('visibility')) < 1) {
//        $errorFields['visibility'] = 'Visibility is missing!';
//        $result = false;
//
//    } else if (get_param('visibility') != 'private' && get_param('visibility') != 'public') {
//        $errorFields['visibility'] = 'Visibility is invalid!';
//        $result = false;
//    }

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
    //$project->type                    = get_param('type') == 'remix' ? 'remix' : 'original'; // this is a hidden field, popuplated with a url param
    //$project->visibility              = get_param('visibility'); // currently hidden, but maybe a candidate for pro users
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
    $moods = explode(',', get_param('projectMoodsList'));
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
    /*
    $newGenre = null;
    if (get_param('newGenre')) {
        $newGenre = Genre::fetchForName(get_param('newGenre'));
        if (!$newGenre || !$newGenre->id) {
            $newGenre = new Genre();
            $newGenre->name = get_param('newGenre');
            $newGenre->insert();
        }
    }

    // save new mood, if one was entered
    $newMood = null;
    if (get_param('newMood')) {
        $newMood = Mood::fetchForName(get_param('newMood'));
        if (!$newMood || !$newMood->id) {
            $newMood = new Mood();
            $newMood->name = get_param('newMood');
            $newMood->insert();
        }
    }
    */
    // handle project main & sub genres
    //$projectSubGenresList = explode(',', get_param('projectSubGenresList'));
    //if ($newGenre) $projectSubGenresList[] = $newGenre->id;
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
    //$projectMoodsList = explode(',', get_param('projectMoodsList'));
    //if ($newMood) $projectMoodsList[] = $newMood->id;
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