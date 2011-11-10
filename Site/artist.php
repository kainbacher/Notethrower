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

// find out if the user browses with a mobile device
$showMobileVersion = false;
$isMobileDevice = mobile_device_detect(true,false,true,true,true,true,true,false,false);
if ($isMobileDevice || get_param('_forceMobile')) {
    $showMobileVersion = true;
}

// let's see if the visiting user is a logged in user
$visitorUserId = -1;
$visitorUser = User::new_from_cookie();
if ($visitorUser) {
    $visitorUserId = $visitorUser->id;
    $logger->info('visitor user id: ' . $visitorUserId);
}

$user_id = get_numeric_param('aid');

$user = User::fetch_for_id($user_id);
if (!$user) {
    show_fatal_error_and_exit('no user found for id: ' . $user_id);
}

// user image
$userImgUrl = getUserImageUri($user->image_filename, 'regular');

$editProfileLink = '';
if ($visitorUser && $visitorUserId == $user->id) {
    $editProfileLink = processTpl('Artist/editProfileLink.html', array());
}

// webpage url
$webpageLink = '';
if ($user->webpage_url) {
    $webpageUrl = $user->webpage_url;
    if (substr($user->webpage_url, 0, 7) != 'http://' && substr($user->webpage_url, 0, 8) != 'https://') {
        $webpageUrl = 'http://' . $user->webpage_url;
    }

    $webpageLink = processTpl('Common/externalWebLink.html', array(
        '${href}'  => escape($webpageUrl),
        '${label}' => escape($user->webpage_url)
    ), $showMobileVersion) . '<br />'; // we don't put the newlines into the template because we probably need the link without them on a different page.
}

// facebook url
$facebookLink = '';
if ($user->facebook_url) {
    $facebookUrl = $user->facebook_url;
    if (substr($user->facebook_url, 0, 7) != 'http://' && substr($user->facebook_url, 0, 8) != 'https://') {
        $facebookUrl = 'http://' . $user->facebook_url;
    }

    $facebookLink = processTpl('Common/externalFacebookLink.html', array(
        '${href}'  => escape($facebookUrl)
    ), $showMobileVersion) . '<br />'; // we don't put the newlines into the template because we probably need the link without them on a different page.
}

// twitter url
$twitterLink = '';
if ($user->twitter_username) {
    $twitterUrl = 'http://twitter.com/' . $user->twitter_username;
    $twitterLink = processTpl('Common/externalTwitterLink.html', array(
        '${href}'  => escape($twitterUrl)
    ), $showMobileVersion) . '<br />'; // we don't put the newlines into the template because we probably need the link without them on a different page.
}

// send message
$sendMessageBlock = '';
if ($visitorUser && $user->id != $visitorUser->id) {
    $sendMessageBlock = processTpl('Common/sendMessage.html', array(
        '${recipientUserId}' => $user->id,
        '${recipientName}'   => escape($user->name)
    ), $showMobileVersion);
}

// artist info
$artistInfo = '';
if ($user->artist_info) {
    $artistInfo = processTpl('Artist/artistInfo.html', array(
        '${artistInfo}' => escape($user->artist_info)
    ), $showMobileVersion);
}

// influences
$influences = '';
if ($user->influences) {
    $influences = processTpl('Artist/influences.html', array(
        '${influences}' => escape($user->influences)
    ), $showMobileVersion);
}

