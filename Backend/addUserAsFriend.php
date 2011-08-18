<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
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

$userId = get_numeric_param('aid');
$trackId  = get_numeric_param('tid');

// check permissions
$track = Project::fetch_for_id($trackId);
if (!$track) {
    show_fatal_error_and_exit('no track found for id: ' . $trackId);

} else {
    if ($track->user_id != $loggedInUser->id) {
        show_fatal_error_and_exit('track ' . $trackId . ' does not belong to user ' . $loggedInUser->id);
    }
}

// add the user to the given track
$atav = new ProjectUserVisibility();
$atav->track_id  = $trackId;
$atav->user_id = $userId;
$atav->save();

echo 'Success';

?>