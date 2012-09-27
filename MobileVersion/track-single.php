<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');
require_once('../Includes/mobile_device_detect.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectFile.php');
include_once('../Includes/DB/User.php');

// let's see if the visiting user is a logged in user
$visitorUserId = -1;
$visitorUser = User::new_from_cookie();
if ($visitorUser) {
    $visitorUserId = $visitorUser->id;
    $logger->info('visitor user id: ' . $visitorUserId);
}

$pfid = get_numeric_param('pfid');

$projectFile = ProjectFile::fetch_for_id($pfid);
if (!$projectFile || $projectFile->status != 'active') {
    show_fatal_error_and_exit('no (active) project file found for id: ' . $pfid);
}

if ($projectFile->type != 'release') {
    show_fatal_error_and_exit('project file is not a release');
}

$project = Project::fetch_for_id($projectFile->project_id);
if (!$project || $project->status != 'active') {
    show_fatal_error_and_exit('no (active) project found for id: ' . $projectFile->project_id);
}

$projectFiles = ProjectFile::fetch_all_for_project_id($project->id);

$autocreatedSibling = null;
foreach ($projectFiles as $tmpPf) {
    if ($tmpPf->autocreated_from == $projectFile->id) {
        $autocreatedSibling = $tmpPf;
        break;
    }
}

$fileDownloadUrl = $GLOBALS['BASE_URL'] . 'Backend/downloadFile.php?mode=download&project_id=' . $project->id . '&atfid=' . $projectFile->id;
$prelistenUrl = $fileDownloadUrl;
if ($autocreatedSibling) {
    $prelistenUrl = $GLOBALS['BASE_URL'] . 'Backend/downloadFile.php?mode=download&project_id=' . $project->id . '&atfid=' . $autocreatedSibling->id;
}

if (getFileExtension($projectFile->filename) != 'mp3') $prelistenUrl = null; // currently we can only play mp3 files

buildPage($projectFile, $project, $prelistenUrl, $fileDownloadUrl);

$logger->info('done');

// END

// functions
function buildPage(&$r, &$project, $prelistenUrl, $fileDownloadUrl) {
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?= escape($r->release_title) ?></title>
		<link rel="stylesheet" href="themes/oneloudr.min.css" />
		<link rel="stylesheet" href="http://code.jquery.com/mobile/1.1.1/jquery.mobile.structure-1.1.1.min.css" />
		<script src="http://code.jquery.com/jquery-1.7.1.min.js"></script>
		<script src="http://code.jquery.com/mobile/1.1.1/jquery.mobile-1.1.1.min.js"></script>
		<script src="mobile.js"></script>


		<link href="<?= $GLOBALS['BASE_URL'] ?>/Styles/jplayer/minimal.skin/jplayer.minimal.css" rel="stylesheet" type="text/css">


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
                <?= buildPlayer($r, $prelistenUrl, $fileDownloadUrl) ?>

    			<h2><?= escape($r->release_title) ?><br />
    			by <a href="artist-single.php?aid=<?= $project->user_id ?>"><?= escape($project->user_name) ?></a></h2>

			</div>
		</div>
	</body>
</html>
<?php
}

function buildPlayer(&$r, $prelistenUrl, $fileDownloadUrl) {
	if ($prelistenUrl) {
?>
<!--jplayer start-->
<script type="text/javascript">
//<![CDATA[

$(document).ready(function(){

    $("#jquery_jplayer_${projectFileId}").jPlayer({
        ready: function () {
            $(this).jPlayer("setMedia", {
                mp3:"<?= $prelistenUrl ?>"
            });
        },
        solution: "html",
        supplied: "mp3",
        wmode: "window",
        cssSelectorAncestor: "#jp_container"
    });
});
//]]>
</script>
<div id="jquery_jplayer" class="jp-jplayer"></div>

<br /><br />
<audio src="<?= $prelistenUrl ?>">
<br /><br />


<div id="jp_container_<?= $r->id ?>" class="jp-audio">
    <div class="jp-type-single">
        <div class="jp-gui jp-interface">
            <ul class="jp-controls">
                <li><a href="javascript:;" class="jp-play" tabindex="1">play</a></li>
                <li><a href="javascript:;" class="jp-pause" tabindex="1">pause</a></li>
        		<li><a href="javascript:;" class="jp-mute" tabindex="1">mute</a></li>
        		<li><a href="javascript:;" class="jp-unmute" tabindex="1">unmute</a></li>

            </ul>

        	<div class="jp-progress-container">
        		<div class="jp-progress">
        			<div class="jp-seek-bar" style="width: 100%; ">
        				<div class="jp-play-bar" style="width: 30.849901166487577%; "></div>
        			</div>
        		</div>
        	</div>

        	<div class="jp-volume-bar-container">
        		<div class="jp-volume-bar">
        			<div class="jp-volume-bar-value" style="width: 80%; "></div>
        		</div>
        	</div>

        </div>
        <div class="jp-no-solution">
            <span>Update Required</span>
            To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.
        </div>
    </div>
</div>
<!--jplayer end-->
<?php
	} else { // no mp3 file for prelistening available, show file download link
?>
<a href="<?= $fileDownloadUrl ?>">Download song</a>
<?php
	}
}
?>