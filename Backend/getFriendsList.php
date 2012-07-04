<?php

include_once('../Includes/Init.php');
include_once('../Includes/Services_JSON.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/ProjectUserVisibility.php');
include_once('../Includes/DB/User.php');

$user = User::new_from_cookie();
if ($user) {
    $logger->info('user is logged in');

} else {
    show_fatal_error_and_exit('access denied for not-logged-in user');
}

$userId = get_numeric_param('aid');
$trackId  = get_numeric_param('tid');

// search for the selected users
$users = ProjectUserVisibility::fetch_all_for_project_id($trackId);

$jsonService = new Services_JSON();
echo $jsonService->encode($users);

?>
