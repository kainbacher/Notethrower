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

// find projects where the user could collaborate
$songList = '';
$releasedTracks = ProjectFile::fetch_all_releases_ordered_by_rating(); // FIXME - add a limit

foreach ($releasedTracks as $releasedTrack) {
	$songList .= buildReleaseRow($releasedTrack);
}

buildPage($songList);

// END

// functions
function buildReleaseRow($r) {
	$project = Project::fetch_for_id($r->project_id); // FIXME - add caching or optimize somehow
	
	return '<li><a href="track-single.php?pfid=' . $r->id . '">' .
           '<h3>' . escape($r->release_title) . '</h3>' .
           '<p>' . escape($project->user_name) . '</p>' .
           '</a></li>';
}

function buildPage($songList) {
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Charts</title>
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
                    <li><a href="track-list.php" class="ui-btn-active ui-state-persist" data-href="a">Songs</a></li>
                    <li><a href="artist-list.php" data-href="b">Artists</a></li>
                </ul>
            </div><!-- /navbar -->

			<div data-role="content" data-theme="a">

                <ul data-role="listview" data-theme="b">
                	<?= $songList ?>
                </ul>

			</div>
		</div>
	</body>
</html>
<?php
}
?>