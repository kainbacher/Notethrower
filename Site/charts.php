<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Logger.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectFile.php');
include_once('../Includes/DB/User.php');

//$logger->set_debug_level();

$user = User::new_from_cookie();
//ensureUserIsLoggedIn($user);

// find projects where the user could collaborate
$songListHtml = '';
$releases = ProjectFile::fetch_all_for_type('release', 'pf.competition_points desc'); // FIXME - add a limit
foreach ($releases as $projectFile) {
    //$projectUserImgUrl = getUserImageUri($song->user_img_filename, 'tiny');

    $project = Project::fetch_for_id($projectFile->project_id); // FIXME - find a more performant way to get the project info
    
    $projectListHtml .= processTpl('Charts/songListItem.html', array(
        '${userId}'       => $project->user_id,
        '${userName}'     => escape($project->user_name),
        '${userImgUrl}'   => $projectUserImgUrl,
        '${projectId}'    => $project->id,
        '${projectTitle}' => escape($project->title)
    ));
}

if (count($projects) == 0) {
    $projectListHtml = 'No released songs found.';
}

processAndPrintTpl('Charts/index.html', array(
    '${Common/pageHeader}'                     => buildPageHeader('Charts', false, false),
    '${Common/bodyHeader}'                     => buildBodyHeader($user),
    '${userId}'                                => $user->id,
    '${Charts/songListItem_list}'              => $projectListHtml,
    '${Common/bodyFooter}'                     => buildBodyFooter(),
    '${Common/pageFooter}'                     => buildPageFooter()
));

?>        	
