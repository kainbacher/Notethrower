<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/UserAttribute.php');
include_once('../Includes/DB/UserGenre.php');
include_once('../Includes/DB/Genre.php');
include_once('../Includes/DB/Attribute.php');

$visitorUserId = -1;

$userIsLoggedIn = false;
$user = User::new_from_cookie();
if ($user) {
    $visitorUserId = $user->id;
    $logger->info('visitor user id: ' . $visitorUserId);

    $userIsLoggedIn = true;
    $logger->info('user is logged in');

} else {
    $logger->info('user is NOT logged in');
}

$topArtistsList = '';
$topArtists = User::fetch_most_listened_artists_from_to(0, 99999); // FIXME - paging?

foreach ($topArtists as $a) {
    $topArtistsList .= buildArtistRow($a);
}

buildPage($topArtistsList);

// END

// functions
function buildArtistRow(&$a) {
	return '<li><a href="artist-single.php?aid=' . $a->id . '">' .
           '<img src="' . getUserImageUri($a->image_filename, 'tiny') . '" alt="img" width="80">' . // height="80">' .
           '<h3>' . escape($a->name) . '</h3>' .
           '</a></li>' . "\n";
}

function buildPage(&$artistsList) {
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Artist list</title>
		<link rel="stylesheet" href="themes/oneloudr.min.css" />
		<link rel="stylesheet" href="http://code.jquery.com/mobile/1.1.1/jquery.mobile.structure-1.1.1.min.css" />
		<script src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
		<script src="http://code.jquery.com/mobile/1.1.1/jquery.mobile-1.1.1.min.js"></script>
		<script src="mobile.js"></script>
	</head>
	<body>
		<div data-role="page" data-theme="a">
			<div data-role="header" data-position="inline">
				<h1>Oneloudr</h1>
			</div>

            <div data-role="navbar">
                <ul>
                    <li><a href="track-list.php" data-href="a">Songs</a></li>
                    <li><a href="artist-list.php" class="ui-btn-active ui-state-persist" data-href="b">Artists</a></li>
                </ul>
            </div><!-- /navbar -->

			<div data-role="content" data-theme="a">

                <ul data-role="listview">
                	<?= $artistsList ?>
                </ul>

			</div>
		</div>
	</body>
</html>
<?php
}
?>