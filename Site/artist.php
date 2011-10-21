<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');
require_once('../Includes/mobile_device_detect.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectUserVisibility.php');
include_once('../Includes/DB/User.php');

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
    ), $showMobileVersion) . '<br /><br />'; // we don't put the newlines into the template because we probably need the link without them on a different page.
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

// additional info
$additionalInfo = '';
if ($user->additional_info) {
    $additionalInfo = processTpl('Artist/additionalInfo.html', array(
        '${additionalInfo}' => escape($user->additional_info)
    ), $showMobileVersion);
}

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

// track lists
//$mySongs         = Project::fetch_all_originals_of_user_id_from_to($user_id, 0, 99999999, false, false, $visitorUserId);
$mySongs         = Project::fetch_all_unfinished_projects_of_user($user_id); // FIXME - deal with finished projects somehow
//$remixes           = Project::fetch_all_remixes_of_user_id_from_to($user_id, 0, 99999999, false, false, $visitorUserId);
//$remixed_by_others = Project::fetch_all_remixes_for_originating_user_id_from_to($user_id, 0, 99999999, false, false, $visitorUserId);

//$rows = max(count($mySongs), count($remixes)), count($remixed_by_others));
$rows = count($mySongs);

$mySongsList = '';
//$myRemixesList = '';
//$remixedByOthersList = '';
for ($i = 0; $i < $rows; $i++) {
    if (isset($mySongs[$i])) {
        $mySongsList .= processTpl('Artist/trackListElement.html', array(
            '${userId}'  => $user_id,
            '${trackId}' => $mySongs[$i]->id,
            '${title}'   => escape($mySongs[$i]->title)
        ), $showMobileVersion);
    }

//    if (isset($remixes[$i])) {
//        $myRemixesList .= processTpl('Artist/trackListElement.html', array(
//            '${userId}'  => $user_id,
//            '${trackId}' => $remixes[$i]->id,
//            '${title}'   => escape($remixes[$i]->title)
//        ), $showMobileVersion);
//    }
//
//    if (isset($remixed_by_others[$i])) {
//        $remixedByOthersList .= processTpl('Artist/trackListElement.html', array(
//            '${userId}'  => $user_id,
//            '${trackId}' => $remixed_by_others[$i]->id,
//            '${title}'   => escape($remixed_by_others[$i]->title)
//        ), $showMobileVersion);
//    }
}

processAndPrintTpl('Artist/index.html', array(
    '${Common/pageHeader}'                              => buildPageHeader('User Info', false, false, false, $showMobileVersion),
    '${Common/bodyHeader}'                              => buildBodyHeader($visitorUser, $showMobileVersion),
    '${userId}'                                         => $user->id,
    '${userName}'                                       => escape($user->name),
    '${userImgUrl}'                                     => $userImgUrl,
    '${Common/externalWebLink_optional}'                => $webpageLink,
    '${Common/sendMessage_optional}'                    => $sendMessageBlock,
    '${Artist/artistInfo_optional}'                     => $artistInfo,
    '${Artist/additionalInfo_optional}'                 => $additionalInfo,
    '${Artist/trackListElement_list_mySongs}'           => $mySongsList,
    //'${Artist/trackListElement_list_myRemixes}'         => $myRemixesList, // FIXME - cleanup
    //'${Artist/trackListElement_list_remixedByOthers}'   => $remixedByOthersList,
    '${Common/bodyFooter}'                              => buildBodyFooter($showMobileVersion),
    '${Common/pageFooter}'                              => buildPageFooter()
), $showMobileVersion);

// END

// TODO - add google analytics snippet for all pages

?>