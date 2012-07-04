<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Services_JSON.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');

$user = User::new_from_cookie();
if ($user) {
    $logger->info('user is logged in');

} else {
    show_fatal_error_and_exit('access denied for not-logged-in user');
}

$searchString = get_param('q');

$users = User::fetch_all_for_name_like($searchString, 20);

foreach ($users as $user) {
	$user->imagePath = getImagePath($user);
}

$jsonService = new Services_JSON();
echo $jsonService->encode($users);


function getImagePath(&$user) {
	if ($user->image_filename) {
        $filename = str_replace('.jpg', '_thumb.jpg', $user->image_filename);
        $userImg    = $GLOBALS['USER_IMAGE_BASE_PATH'] . $filename;
        $userImgUrl = $GLOBALS['USER_IMAGE_BASE_URL']  . $filename;

        if (file_exists($userImg)) {
      	    return $userImgUrl;

        } else {
      	    return $GLOBALS['BASE_URL'] . 'Images/no_artist_image.png';
        }

    } else {
        return $GLOBALS['BASE_URL'] . 'Images/no_artist_image.png';
    }
}

?>
