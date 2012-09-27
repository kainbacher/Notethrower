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
    
    // show voting links if not already voted
    $votingLinksHtml = '';
    if (!userAlreadyVotedForProjectFile($user ? $user->id : null, $releasedTrack->id)) {
        $votingLinksHtml = processTpl('Common/hotNotVotingLinks.html', array(
            '${pfid}' => $releasedTrack->id
        ));
    }
    
    $project = Project::fetch_for_id($releasedTrack->project_id);
    
    $totalHotNotCount = $releasedTrack->hot_count + $releasedTrack->not_count;
    $totalHotNotCountAnon = $releasedTrack->hot_count_anon + $releasedTrack->not_count_anon;
    $totalHotNotCountPro = $releasedTrack->hot_count_pro + $releasedTrack->not_count_pro;
    
    $hotPercentage = ($totalHotNotCount + $totalHotNotCountAnon * 0.5 + $totalHotNotCountPro * 2) > 0 ? 
            ($releasedTrack->hot_count + $releasedTrack->hot_count_anon * 0.5 + $releasedTrack->hot_count_pro * 2) / ($totalHotNotCount + $totalHotNotCountAnon * 0.5 + $totalHotNotCountPro * 2)
            : 
            0;
    $notPercentage = ($totalHotNotCount + $totalHotNotCountAnon * 0.5 + $totalHotNotCountPro * 2) > 0 ? 
            ($releasedTrack->not_count + $releasedTrack->not_count_anon * 0.5 + $releasedTrack->not_count_pro * 2) / ($totalHotNotCount + $totalHotNotCountAnon * 0.5 + $totalHotNotCountPro * 2)
            : 
            0;
			    
    $songListHtml .= processTpl('Charts/songListItem.html', array(
	    '${artistImgUrl}'                      => getUserImageUri($project->user_img_filename, 'tiny'),
        '${chartRank}'                         => $chartRank,
        '${pfid}'                              => $releasedTrack->id,
        '${hotPercentage}'                     => number_format(100 * $hotPercentage, 1, '.', ''),
        '${notPercentage}'                     => number_format(100 * $notPercentage, 1, '.', ''),
        '${Common/player_optional}'            => $playerHtml,
        '${fileDownloadUrl}'                   => $fileDownloadUrl,
        '${filename}'                          => escape($releasedTrack->orig_filename),
        '${releasePageUrl}'                    => $releasePageUrl,
        '${title}'                             => escape($releasedTrack->release_title),
		'${baseUrlEncoded}'                    => urlencode($GLOBALS['BASE_URL']),
        '${userId}'                            => $project->user_id,
        '${userName}'                          => escape($project->user_name),
        '${Common/hotNotVotingLinks_optional}' => $votingLinksHtml
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
