<?php

include_once('../Includes/Init.php');

include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/AudioTrackUserVisibility.php');

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

writePageDoctype();

?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <?php writePageMetaTags(); ?>
    <title><?php writePageTitle(); ?></title>
    <link rel="stylesheet" href="../Styles/main.css" type="text/css">

    <script type="text/javascript" src="../Javascripts/jquery-1.6.1.min.js"></script>
    <script type="text/javascript" src="../Javascripts/jquery.main.js"></script>

    <script type="text/javascript">

function getFlashContent(name) {
    if (navigator.appName.indexOf("Microsoft") != -1) {
        return window[name];
    } else {
        return document[name];
    }
}

function reloadDataInWidget(aid, tid) {
    getFlashContent("NTWidget").reloadData(aid, tid);
}

function showCollaborationUsersPopup() {
    window.open('showCollaborationUsers.php?aid=<?php echo $user->id; ?>', 'NT_COLLABORATORS', 'scrollbars=yes,resizable=yes,status=0,width=400,height=600');
}

function showSendMessagePopup(raid) {
    window.open('sendMessage.php?raid=' + raid, 'NT_SEND_MESSAGE', 'scrollbars=yes,resizable=yes,status=0,width=600,height=400');
}

    </script>
    </head>
    <body>
        <div id="bodyWrapper">
            <? include ("pageHeader.php"); ?>
            <? include ("mainMenu.php"); ?>

            <div id="pageMainContent">

                <div id="userInfoContainer">

                    <div id="userInfoDiv">
                        <div id="container">


                            <div id="userInfoLeft">

<?php
    // user image
    if ($user->image_filename) {
        $userImg    = $GLOBALS['USER_IMAGE_BASE_PATH'] . $user->image_filename;
        $userImgUrl = $GLOBALS['USER_IMAGE_BASE_URL']  . $user->image_filename;

        $logger->info('user img: ' . $userImg);

        if (file_exists($userImg)) {
      	    echo '<img src="' . $userImgUrl . '" width="200">';

        } else {
      	    echo '<img src="' . $GLOBALS['BASE_URL'] . 'Images/no_artist_image.png" width="200">';
        }

    } else {
        echo '<img src="' . $GLOBALS['BASE_URL'] . 'Images/no_artist_image.png" width="200">';
    }

    echo '<br><br>' . "\n";

    // webpage url
    if ($user->webpage_url) {
        $webpageUrl = $user->webpage_url;
        if (substr($user->webpage_url, 0, 7) != 'http://' && substr($user->webpage_url, 0, 8) != 'https://') {
            $webpageUrl = 'http://' . $user->webpage_url;
        }
        echo '<div class="weblink"><a href="' . escape($webpageUrl) . '" target="_blank"><img src="../Images/icon_weblink.png" alt="icon_weblink" width="16" height="16"/><span class="weblinkText">' . escape($user->webpage_url) . '</span></a></div>' . "\n";
    }

    // send Message
/*
    if ($visitorUser && $user->id != $visitorUser->id) {
        echo '<br>' . "\n";
        echo '<div class="sendMessageLink"><a href="javascript:showSendMessagePopup(' . $user->id . ');"><img border="0" src="../Images/Mail_Icon.png">&nbsp;Send message to ' . escape($user->name) . '</a></div>' . "\n";
    }
*/

    // send Message
    if ($visitorUser && $user->id != $visitorUser->id) {
        echo '<br>' . "\n";
        echo '<div class="sendMessageLink"><a href="sendMessage.php?raid=' . $user->id . '"><img border="0" src="../Images/Mail_Icon.png">&nbsp;Send message to ' . escape($user->name) . '</a></div>' . "\n";
    }



?>

                            </div> <!-- userInfoLeft -->

                            <div id="userInfoMiddle">

                                <h1><?php echo escape($user->name); ?></h1>
                                <br/>




