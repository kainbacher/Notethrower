<?php

include_once('../Includes/Init.php');

include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Artist.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/AudioTrackArtistVisibility.php');

// let's see if the visiting user is a logged in artist
$visitorArtistId = -1;
$visitorArtist = Artist::new_from_cookie();
if ($visitorArtist) {
    $visitorArtistId = $visitorArtist->id;
    $logger->info('visitor artist id: ' . $visitorArtistId);
}

$artist_id = get_numeric_param('aid');

$artist = Artist::fetch_for_id($artist_id);
if (!$artist) {
    show_fatal_error_and_exit('no artist found for id: ' . $artist_id);
}

writePageDoctype();

?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <?php writePageMetaTags(); ?>
    <title><?php writePageTitle(); ?></title>
    <link rel="stylesheet" href="../Styles/main.css" type="text/css">

    <script type="text/javascript" src="../Javascripts/jquery-1.3.2.min.js"></script>
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

function showCollaborationArtistsPopup() {
    window.open('showCollaborationArtists.php?aid=<?php echo $artist->id; ?>', 'NT_COLLABORATORS', 'scrollbars=yes,resizable=yes,status=0,width=400,height=600');
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

                <div class="horizontalMenu">
                    <ul>
                        <li><a href="index.php">Startpage</a></li>
                    </ul>
                </div>

                <div id="artistInfoContainer">

                    <div id="artistInfoDiv">
                        <div id="container">


                            <div id="artistInfoLeft">

<?php
    // artist image
    if ($artist->image_filename) {
        $artistImg    = $GLOBALS['ARTIST_IMAGE_BASE_PATH'] . $artist->image_filename;
        $artistImgUrl = $GLOBALS['ARTIST_IMAGE_BASE_URL']  . $artist->image_filename;

        $logger->info('artist img: ' . $artistImg);

        if (file_exists($artistImg)) {
      	    echo '<img src="' . $artistImgUrl . '" width="200">';

        } else {
      	    echo '<img src="' . $GLOBALS['BASE_URL'] . 'Images/no_artist_image.png" width="200">';
        }

    } else {
        echo '<img src="' . $GLOBALS['BASE_URL'] . 'Images/no_artist_image.png" width="200">';
    }

    echo '<br><br>' . "\n";

    // webpage url
    if ($artist->webpage_url) {
        $webpageUrl = $artist->webpage_url;
        if (substr($artist->webpage_url, 0, 7) != 'http://' && substr($artist->webpage_url, 0, 8) != 'https://') {
            $webpageUrl = 'http://' . $artist->webpage_url;
        }
        echo '<div class="weblink"><a href="' . escape($webpageUrl) . '" target="_blank"><img src="../Images/icon_weblink.png" alt="icon_weblink" width="16" height="16"/><span class="weblinkText">' . escape($artist->webpage_url) . '</span></a></div>' . "\n";
    }

    // send Message
/*
    if ($visitorArtist && $artist->id != $visitorArtist->id) {
        echo '<br>' . "\n";
        echo '<div class="sendMessageLink"><a href="javascript:showSendMessagePopup(' . $artist->id . ');"><img border="0" src="../Images/Mail_Icon.png">&nbsp;Send message to ' . escape($artist->name) . '</a></div>' . "\n";
    }
*/

    // send Message
    if ($visitorArtist && $artist->id != $visitorArtist->id) {
        echo '<br>' . "\n";
        echo '<div class="sendMessageLink"><a href="sendMessage.php?raid=' . $artist->id . '"><img border="0" src="../Images/Mail_Icon.png">&nbsp;Send message to ' . escape($artist->name) . '</a></div>' . "\n";
    }



?>

                            </div> <!-- artistInfoLeft -->

                            <div id="artistInfoMiddle">

                                <h1><?php echo escape($artist->name); ?></h1>
                                <br/>




<?php


    // artist info
    if ($artist->artist_info) {

        echo '<h2>Artist/Band information:</h2><p>' . "\n";
        echo escape($artist->artist_info) . "\n";
        echo '</p><br/>' . "\n";

    }

    // additional info
    if ($artist->additional_info) {
        echo '<h2>Additional information:</h2><p>' . "\n";
        echo escape($artist->additional_info) . "\n";
        echo '</p><br/>' . "\n";
    }


    // collaborators
    $collaborators = AudioTrackArtistVisibility::fetch_all_collaboration_artists_of_artist_id($artist->id, 10); // attention: if the limit of 10 is changed, the code below must be changed as well (row processing code and colspans)
    if (count($collaborators) > 0) {
        echo '<br><br><h2>' . escape($artist->name) . '\'s friends:</h2>' . "\n";
        echo '<table>';
        // row 1
        echo '<tr>';
        for ($i = 0; $i < 5; $i++) {
            echo '<td>';
            if (isset($collaborators[$i])) {
                echo '<a href="artistInfo.php?aid=' . $collaborators[$i]->collaborating_artist_id . '" target="_blank">';
                echo getArtistImageHtml($collaborators[$i]->artist_image_filename, $collaborators[$i]->artist_name, 'tiny');
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
                echo '<a href="artistInfo.php?aid=' . $collaborators[$i]->collaborating_artist_id . '" target="_blank">';
                echo getArtistImageHtml($collaborators[$i]->artist_image_filename, $collaborators[$i]->artist_name, 'tiny');
                echo '</a>';

            } else {
                echo '&nbsp;';
            }
            echo '</td>' . "\n";
        }
        echo '</tr>' . "\n";

        if (count($collaborators) > 10) {
            echo '<tr><td colspan="5">' . "\n";
            echo '... and some more. <div class="sendMessageLink"><a href="javascript:showCollaborationArtistsPopup();" target="_blank"></div>';
            echo 'See all.';
            echo '</a>';
            echo '</td></tr>' . "\n";
        }
        echo '</table' . "\n";
    }

?>





                            </div> <!-- artistInfoMiddle -->

                            <div id="artistInfoRight">




                                <div id="artistInfoWidget">
                                  <div id="inner">
	                                <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="300" height="400" id="NTWidget" align="middle">
	                                  <param name="allowScriptAccess" value="always" />
	                                  <param name="allowFullScreen" value="false" />
	                                  <param name="movie" value="../Widget/PpWidget.swf?aid=<?php echo $artist->id; ?>" />
	                                  <param name="loop" value="false" />
	                                  <param name="quality" value="high" />
	                                  <param name="wmode" value="transparent" />
	                                  <param name="bgcolor" value="#ffffff" />
	                                  <embed src="../Widget/PpWidget.swf?aid=<?php echo $artist->id; ?>" loop="false" quality="high" wmode="transparent" bgcolor="#ffffff" width="300" height="400" name="NTWidget" align="middle" allowScriptAccess="always" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
	                                </object>
	                              </div>
                                </div>




                            </div><!-- artistInfoRight -->


                            <div class="clear"></div>


      </div>
      </div> <!-- artistInfoContainer -->


      </div>



      <div style="clear:both;"></div>

      <div id="trackInfoWrapper">


		<div class="trackInfoColumn">
			<div class="trackInfoTitle">My songs</div>
			<div class="trackInfoContent">

<?php

$originals         = AudioTrack::fetch_all_originals_of_artist_id_from_to($artist_id, 0, 99999999, false, false, $visitorArtistId);
$remixes           = AudioTrack::fetch_all_remixes_of_artist_id_from_to($artist_id, 0, 99999999, false, false, $visitorArtistId);
$remixed_by_others = AudioTrack::fetch_all_remixes_for_originating_artist_id_from_to($artist_id, 0, 99999999, false, false, $visitorArtistId);

$rows = max(max(count($originals), count($remixes)), count($remixed_by_others));

for ($i = 0; $i < $rows; $i++) {
    echo '<div><a href="javascript:reloadDataInWidget(' . $artist_id . ', ' . $originals[$i]->id . ');">'         . escape($originals[$i]->title)         . '</a></div>' . "\n";
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
    echo '<div><a href="javascript:reloadDataInWidget(' . $artist_id . ', ' . $remixes[$i]->id . ');">'           . escape($remixes[$i]->title)           . '</a></div>' . "\n";
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
    echo '<div><a href="javascript:reloadDataInWidget(' . $artist_id . ', ' . $remixed_by_others[$i]->id . ');">' . escape($remixed_by_others[$i]->title) . '</a></div>' . "\n";
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
