<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');
require_once('../Includes/mobile_device_detect.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectUserVisibility.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/UserAttribute.php');
include_once('../Includes/DB/UserGenre.php');
include_once('../Includes/DB/UserTool.php');

// let's see if the visiting user is a logged in user
$visitorUserId = -1;
$visitorUser = User::new_from_cookie();
if ($visitorUser) {
    $visitorUserId = $visitorUser->id;
    $logger->info('visitor user id: ' . $visitorUserId);
}

$user_id = get_numeric_param('aid');

$user = User::fetch_for_id($user_id);
if (!$user || $user->status != 'active') {
    show_fatal_error_and_exit('no (active) user found for id: ' . $user_id);
}

$skills = implode(', ', UserAttribute::getAttributeNamesForUserIdAndState($user->id, 'offers'));
$genres = implode(', ', UserGenre::getGenreNamesForUserId($user->id));
$tools  = implode(', ', UserTool::getToolNamesForUserId($user->id));

$releasesList = '';
$releasedTracks = ProjectFile::fetch_all_for_user_id_and_type($user_id, 'release');

foreach ($releasedTracks as $releasedTrack) {
    $releasesList .= buildReleaseRow($releasedTrack);
}

buildPage($user, $skills, $genres, $tools, $releasesList);

// END

// functions
function buildReleaseRow($r) {
	return '<li><a href="track-single.php?pfid=' . $r->id . '">' .
           '<h3>' . escape($r->release_title) . '</h3>' .
           '</a></li>';
}

function buildPage(&$a, $skills, $genres, $tools, $releases) {
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?= escape($a->name) ?></title>
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
                    <li><a href="artist-list.php" data-href="b">Artists</a></li>
                </ul>
            </div><!-- /navbar -->

			<div data-role="content" data-theme="a">

    			<img src="<?= getUserImageUri($a->image_filename, 'regular') ?>" alt="img" width="100%" >

    			<br />
    			<br />

    			<h1><?= escape($a->name) ?></h1>

    			<div>
        			<p><strong>Skills: </strong><?= escape($skills) ?></p>
    			</div>

    			<div>
        			<p><strong>Genres: </strong><?= escape($genres) ?></p>
    			</div>

    			<div>
        			<p><strong>Tools: </strong><?= escape($tools) ?></p>
    			</div>

    			<?= showReleasesBlock($releases) ?>

			</div>
		</div>
	</body>
</html>
<?php
}

function showReleasesBlock($releases) {
	if (!$releases) return;
?>
<h2>Releases</h2>

<ul data-role="listview" data-inset="true" data-theme="a">
    <?= $releases ?>
</ul>
<?php
}

?>