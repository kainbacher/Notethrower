<?php

include_once('../Includes/Init.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/AudioTrackFile.php');

$user = User::new_from_cookie();
ensureUserIsLoggedIn($user);

$msg = get_param('msg');

$newbordTrackIdList = AudioTrack::fetchAllNewbornTrackIdsForUserId($user->id);
foreach ($newbordTrackIdList as $nbtid) {
    AudioTrack::delete_with_id($nbtid);
}

$originalTracks = AudioTrack::fetch_all_originals_of_user_id_from_to($user->id, 0, 999999999, true, true, -1);
$remixedTracks  = AudioTrack::fetch_all_remixes_of_user_id_from_to($user->id, 0, 999999999, true, true, -1);

writePageDoctype();

?>
<html>
  	<head>
		<? include ("headerData.php"); ?>
		
		<script type="text/javascript">
 		    function callPublish(msg, attachment, action_link) {
 		        FB.ensureInit(function () {
 		            FB.Connect.streamPublish('', attachment, action_link);
 		        });
 		    }
 		</script>
 		<!-- Facebook stuff end -->
 		
 		<script type="text/javascript">
 		    
 		    function share(tid, aid) {
 		        var href = 'http://www.notethrower.com/NT/Site/index.php?tid=' + tid + '&taid=' + aid;
 		        callPublish('',{'name':'I uploaded a new Song','href':href,'description':'I just uploaded a new Song to NoteThrower.  Listen for free, or collaborate with me on new tracks and even license our music together!','media':[{'type':'image','src':'http://profile.ak.fbcdn.net/object2/1043/112/q170458041694_1270.jpg','href':href}]},[{'text':'play the song','href':href}])
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
                <li><a href="track.php?action=create">Create new audio track or full song</a></li>
            </ul>
        </div>


    <div id="mainColumnLeft">



      <div id="trackListDivStart"></div>
      <div id="trackListDiv"><div id="container">

<?php

if ($msg) echo '<span class="problemMessage">' . $msg . '</span><br><br>' . "\n";

?>

        <br/>
        <div>
            <div style="float:left; width:430px;">
                <h1>My original tracks:</h1><br>
            </div>
            
            <div style="float:right;">
                <a class="button" href="track.php?action=create">&raquo; Upload Music</a>
            </div>
            
            <div class="clear"></div>
        
        </div>
        
        <br/>
        <br/>
        
        <table class="trackListTable">
<?php

$colspan = 4;

$i = 0;
foreach ($originalTracks as $t) {
    $i++;
    echo '<tr class="standardRow' . ($i % 2 + 1) . '" onmouseover="this.className=\'highlightedRow\';" onmouseout="this.className=\'standardRow' . ($i % 2 + 1) . '\';">';
    echo '<td>' . escape($t->title) . '</td>';
    //echo '<td style="width:15%"><a href="track.php?action=toggleTrackState&tid=' . $t->id . '">' . ($t->status == 'active' ? 'Active' : 'Inactive') . '</a></td>';
    echo '<td style="width:15%">' . ($t->visibility == 'public' ? 'Public' : 'Private') . '</td>';
    echo '<td style="width:15%"><a href="track.php?action=edit&tid=' . $t->id . '">Edit track</a></td>';
    echo '<td style="width:15%"><a href="javascript:askForConfirmationAndRedirect(\'Do you really want to delete this track?\', \'' .
            escape_and_rewrite_single_quotes($t->title) . '\', \'track.php?action=delete&tid=' . $t->id . '\');">Delete track</a></td>';
    if ($t->visibility == 'public') {
        echo '<td style="vertical-align:middle"><a href="javascript:share(' . $t->id . ',' . $t->user_id .');"><img border="0" src="../Images/fb_share.png"></a></td>';
    } else {
        echo '<td>&nbsp;</td>';
    }
    echo '</tr>' . "\n";
}

if (count($originalTracks) == 0) {
    echo '<tr>';
    echo '<td colspan="' . $colspan . '">No original tracks found</td>';
    echo '</tr>' . "\n";
}

?>
        </table>
        <br><br>

        <h1>My remixes:</h1><br>
        <table class="trackListTable">
<?php

$i = 0;
foreach ($remixedTracks as $t) {
    $i++;
    echo '<tr class="standardRow' . ($i % 2 + 1) . '" onmouseover="this.className=\'highlightedRow\';" onmouseout="this.className=\'standardRow' . ($i % 2 + 1) . '\';">';
    echo '<td>' . escape($t->title) . '</td>';
    //echo '<td style="width:15%"><a href="track.php?action=toggleTrackState&tid=' . $t->id . '">' . ($t->status == 'active' ? 'Active' : 'Inactive') . '</a></td>';
    echo '<td style="width:15%">' . ($t->visibility == 'public' ? 'Public' : 'Private') . '</td>';
    echo '<td style="width:15%"><a href="track.php?action=edit&tid=' . $t->id . '">Edit track</a></td>';
    echo '<td style="width:15%"><a href="javascript:askForConfirmationAndRedirect(\'Do you really want to delete this track?\', \'' .
            escape_and_rewrite_single_quotes($t->title) . '\', \'track.php?action=delete&tid=' . $t->id . '\');">Delete track</a></td>';

    echo '</tr>' . "\n";
}

if (count($remixedTracks) == 0) {
    echo '<tr>';
    echo '<td colspan="' . $colspan . '">No remixes found</td>';
    echo '</tr>' . "\n";
}

?>

        </table>
        <br>
      		</div>
      		</div>
      			<div id="trackListDivEnd"></div>

      			<br><br><br><br>

    </div> <!-- mainColumnLeft -->

		<div id="mainColumnRight">
            <? include ("sidebar.php"); ?>
		</div> <!-- mainColumnRight -->

    <div class="clear"></div>

    </div> <!-- pageMainContent -->


        <? include ("footer.php"); ?>

		</div> <!-- bodyWrapper -->

		<?php writeGoogleAnalyticsStuff(); ?>
	</body>
</html>