<?php


    // artist info
    if ($user->artist_info) {

        echo '<h2>Artist/Band information:</h2><p>' . "\n";
        echo escape($user->artist_info) . "\n";
        echo '</p><br/>' . "\n";

    }

    // additional info
    if ($user->additional_info) {
        echo '<h2>Additional information:</h2><p>' . "\n";
        echo escape($user->additional_info) . "\n";
        echo '</p><br/>' . "\n";
    }


    // collaborators
    $collaborators = AudioTrackUserVisibility::fetch_all_collaboration_users_of_user_id($user->id, 10); // attention: if the limit of 10 is changed, the code below must be changed as well (row processing code and colspans)
    if (count($collaborators) > 0) {
        echo '<br><br><h2>' . escape($user->name) . '\'s friends:</h2>' . "\n";
        echo '<table>';
        // row 1
        echo '<tr>';
        for ($i = 0; $i < 5; $i++) {
            echo '<td>';
            if (isset($collaborators[$i])) {
                echo '<a href="userInfo.php?aid=' . $collaborators[$i]->collaborating_user_id . '" target="_blank">';
                echo getUserImageHtml($collaborators[$i]->user_image_filename, $collaborators[$i]->user_name, 'tiny');
                echo '</a>';

            } else {
                echo '&nbsp;';
            }
            echo '</td>' . "\n";
        }
        echo '</tr>' . "\n";

        // row 2
        echo '<tr>';
        for ($i = 5; $i < 10; $i++) {
            echo '<td>';
            if (isset($collaborators[$i])) {
                echo '<a href="userInfo.php?aid=' . $collaborators[$i]->collaborating_user_id . '" target="_blank">';
                echo getUserImageHtml($collaborators[$i]->user_image_filename, $collaborators[$i]->user_name, 'tiny');
                echo '</a>';

            } else {
                echo '&nbsp;';
            }
            echo '</td>' . "\n";
        }
        echo '</tr>' . "\n";

        if (count($collaborators) > 10) {
            echo '<tr><td colspan="5">' . "\n";
            echo '... and some more. <div class="sendMessageLink"><a href="javascript:showCollaborationUsersPopup();" target="_blank"></div>';
            echo 'See all.';
            echo '</a>';
            echo '</td></tr>' . "\n";
        }
        echo '</table' . "\n";
    }

?>





                            </div> <!-- userInfoMiddle -->

                            <div id="userInfoRight">




                                <div id="userInfoWidget">
                                  <div id="inner">
	                                <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="300" height="400" id="NTWidget" align="middle">
	                                  <param name="allowScriptAccess" value="always" />
	                                  <param name="allowFullScreen" value="false" />
	                                  <param name="movie" value="../Widget/PpWidget.swf?aid=<?php echo $user->id; ?>" />
	                                  <param name="loop" value="false" />
	                                  <param name="quality" value="high" />
	                                  <param name="wmode" value="transparent" />
	                                  <param name="bgcolor" value="#ffffff" />
	                                  <embed src="../Widget/PpWidget.swf?aid=<?php echo $user->id; ?>" loop="false" quality="high" wmode="transparent" bgcolor="#ffffff" width="300" height="400" name="NTWidget" align="middle" allowScriptAccess="always" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
	                                </object>
	                              </div>
                                </div>




                            </div><!-- userInfoRight -->


                            <div class="clear"></div>


      </div>
      </div> <!-- userInfoContainer -->


      </div>



      <div style="clear:both;"></div>

      <div id="trackInfoWrapper">


		<div class="trackInfoColumn">
			<div class="trackInfoTitle">My songs</div>
			<div class="trackInfoContent">

<?php

$originals         = AudioTrack::fetch_all_originals_of_user_id_from_to($user_id, 0, 99999999, false, false, $visitorUserId);
$remixes           = AudioTrack::fetch_all_remixes_of_user_id_from_to($user_id, 0, 99999999, false, false, $visitorUserId);
$remixed_by_others = AudioTrack::fetch_all_remixes_for_originating_user_id_from_to($user_id, 0, 99999999, false, false, $visitorUserId);

$rows = max(max(count($originals), count($remixes)), count($remixed_by_others));

for ($i = 0; $i < $rows; $i++) {
    echo '<div><a href="javascript:reloadDataInWidget(' . $user_id . ', ' . $originals[$i]->id . ');">'         . escape($originals[$i]->title)         . '</a></div>' . "\n";
}

?>

			</div> <!-- trackInfoContent -->
		</div> <!-- trackInfoColumn -->

		<div class="trackInfoColumn">
			<div class="trackInfoTitle">My remixes</div>
			<div class="trackInfoContent">

<?php

$rows = max(max(count($originals), count($remixes)), count($remixed_by_others));

for ($i = 0; $i < $rows; $i++) {
    echo '<div><a href="javascript:reloadDataInWidget(' . $user_id . ', ' . $remixes[$i]->id . ');">'           . escape($remixes[$i]->title)           . '</a></div>' . "\n";
}

?>

			</div> <!-- trackInfoContent -->
		</div> <!-- trackInfoColumn -->

		<div class="trackInfoColumn last">
			<div class="trackInfoTitle">Remixed from other artists</div>
			<div class="trackInfoContent">

<?php

$rows = max(max(count($originals), count($remixes)), count($remixed_by_others));

for ($i = 0; $i < $rows; $i++) {
    echo '<div><a href="javascript:reloadDataInWidget(' . $user_id . ', ' . $remixed_by_others[$i]->id . ');">' . escape($remixed_by_others[$i]->title) . '</a></div>' . "\n";
}

?>

                        </div> <!-- trackInfoContent -->
                    </div> <!-- trackInfoColumn -->
                    <div class="clear"></div>

                </div> <!-- trackInfoWrapper -->
            </div>	<!-- pageMainContent -->

            <? include ("footer.php"); ?>

        </div> <!-- bodyWrapper -->


    <div id="sendMessageOverlay">
        <div id="sendMessageWrapper"></div>
    </div> <!-- sendMessageOverlay -->

    <?php writeGoogleAnalyticsStuff(); ?>

    </body>
</html>
