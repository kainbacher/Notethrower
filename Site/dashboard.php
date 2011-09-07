<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Logger.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Attribute.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/User.php');

$logger->set_debug_level();

$user = User::new_from_cookie();
ensureUserIsLoggedIn($user);

// find projects where the user could collaborate
$projectListHtml = '';
$projects = Project::fetchAllThatNeedSkillsOfUser(&$user); // FIXME - limit/paging?
foreach ($projects as $project) {
    $projectUserImgUrl = getUserImageUri($project->userImgFilename, 'tiny');

    $projectListHtml .= processTpl('Dashboard/projectListItem.html', array(
        '${userName}'     => escape($project->user_name),
        '${userImgUrl}'   => $projectUserImgUrl,
        '${projectId}'    => $project->id,
        '${projectTitle}' => escape($project->title),
        '${projectNeeds}' => implode(', ', $project->needsAttributeNamesList)
    ));
}

// find artists which could help the user with his projects
$artistListHtml = '';
$collabArtists = User::fetchAllThatOfferSkillsForUsersProjects(&$user); // FIXME - limit/paging?
foreach ($collabArtists as $collabArtist) {
    $collabArtistImgUrl = getUserImageUri($collabArtist->imageFilename, 'tiny');

    $artistListHtml .= processTpl('Dashboard/artistListItem.html', array(
        '${userName}'     => escape($collabArtist->name),
        '${userImgUrl}'   => $collabArtistImgUrl,
        '${userOffers}'   => implode(', ', $collabArtist->offersAttributeNamesList)
    ));
}

processAndPrintTpl('Dashboard/index.html', array(
    '${Common/pageHeader}'                     => buildPageHeader('Dashboard', true, false),
    '${Common/bodyHeader}'                     => buildBodyHeader($user),
    '${Dashboard/projectListItem_list}'        => $projectListHtml,
    '${Dashboard/artistListItem_list}'         => $artistListHtml,
    '${Common/bodyFooter}'                     => buildBodyFooter(),
    '${Common/pageFooter}'                     => buildPageFooter()
));

?>        	