<?php

include_once('../Includes/Init.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/User.php');

$user = User::new_from_cookie();
ensureUserIsLoggedIn($user);

$message = '';
if (get_param('msg')) {
    $message = processTpl('Common/message_notice.html', array(
        '${msg}' => escape(get_param('msg'))
    ));
}

$newbornProjectIdList = Project::fetchAllNewbornProjectIdsForUserId($user->id);
foreach ($newbornProjectIdList as $nbpid) {
    Project::delete_with_id($nbpid);
}

$originalTracks = Project::fetch_all_originals_of_user_id_from_to($user->id, 0, 999999999, true, true, -1);
$remixedTracks  = Project::fetch_all_remixes_of_user_id_from_to($user->id, 0, 999999999, true, true, -1);

$originalTracksList = '';
foreach ($originalTracks as $t) {
    $originalTracksList .= processTpl('TrackList/trackListItem.html', array(
        '${trackId}'           => $t->id,
        '${trackTitle}'        => escape($t->title),
        '${trackTitleEscaped}' => escape_and_rewrite_single_quotes($t->title)
        // FIXME - later - visibility? facebook sharing?
    ));
}

if (count($originalTracks) == 0) {
    $originalTracksList = 'No tracks found';
}

$remixedTracksList = '';
foreach ($remixedTracks as $t) {
    $remixedTracksList .= processTpl('TrackList/trackListItem.html', array(
        '${trackId}'           => $t->id,
        '${trackTitle}'        => escape($t->title),
        '${trackTitleEscaped}' => escape_and_rewrite_single_quotes($t->title)
        // FIXME - later - visibility? facebook sharing? (see old script)
    ));
}

if (count($remixedTracks) == 0) {
    $remixedTracksList = 'No tracks found';
}

processAndPrintTpl('TrackList/index.html', array(
    '${Common/pageHeader}'                      => buildPageHeader('My tracks'),
    '${Common/bodyHeader}'                      => buildBodyHeader($user),
    '${Common/message_choice_optional}'         => $message,
    '${TrackList/trackListItem_originals_list}' => $originalTracksList,
    '${TrackList/trackListItem_remixes_list}'   => $remixedTracksList,
    '${Common/bodyFooter}'                      => buildBodyFooter(),
    '${Common/pageFooter}'                      => buildPageFooter()
));

// END

// functions
// -----------------------------------------------------------------------------

?>