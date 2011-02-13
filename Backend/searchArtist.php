<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Services_JSON.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Artist.php');

include_once('../Includes/DB/Artist.php');

$artist = Artist::new_from_cookie();
if ($artist) {
    $logger->info('user is logged in');

} else {
    show_fatal_error_and_exit('access denied for not-logged-in user');
}

$searchString = get_param('q');

$artists = Artist::fetch_all_for_name_like($searchString, 20);

foreach ($artists as $artist) {
	$artist->imagePath = getImagePath($artist);
}

$jsonService = new Services_JSON();
echo $jsonService->encode($artists);


function getImagePath($artist) {
	if ($artist->image_filename) {
        $filename = str_replace('.jpg', '_thumb.jpg', $artist->image_filename);
        $artistImg    = $GLOBALS['ARTIST_IMAGE_BASE_PATH'] . $filename;
        $artistImgUrl = $GLOBALS['ARTIST_IMAGE_BASE_URL']  . $filename;

        if (file_exists($artistImg)) {
      	    return $artistImgUrl;

        } else {
      	    return $GLOBALS['BASE_URL'] . 'Images/no_artist_image.png';
        }

    } else {
        return $GLOBALS['BASE_URL'] . 'Images/no_artist_image.png';
    }
}

?>