// video
$video = '';
if ($user->video_url) {
    preg_match("#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $user->video_url, $video_match);
    $video = processTpl('Artist/video.html', array(
        '${videoId}' => escape($video_match[0])
    ), $showMobileVersion);
}


// currently hidden
//// additional info
//$additionalInfo = '';
//if ($user->additional_info) {
//    $additionalInfo = processTpl('Artist/additionalInfo.html', array(
//        '${additionalInfo}' => escape($user->additional_info)
//    ), $showMobileVersion);
//}

// collaborators - FIXME - put into common template
//    $collaborators = ProjectUserVisibility::fetch_all_collaboration_users_of_user_id($user->id, 10); // attention: if the limit of 10 is changed, the code below must be changed as well (row processing code and colspans)
//    if (count($collaborators) > 0) {
//        echo '<br><br><h2>' . escape($user->name) . '\'s friends:</h2>' . "\n";
//        echo '<table>';
//        // row 1
//        echo '<tr>';
//        for ($i = 0; $i < 5; $i++) {
//            echo '<td>';
//            if (isset($collaborators[$i])) {
//                echo '<a href="artist.php?aid=' . $collaborators[$i]->collaborating_user_id . '" target="_blank">';
//                echo getUserImageHtml($collaborators[$i]->user_image_filename, $collaborators[$i]->user_name, 'tiny');
//                echo '</a>';
//
//            } else {
//                echo '&nbsp;';
//            }
//            echo '</td>' . "\n";
//        }
//        echo '</tr>' . "\n";
//
//        // row 2
//        echo '<tr>';
//        for ($i = 5; $i < 10; $i++) {
//            echo '<td>';
//            if (isset($collaborators[$i])) {
//                echo '<a href="artist.php?aid=' . $collaborators[$i]->collaborating_user_id . '" target="_blank">';
//                echo getUserImageHtml($collaborators[$i]->user_image_filename, $collaborators[$i]->user_name, 'tiny');
//                echo '</a>';
//
//            } else {
//                echo '&nbsp;';
//            }
//            echo '</td>' . "\n";
//        }
//        echo '</tr>' . "\n";
//
//        if (count($collaborators) > 10) {
//            echo '<tr><td colspan="5">' . "\n";
//            echo '... and some more. <div class="sendMessageLink"><a href="javascript:showCollaborationUsersPopup();" target="_blank"></div>';
//            echo 'See all.';
//            echo '</a>';
//            echo '</td></tr>' . "\n";
//        }
//        echo '</table' . "\n";
//    }

// FIXME - what about the widget? we wanna get away from the flash stuff but can we use the jplayer instead? can it be started by a JS call?
//        <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="300" height="400" id="NTWidget" align="middle">
//	                                  <param name="allowScriptAccess" value="always" />
//	                                  <param name="allowFullScreen" value="false" />
//	                                  <param name="movie" value="../Widget/PpWidget.swf?aid=#####php echo $user->id; ####" />
//	                                  <param name="loop" value="false" />
//	                                  <param name="quality" value="high" />
//	                                  <param name="wmode" value="transparent" />
//	                                  <param name="bgcolor" value="#ffffff" />
//	                                  <embed src="../Widget/PpWidget.swf?aid=#####php echo $user->id; #######" loop="false" quality="high" wmode="transparent" bgcolor="#ffffff" width="300" height="400" name="NTWidget" align="middle" allowScriptAccess="always" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
//	                                </object>
//

// project lists
$unfinishedProjects = Project::fetch_all_unfinished_projects_of_user($user_id, false); // FIXME - deal with finished projects somehow
$projectsSection = '';
$projectsList = '';
foreach ($unfinishedProjects as $unfinishedProject) {
    $projectsList .= processTpl('Artist/projectListElement.html', array(
        '${userId}'    => $user_id,
        '${projectId}' => $unfinishedProject->id,
        '${title}'     => escape($unfinishedProject->title)
    ), $showMobileVersion);
}

if (count($unfinishedProjects) == 0) {
    $projectsList = 'No open projects found.';
}

$projectsSection = processTpl('Artist/projectsSection.html', array(
    '${Artist/projectListElement_list}' => $projectsList
), $showMobileVersion);


// released tracks
$releasedTracks = ProjectFile::fetch_all_for_user_id_and_type($user_id, 'release');
$releasesSection = '';
$releasedTracksList = '';
foreach ($releasedTracks as $releasedTrack) {
    $releasedTracksList .= processTpl('Artist/trackListElement.html', array(
        '${userId}'        => $user_id,
        '${projectFileId}' => $releasedTrack->id,
        '${title}'         => escape($releasedTrack->title) . 'FIXME'
    ), $showMobileVersion);
}

if (count($releasedTracks) == 0) {
    $releasedTracksList = 'No released tracks found.';
}

$releasesSection = processTpl('Artist/releasesSection.html', array(
    '${Artist/releaseListElement_list}' => $releasedTracksList
), $showMobileVersion);

$skills = implode(', ', UserAttribute::getAttributeNamesForUserIdAndState($user->id, 'offers'));
$genres = implode(', ', UserGenre::getGenreNamesForUserId($user->id));
$tools  = implode(', ', UserTool::getToolNamesForUserId($user->id));

processAndPrintTpl('Artist/index.html', array(
    '${Common/pageHeader}'                                 => buildPageHeader('Artist', false, false, false, $showMobileVersion),
    '${Common/bodyHeader}'                                 => buildBodyHeader($visitorUser, $showMobileVersion),
    '${userId}'                                            => $user->id,
    '${userName}'                                          => escape($user->name),
    '${userImgUrl}'                                        => $userImgUrl,
    '${Common/externalWebLink_list}'                       => $webpageLink . $facebookLink . $twitterLink,
    '${Common/sendMessage_optional}'                       => $sendMessageBlock,
    '${Artist/artistInfo_optional}'                        => $artistInfo,
    '${Artist/influences_optional}'                        => $influences,
    //'${Artist/additionalInfo_optional}'                    => $additionalInfo, // currently hidden
    '${Artist/video_optional}'                             => $video,
    '${Artist/releasesSection_optional}'                   => $releasesSection,
    '${Artist/projectsSection_optional}'                   => $projectsSection,
    '${Artist/editProfileLink_optional}'                   => $editProfileLink,
    '${skills}'                                            => $skills,
    '${genres}'                                            => $genres,
    '${tools}'                                             => $tools,
    '${Common/bodyFooter}'                                 => buildBodyFooter($showMobileVersion),
    '${Common/pageFooter}'                                 => buildPageFooter()
), $showMobileVersion);

// END

// TODO - add google analytics snippet for all pages

?>