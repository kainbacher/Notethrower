<?php

include_once('../Includes/Init.php');

include_once('../Includes/FormUtil.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Attribute.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectAttribute.php');
include_once('../Includes/DB/ProjectFile.php');
include_once('../Includes/DB/ProjectUserVisibility.php');
include_once('../Includes/DB/User.php');

// FIXME - use this in upload form:
// <input type="hidden" name="MAX_FILE_SIZE" value="524288001">

$user = User::new_from_cookie();
ensureUserIsLoggedIn($user);

$project = null;
$unpersistedProject = null;
$generalMessageList = '';
$projectFilesMessageList = '';
$problemOccured = false;
$errorFields = Array();

$projectId = get_numeric_param('pid'); // this is only set in an update scenario

if (get_param('action') == 'create') {
    $project = new Project();
    $project->user_id                   = $user->id;
    $project->title                     = '(New project)';
    $project->type                      = 'original';
    $project->originating_user_id       = null;
    $project->price                     = 0;
    $project->currency                  = 'USD'; // TODO - take from config - check other occurences as well
    $project->genres                    = '';
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
    $atav->user_id = $user->id;
    $atav->project_id = $project->id;
    $atav->save();

} else if (get_param('action') == 'edit') {
    if (!$projectId) {
        show_fatal_error_and_exit('cannot save without a project id!');
    }

    $project = Project::fetch_for_id($projectId);
    ensureProjectBelongsToUserId($project, $user->id);

} else if (get_param('action') == 'save') {
    $logger->info('attempting to save project data ...');
    if (!$projectId) {
        show_fatal_error_and_exit('cannot save without a project id!');
    }

    $project = Project::fetch_for_id($projectId);
    ensureProjectBelongsToUserId($project, $user->id);

    if (inputDataOk($errorFields, $project)) {
        processParams($project, $user);

        if ($project->status == 'newborn') {
            $project->status = 'active';
        }

        $project->save();

        // if the project is private, make sure that the owner can see it
        if ($project->visibility == 'private') {
            $atav = ProjectUserVisibility::fetch_for_user_id_project_id($user->id, $project->id);
            if (!$atav) {
                $atav = new ProjectUserVisibility();
                $atav->user_id = $user->id;
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
        $unpersistedProject->id = $projectId;
        processParams($unpersistedProject, $user);
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
    ensureProjectBelongsToUserId($project, $user->id);

    Project::delete_with_id($projectId);
    ProjectAttribute::deleteForProjectId($projectId);

    header('Location: projectList.php');
    exit;

} else if (get_param('action') == 'toggleTrackState') { // not used currently - projects are always active as long as at least the mp3 version was uploaded, otherwise they are inactive until this is done
    $logger->info('changing project state ...');
    if (!$projectId) {
        show_fatal_error_and_exit('cannot modify project state without a project id!');
    }

    $msg = '';

    $project = Project::fetch_for_id($projectId);
    ensureProjectBelongsToUserId($project, $user->id);

    if ($project->status == 'active') {
        $project->status = 'inactive';

    } else {
        $masterFound = ProjectFile::master_mp3_file_found_for_project_id($project->id);
        if ($masterFound) {
            $project->status = 'active';

        } else {
            $msg = 'The project status cannot be set to \'Active\' because no mix MP3 file was uploaded yet! ' .
                   'Without a mix MP3 file the project will not be visible for other users. ' .
                   'Please make sure the file is of high quality, at least 128kbps at 44.1kHz';
        }
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
    ensureProjectBelongsToUserId($project, $user->id);

    $file = ProjectFile::fetch_for_id(get_numeric_param('fid'));
    if (!$file) {
        show_fatal_error_and_exit('project file not found!');
    }

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
    ensureProjectBelongsToUserId($project, $user->id);

    $file = ProjectFile::fetch_for_id(get_numeric_param('fid'));
    if (!$file) {
        //show_fatal_error_and_exit('project file not found!');
        $jsonReponse = array(
            'result' => 'ERROR',
            'error'  => 'project file not found'
        );
        sendJsonResponseAndExit($jsonReponse);
    }

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
    ensureProjectBelongsToUserId($project, $user->id);

    echo getUploadedFilesSection($project, $projectFilesMessageList);
    exit;
}

$userSelectionArray = array();
$userSelectionArray[0] = '- Please choose -';
$users = User::fetch_all_from_to(0, 999999999, false, false);
foreach ($users as $a) {
    if ($a->name != $user->name) {
        $userSelectionArray[$a->id] = $a->name;
    }
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

$hidden = true;
if ($errorFields['originating_user_id'] || ($project && $project->type == 'remix') || ($unpersistedProject && $unpersistedProject->type == 'remix')) $hidden = false;

$formElementsList .= getFormFieldForParams(array(
    'inputType'              => 'select',
    'propName'               => 'originating_user_id',
    'label'                  => 'Originating artist',
    'mandatory'              => false,
    'selectOptions'          => $userSelectionArray,
    'obj'                    => $project,
    'unpersistedObj'         => $unpersistedProject,
    'errorFields'            => $errorFields,
    'workWithUnpersistedObj' => $problemOccured,
    'hide'                   => $hidden
));

$formElementsList .= getFormFieldForParams(array(
    'propName'               => 'price',
    'label'                  => 'Price for commercial license',
    'mandatory'              => false,
    'maxlength'              => 255,
    'obj'                    => $project,
    'unpersistedObj'         => $unpersistedProject,
    'errorFields'            => $errorFields,
    'workWithUnpersistedObj' => $problemOccured,
    'inputFieldSuffix'       => 'USD', // FIXME - make constant?
    'infoText'               => 'Please enter the price you want others to pay to license your work.  Notethrower will take a 10% fee from the sale at this price.  If you are uploading a remix of another Notethrower artist\'s track, you will split the profit with that artist 50/50, minus the 10% fee.'
));

$formElementsList .= getFormFieldForParams(array(
    'inputType'              => 'select',
    'propName'               => 'genres',
    'label'                  => 'Genre',
    'mandatory'              => true,
    'selectOptions'          => array_merge(array('' => '- Please choose -'), $GLOBALS['GENRES']),
    'obj'                    => $project,
    'unpersistedObj'         => $unpersistedProject,
    'errorFields'            => $errorFields,
    'workWithUnpersistedObj' => $problemOccured
));

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
    'objValues'              => ProjectAttribute::fetchAttributeIdsForProjectIdAndState($project->id, 'needs'),
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
//    'infoText'               => 'If you only want certain Notethrower artist\'s to have access to your track choose PRIVATE.  If you want to make music with the world, choose; you guessed it, PUBLIC.  Your choice. You can change this at any time.'
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
//        if ($usersWithAccessList[$ai]->user_id != $user->id) {
//            $usersWithAccessListStr .= '<a href="userInfo.php?aid=' . $usersWithAccessList[$ai]->user_id . '" target="_blank">' .
//                    escape($usersWithAccessList[$ai]->user_name) . '</a>, ';
//        }
//    }
//    $usersWithAccessListStr .= 'and some more ...';
//
//} else if ($ac > 1) {
//    foreach ($usersWithAccessList as $a) {
//        if ($a->user_id != $user->id) {
//            $usersWithAccessListStr .= '<a href="userInfo.php?aid=' . $a->user_id . '" target="_blank">' .
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

processAndPrintTpl('Project/index.html', array(
    '${Common/pageHeader}'                      => buildPageHeader(($projectId ? 'Edit project' : 'Create project'), false, false, true),
    '${Common/bodyHeader}'                      => buildBodyHeader($user),
    '${headline}'                               => $projectId ? 'Edit project' : 'Create project',
    '${Common/message_choice_list}'             => $generalMessageList,
    '${formAction}'                             => $_SERVER['PHP_SELF'],
    '${projectId}'                              => $project && $project->id ? $project->id : '',
    '${type}'                                   => get_param('type') == 'remix' ? 'remix' : 'original',
    '${submitButtonValue}'                      => 'Save',
    '${Common/formElement_list}'                => $formElementsList,
    '${Project/uploadedFilesSection}'           => getUploadedFilesSection($project, $projectFilesMessageList),
    '${Common/bodyFooter}'                      => buildBodyFooter(),
    '${Common/pageFooter}'                      => buildPageFooter()
));

exit;

// END

// functions
// -----------------------------------------------------------------------------
function getUploadedFilesSection(&$project, $messageList) {
    global $logger;

    $masterFound = ProjectFile::master_mp3_file_found_for_project_id($project->id);
    if ($masterFound) {
        $project->status = 'active';
        $project->save();

    } else {
        $project->status = 'inactive';
        $project->save();
        $messageList .= processTpl('Common/message_notice.html', array(
            '${msg}' => 'Please upload a mix MP3 file to make sure your project is activated.<br />' .
                        'Without a mix MP3 file the project will not be visible for other users.<br />' .
                        'Please make sure the file is of high quality, at least 128kbps at 44.1kHz'
        ));
    }

    $masterFileFoundHtml    = '';
    $masterFileNotFoundHtml = '';

    $projectFilesHtml         = '';
    $projectFilesNotFoundHtml = '';

    $projectFiles = ProjectFile::fetch_all_for_project_id($project->id, true);

    $logger->info(count($projectFiles) . ' project files found');

    foreach ($projectFiles as $file) {
        $uploaderUserImg = getUserImageHtml($file->userImageFilename, $file->userName, 'tiny');

        $mixMp3OrRawFileIcon = '';
        if ($file->is_master && strpos($file->orig_filename, '.mp3') !== false) {
            $mixMp3OrRawFileIcon = processTpl('Project/mixMp3FileIcon.html', array());
        } else {
            $mixMp3OrRawFileIcon = processTpl('Project/rawFileIcon.html', array());
        }

        $projectFilesHtml .= processTpl('Project/projectFileElement.html', array(
            '${mixMp3OrRawFileIcon}'  => $mixMp3OrRawFileIcon,
            '${filename}'             => escape($file->orig_filename),
            '${filenameEscaped}'      => escape_and_rewrite_single_quotes($file->orig_filename),
            '${fileDownloadUrl}'      => '../Backend/downloadFile.php?mode=download&project_id=' . $project->id . '&atfid=' . $file->id,
            '${status}'               => $file->status == 'active' ? 'Active' : 'Inactive', // TODO - currently not used
            '${projectId}'            => $project->id,
            '${projectFileId}'        => $file->id,
            '${uploadedByName}'       => $file->userName,
            '${uploaderUserImg}'      => $uploaderUserImg
        ));

        // FIXME - download links absichern, damit man nicht beliebige files von beliebigen projekten runterladen kann? ist das überhaupt nötig?
    }

    if (count($projectFiles) == 0) {
        $projectFilesNotFoundHtml = processTpl('Project/projectFilesNotFound.html', array());
    }

    return processTpl('Project/uploadedFilesSection.html', array(
        '${Common/message_choice_list}'             => $messageList,
        '${Project/projectFileElement_list}'        => $projectFilesHtml,
        '${Project/projectFilesNotFound_optional}'  => $projectFilesNotFoundHtml,
        '${projectId}'                              => $project->id
    ));
}

function inputDataOk(&$errorFields, &$project) {
    global $logger;

    $result = true;

    if (strlen(get_param('title')) < 1) {
        $errorFields['title'] = 'Title is missing!';
        $result = false;
    }

    if (get_param('type') != 'original') {
        if (!get_numeric_param('originating_user_id')) {
            $errorFields['originating_user_id'] = 'Originating user is missing!';
            $result = false;

        } else {
            $checkUser = User::fetch_for_id(get_numeric_param('originating_user_id'));
            if (!$checkUser) {
                $errorFields['originating_user_id'] = 'Originating user is invalid!';
                $result = false;
            }
        }
    }

    if ($price = get_numeric_param('price')) {
        if ($price < 0) {
            $errorFields['price'] = 'Price is invalid!';
            $result = false;
        }
    }

    if (strlen(get_param('genres')) < 1) {
        $errorFields['genres'] = 'Genre is missing!';
        $result = false;
    }

    // FIXME - check genres for validity

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

function processParams(&$project, &$user) {
    global $logger;

    $project->user_id                 = $user->id;
    $project->title                   = get_param('title');
    $project->type                    = get_param('type') == 'remix' ? 'remix' : 'original'; // this is a hidden field, popuplated with a url param
    $project->originating_user_id     = get_numeric_param('originating_user_id');
    $project->price                   = get_numeric_param('price');
    $project->genres                  = get_param('genres');
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
//            $email_sent = send_email($originator->email_address, $user->name . ' has created a remix using one of your tracks',
//                    'Hey ' . $originator->name . ',' . "\n\n" .
//                    $user->name . ' has just started creating a new remix using one of your tracks.' . "\n\n" .
//                    'You may want to check out the "Remixed by others" section in your Notethrower Widget or on your public user page: ' .
//                    $GLOBALS['BASE_URL'] . 'Site/userInfo.php?aid=' . $project->originating_user_id . "\n\n" .
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

    // handle project attributes
    if (!is_null($project->id)) {
        ProjectAttribute::deleteForProjectId($project->id); // first, delete all existing attributes
        ProjectAttribute::addAll(explode(',', get_param('projectAttributesList')), $project->id, 'needs'); // then save the selected attributes
//        $project->containsOthers = get_param('containsOthers');
//        $project->needsOthers = get_param('needsOthers');
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