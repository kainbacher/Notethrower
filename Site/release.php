<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');
require_once('../Includes/mobile_device_detect.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectFile.php');
include_once('../Includes/DB/User.php');

// find out if the user browses with a mobile device
$showMobileVersion = false;
$isMobileDevice = mobile_device_detect(true,false,true,true,true,true,true,false,false);
if ($isMobileDevice || get_param('_forceMobile')) {
    $showMobileVersion = true;
}

// let's see if the visiting user is a logged in user
$visitorUserId = -1;
$visitorUser = User::new_from_cookie();
if ($visitorUser) {
    $visitorUserId = $visitorUser->id;
    $logger->info('visitor user id: ' . $visitorUserId);
}

$pfid = get_numeric_param('pfid');

$projectFile = ProjectFile::fetch_for_id($pfid);
if (!$projectFile || $projectFile->status != 'active') {
    show_fatal_error_and_exit('no (active) project file found for id: ' . $pfid);
}

if ($projectFile->type != 'release') {
    show_fatal_error_and_exit('project file is not a release');
}

$project = Project::fetch_for_id($projectFile->project_id);
if (!$project || $project->status != 'active') {
    show_fatal_error_and_exit('no (active) project found for id: ' . $projectFile->project_id);
}

$projectFiles = ProjectFile::fetch_all_for_project_id($project->id);

$autocreatedSibling = null;
foreach ($projectFiles as $tmpPf) {
    if ($tmpPf->autocreated_from == $projectFile->id) {
        $autocreatedSibling = $tmpPf;
        break;
    }
}

// user image
$projectOwnerImgUrl = getUserImageUri($project->user_img_filename, 'regular');

$fileDownloadUrl = '../Backend/downloadFile.php?mode=download&project_id=' . $project->id . '&atfid=' . $projectFile->id;
$prelistenUrl = $fileDownloadUrl;
if ($autocreatedSibling) {
    $prelistenUrl = '../Backend/downloadFile.php?mode=download&project_id=' . $project->id . '&atfid=' . $autocreatedSibling->id;
}

processAndPrintTpl('Release/index.html', array(
    '${Common/pageHeader}'  => buildPageHeader('Release', false, false, false, $showMobileVersion),
    '${Common/bodyHeader}'  => buildBodyHeader($visitorUser, $showMobileVersion),
    '${releaseTitle}'       => escape($projectFile->release_title),
    '${fileDownloadUrl}'    => $fileDownloadUrl,
    '${prelistenUrl}'       => $prelistenUrl,
    '${projectOwnerImgUrl}' => $projectOwnerImgUrl,
    '${Common/bodyFooter}'  => buildBodyFooter($showMobileVersion),
    '${Common/pageFooter}'  => buildPageFooter()
), $showMobileVersion);

// END

// TODO - add google analytics snippet for all pages

?>