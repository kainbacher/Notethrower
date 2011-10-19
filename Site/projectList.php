<?php

include_once('../Includes/Init.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/User.php');

$logger->set_debug_level();

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

$originalProjects = Project::fetch_all_originals_of_user_id_from_to($user->id, 0, 999999999, true, true, -1);
$remixedProjects  = Project::fetch_all_remixes_of_user_id_from_to($user->id, 0, 999999999, true, true, -1); // FIXME - this should go away, right?

$originalProjectsList = '';
foreach ($originalProjects as $t) {
    $originalProjectsList .= processTpl('ProjectList/projectListItem.html', array(
        '${projectId}'           => $t->id,
        '${projectTitle}'        => escape($t->title),
        '${projectTitleEscaped}' => escape_and_rewrite_single_quotes($t->title)
        // FIXME - later - visibility? facebook sharing?
    ));
}

if (count($originalProjects) == 0) {
    $originalProjectsList = 'No projects found';
}

$remixedProjectsList = '';
foreach ($remixedProjects as $t) {
    $remixedProjectsList .= processTpl('ProjectList/projectListItem.html', array(
        '${projectId}'           => $t->id,
        '${projectTitle}'        => escape($t->title),
        '${projectTitleEscaped}' => escape_and_rewrite_single_quotes($t->title)
        // FIXME - later - visibility? facebook sharing? (see old script)
    ));
}

if (count($remixedProjects) == 0) {
    $remixedProjectsList = 'No projects found';
}

processAndPrintTpl('ProjectList/index.html', array(
    '${Common/pageHeader}'                          => buildPageHeader('My projects'),
    '${Common/bodyHeader}'                          => buildBodyHeader($user),
    '${Common/message_choice_optional}'             => $message,
    '${ProjectList/projectListItem_originals_list}' => $originalProjectsList,
    '${ProjectList/projectListItem_remixes_list}'   => $remixedProjectsList,
    '${Common/bodyFooter}'                          => buildBodyFooter(),
    '${Common/pageFooter}'                          => buildPageFooter()
));

// END

// functions
// -----------------------------------------------------------------------------

?>