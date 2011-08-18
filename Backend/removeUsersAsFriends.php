<?php

include_once('../Includes/Init.php');

include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectUserVisibility.php');
include_once('../Includes/DB/User.php');

$loggedInUser = User::new_from_cookie();
if ($loggedInUser) {
    $logger->info('user is logged in');

} else {
    show_fatal_error_and_exit('access denied for not-logged-in user');
}

$trackId         = get_numeric_param('tid');
$userIdListStr = get_param('aids');

// check permissions
$track = Project::fetch_for_id($trackId);
if (!$track) {
    show_fatal_error_and_exit('no track found for id: ' . $trackId);

} else {
    if ($track->user_id != $loggedInUser->id) {
        $logger->debug('cp6');
        show_fatal_error_and_exit('track ' . $trackId . ' does not belong to user ' . $loggedInUser->id);
    }
}

// remove the users with the specified ids (e.g. 12,13,43)
ProjectUserVisibility::delete_all_with_track_id_and_user_id_list($trackId, explode(',', $userIdListStr));

echo 'Success';

?>