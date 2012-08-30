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

$pfid = get_numeric_param('pfid');
$vote = get_param('vote'); // h/n for hot/not
$nextUrl = get_param('next');

if (!userAlreadyVotedForProjectFile($user ? $user->id : null, $pfid)) {
    $pf = ProjectFile::fetch_for_id($pfid);
    if ($pf) {
        if ($vote == 'h') $pf->hot_count = $pf->hot_count + 1;
        else $pf->not_count = $pf->not_count + 1;
        $pf->update();
    }
}

if ($next) {
    redirectTo($GLOBALS['BASE_URL'] . $next);
} else {
    redirectTo($GLOBALS['BASE_URL'] . 'charts');
}

?>        	
