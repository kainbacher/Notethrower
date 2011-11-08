<?php

include_once('../Includes/Init.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/User.php');

//$logger->set_debug_level();

$user = User::new_from_cookie();
ensureUserIsLoggedIn($user);

$message = '';
if (get_param('msg')) {
    $message = processTpl('Common/message_notice.html', array(
        '${msg}' => escape(get_param('msg'))
    ));
}

$newbornProjectIdList = Project::fetchAllNewbornProjectIdsForUserId($user->id);
foreach ($newbornProjectIdList as $nbpid) {
    Project::delete_with_id($nbpid);
}

// own projects
$projects = Project::fetch_all_unfinished_projects_of_user($user->id); // FIXME - deal with finished projects somehow

$projectsList = '';
foreach ($projects as $p) {
    $projectsList .= processTpl('ProjectList/projectListItem.html', array(
        '${projectId}'                              => $p->id,
        '${projectTitle}'                           => escape($p->title),
        '${ProjectList/projectOwnerAddon_optional}' => '',
        '${projectTitleEscaped}'                    => escape_and_rewrite_single_quotes($p->title)
        // FIXME - later - visibility? facebook sharing?
    ));
}

if (count($projects) == 0) {
    $projectsList = 'No projects found';
}

// projects, where the user is associated
$associatedProjects = Project::fetch_all_associated_projects_of_user($user->id);

$associatedProjectsList = '';
foreach ($associatedProjects as $p) {
    $associatedProjectsList .= processTpl('ProjectList/projectListItem.html', array(
        '${projectId}'                              => $p->id,
        '${projectTitle}'                           => escape($p->title),
        '${ProjectList/projectOwnerAddon_optional}' => getProjectOwnerAddon($p->user_id, $p->user_name),
        '${projectTitleEscaped}'                    => escape_and_rewrite_single_quotes($p->title)
        // FIXME - later - visibility? facebook sharing?
    ));
}

if (count($associatedProjects) == 0) {
    $associatedProjectsList = 'No projects found';
}

processAndPrintTpl('ProjectList/index.html', array(
    '${Common/pageHeader}'                           => buildPageHeader('My projects'),
    '${Common/bodyHeader}'                           => buildBodyHeader($user),
    '${Common/message_choice_optional}'              => $message,
    '${ProjectList/projectListItem_list_own}'        => $projectsList,
    '${ProjectList/projectListItem_list_associated}' => $associatedProjectsList,
    '${Common/bodyFooter}'                           => buildBodyFooter(),
    '${Common/pageFooter}'                           => buildPageFooter()
));

// END

// functions
// -----------------------------------------------------------------------------
function getProjectOwnerAddon($ownerId, $ownerName) {
    return processTpl('ProjectList/projectOwnerAddon.html', array(
        '${ownerId}'   => $ownerId,
        '${ownerName}' => escape($ownerName)
    ));
}

?>