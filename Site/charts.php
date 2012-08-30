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
$songListHtml = '';
$releasedTracks = ProjectFile::fetch_all_releases_ordered_by_rating(); // FIXME - add a limit
$releasedTracksCopy = $releasedTracks;

$chartRank = 1;
foreach ($releasedTracks as $releasedTrack) {
    $autocreatedSibling = null;
    foreach ($releasedTracksCopy as $tmpPf) {
        if ($tmpPf->autocreated_from == $releasedTrack->id) {
            $autocreatedSibling = $tmpPf;
            break;
        }
    }
    
    $fileDownloadUrl = $GLOBALS['BASE_URL'] . 'Backend/downloadFile.php?mode=download&project_id=' . $releasedTrack->project_id . '&atfid=' . $releasedTrack->id;
    
    $releasePageUrl = $fileDownloadUrl;
    // FIXME - activate as soon as this page is ready: $releasePageUrl = $GLOBALS['BASE_URL'] . 'release?pfid=' . $releasedTrack->id;

    $prelistenUrl = $fileDownloadUrl;
    if ($autocreatedSibling) {
        $prelistenUrl = $GLOBALS['BASE_URL'] . 'Backend/downloadFile.php?mode=download&project_id=' . $releasedTrack->project_id . '&atfid=' . $autocreatedSibling->id;
    }
    
    $playerHtml = '<i>(no mp3 file available)</i>';
    if (
        getFileExtension($releasedTrack->filename) == 'mp3' ||
        $autocreatedSibling
    ) {
        $playerHtml = processTpl('Common/player.html', array(
            '${projectFileId}'   => $releasedTrack->id,
            '${prelisteningUrl}' => $prelistenUrl
        ));
    }
    
    $project = Project::fetch_for_id($releasedTrack->project_id);
    
    $totalHotNotCount = $releasedTrack->hot_count + $releasedTrack->not_count;
    
    $songListHtml .= processTpl('Charts/songListItem.html', array(
        '${chartRank}'              => $chartRank,
        '${pfid}'                   => $releasedTrack->id,
        '${hotPercentage}'          => $totalHotNotCount > 0 ? number_format(100 * $releasedTrack->hot_count / $totalHotNotCount, 1, '.', '') : 0,
        '${notPercentage}'          => $totalHotNotCount > 0 ? number_format(100 * $releasedTrack->not_count / $totalHotNotCount, 1, '.', '') : 0,
        '${Common/player_optional}' => $playerHtml,
        '${fileDownloadUrl}'        => $fileDownloadUrl,
        '${filename}'               => escape($releasedTrack->orig_filename),
        '${releasePageUrl}'         => $releasePageUrl,
        '${title}'                  => escape($releasedTrack->release_title),
        '${userId}'                 => $project->user_id,
        '${userName}'               => escape($project->user_name),
    ));
    
    $chartRank++;
}

if (count($releasedTracks) == 0) {
    $songListHtml = 'No released songs found.';
}

processAndPrintTpl('Charts/index.html', array(
    '${Common/pageHeader}'                     => buildPageHeader('Charts', true, false, false, true),
    '${Common/bodyHeader}'                     => buildBodyHeader($user),
    '${userId}'                                => $user->id,
    '${Charts/songListItem_list}'              => $songListHtml,
    '${Common/bodyFooter}'                     => buildBodyFooter(),
    '${Common/pageFooter}'                     => buildPageFooter()
));

?>        	
