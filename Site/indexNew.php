<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/News.php');

$loginErrorMsg = '';

$visitorUserId = -1;

$userIsLoggedIn = false;
$user = User::new_from_cookie();
if ($user) {
    $visitorUserId = $user->id;
    $logger->info('visitor user id: ' . $visitorUserId);

    $userIsLoggedIn = true;
    $logger->info('user is logged in');

} else {
    $logger->info('user is NOT logged in');

    if (get_param('action') == 'login') {
        $logger->info('login request received');
        if (get_param('username') && get_param('password')) {
            $user = User::fetch_for_username_password(get_param('username'), get_param('password'));
            if ($user && $user->status == 'active') {
                $user->doLogin();
                $logger->info('login successful, reloading page to set cookie');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;

            } else {
                $loginErrorMsg = 'Login failed! Please try again.';
                $logger->info('login failed');
            }

        } else {
            $loginErrorMsg = 'Please provide a username and password!';
            $logger->info('username and/or password missing');
        }
    }
}

$trackCount = AudioTrack::count_all(false, false, $visitorUserId);
$logger->info('track count: ' . $trackCount);

$newsCount = News::count_all();

writePageDoctype();

?>
<html>
  <head>
    <? include ("headerData.php"); ?>
    
    <link rel="stylesheet" href="../Styles/ajaxpagination.css" type="text/css">
        
    <script type="text/javascript">
        
// the track id, if it a tid is specified as query parameter
// (deep link to load a song in the widget)
// we need the track id and the user id to load the widget
// so one have to use ?tid=<trackid>&taid=<arstistIdOfTheTrack> 
// to load the specific track in the widget
var tid  = -1;
var taid = -1;
var regex = new RegExp("[\\?&]tid=([^&#]*)");
var results = regex.exec(window.location.href);
if( results != null ) {
    tid = results[0].substring(results[0].lastIndexOf("=")+1);
}
// the id of the composer of the track 
regex = new RegExp("[\\?&]taid=([^&#]*)");
results = regex.exec(window.location.href);
if( results != null ) {
    taid = results[0].substring(results[0].lastIndexOf("=")+1);
}


// default mode is mostRecent
var mode = 'mostRecent';

// the count of the tracks
var tracksCount = <?php echo $trackCount; ?>;

// count of pages
var pageCount = Math.ceil(tracksCount / 16);

// the json object for the pagination
var trackLinks = {pages: [], selectedpage: 0};

// method that adds the pages to the trackLinks json object
// based on how many pages have to be displayed
function initTrackLinks() {
	for (var i=0; i < pageCount; i++) {
		trackLinks.pages[i] = 'tracksSnippet.php?page=' + (i+1) + '&mode=' + mode;
	}
}

// inits the tracksPagination
var tracksPagingInstance;
function initTracksPagination() {
	initTrackLinks();
	tracksPagingInstance = new ajaxpageclass.createBook(trackLinks, "tracksContent", ["pagination"]);
	tracksPagingInstance.selectpage(0);
	tracksPagingInstance.paginatepersist = false;
}

function toggleMode() {
	if (mode == 'mostRecent') {
		mode = 'mostDownloaded';
		$('#trackGridHeadline').html('Most downloaded tracks:');
		$('#trackGridHeadlineControls').html('<a href="#" onclick="toggleMode()">Show most recent</a>');
		initTracksPagination();
	} else {
		mode = 'mostRecent';
		$('#trackGridHeadline').html('Most recent tracks:');
		$('#trackGridHeadlineControls').html('<a href="#" onclick="toggleMode()">Show most downloaded</a>');
		initTracksPagination();
	}
}

function featuredTrackClicked(tid) {
    var showHideMode = ''; // for animations, etc.
    $('#trackGrid').hide(showHideMode);
    $('#trackGridHeadlineContainer').hide(showHideMode);

    // load track details html
    $.ajax({
        type: 'POST',
        url: 'getTrackDetails.php',
        data: 'tid=' + tid,
        dataType: 'html',
        cache: false,
        timeout: 10000, // 10 seconds
        beforeSend: function(xmlHttpRequest) {
            $('#chosenTrackDetails').html('<table><tr><td valign="middle"><!-- FIXME - put loading anim gif here --></td><td valign="middle">' +
                    'Loading ...</td></tr></table>');
        },
        error: function(xmlHttpRequest, textStatus, errorThrown) {
            $('#chosenTrackDetails').html('<b>ERROR:<br>' +
                    textStatus + '<br>' +
                    errorThrown + '</b>');
        },
        success: function(html) {
            $('#chosenTrackDetails').html(html);
        }
    });

    $('#chosenTrackDetailsContainer').show(showHideMode);
    $('#chosenTrackHeadlineContainer').show(showHideMode);
    $('#paginationWrapper').hide(showHideMode);
}

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

function showSendMessagePopup(raid) {
    window.open('sendMessage.php?raid=' + raid, 'NT_SEND_MESSAGE', 'scrollbars=yes,resizable=yes,status=0,width=600,height=400');
}

function showCollaborationUsersPopup() {
    window.open('showCollaborationUsers.php?aid=<?php echo $user->id; ?>', 'NT_COLLABORATORS', 'scrollbars=yes,resizable=yes,status=0,width=400,height=600');
}

function showLogin() {
    //document.getElementById("loginFormDiv").style.display="block";
    $('#loginFormDiv').toggle('fast');
}

/* documentready fuction */
/* This is needed to support linking directly to a song played by the widget */
/* ---------------------------------------------------------------------- */

$(document).ready(function(){
    
    if (tid != -1 && taid != -1) {
        featuredTrackClicked(tid);
        reloadDataInWidget(tid, taid);
    }
    
});


    </script>
	</head>
	<body>
		<div id="bodyWrapperStart">
            <? include ("pageHeader.php"); ?>
            <? include ("mainMenu.php"); ?>

            <div class="container">
                
                <div class="span-16">

                    <h1>Latest Tacks</h1>
                    <div class="trackList">
                    
                        <div class="span-16 trackListItem">
                            <div class="span-2">
                                <img src="../Images/no_artist_image.png" height="25" />
                            </div>
                            <div class="span-6">
                                Linkin Park
                            </div>
                            <div class="span-6">
                                Track Name
                            </div>
                            <div class="span-2 last">
                                <a href="#" class="trackListButton trackListPlay">&nbsp;</a>
                                <a href="#" class="trackListButton trackListStar">&nbsp;</a>
                            </div>
                        </div>

                        <div class="span-16 trackListItem">
                            <div class="span-2">
                                <img src="../Images/no_artist_image.png" height="25" />
                            </div>
                            <div class="span-6">
                                Guns'n'Roses
                            </div>
                            <div class="span-6">
                                Track Name
                            </div>
                            <div class="span-2 last">
                                <a href="#" class="trackListButton trackListPlay">&nbsp;</a>
                                <a href="#" class="trackListButton trackListStar">&nbsp;</a>
                            </div>
                        </div>

                        <div class="span-16 trackListItem">
                            <div class="span-2">
                                <img src="../Images/no_artist_image.png" height="25" />
                            </div>
                            <div class="span-6">
                                Guns'n'Roses
                            </div>
                            <div class="span-6">
                                Track Name
                            </div>
                            <div class="span-2 last">
                                <a href="#" class="trackListButton trackListPlay">&nbsp;</a>
                                <a href="#" class="trackListButton trackListStar">&nbsp;</a>
                            </div>
                        </div>

                        <div class="span-16 trackListItem">
                            <div class="span-2">
                                <img src="../Images/no_artist_image.png" height="25" />
                            </div>
                            <div class="span-6">
                                Guns'n'Roses
                            </div>
                            <div class="span-6">
                                Track Name
                            </div>
                            <div class="span-2 last">
                                <a href="#" class="trackListButton trackListPlay">&nbsp;</a>
                                <a href="#" class="trackListButton trackListStar">&nbsp;</a>
                            </div>
                        </div>

                        <div class="span-16 trackListItem">
                            <div class="span-2">
                                <img src="../Images/no_artist_image.png" height="25" />
                            </div>
                            <div class="span-6">
                                Guns'n'Roses
                            </div>
                            <div class="span-6">
                                Track Name
                            </div>
                            <div class="span-2 last">
                                <a href="#" class="trackListButton trackListPlay">&nbsp;</a>
                                <a href="#" class="trackListButton trackListStar">&nbsp;</a>
                            </div>
                        </div>

                        <div><a href="#">more Traks</a></div>
                        <br />
                        <br />
                    </div>

                    <h1>Top Traks</h1>
                    <div class="trackList">
                    
                        <div class="span-16 trackListItem">
                            <div class="span-2">
                                <img src="../Images/no_artist_image.png" height="25" />
                            </div>
                            <div class="span-6">
                                Linkin Park
                            </div>
                            <div class="span-6">
                                Track Name
                            </div>
                            <div class="span-2 last">
                                <a href="#" class="trackListButton trackListPlay">&nbsp;</a>
                                <a href="#" class="trackListButton trackListStar">&nbsp;</a>
                            </div>
                        </div>

                        <div class="span-16 trackListItem">
                            <div class="span-2">
                                <img src="../Images/no_artist_image.png" height="25" />
                            </div>
                            <div class="span-6">
                                Guns'n'Roses
                            </div>
                            <div class="span-6">
                                Track Name
                            </div>
                            <div class="span-2 last">
                                <a href="#" class="trackListButton trackListPlay">&nbsp;</a>
                                <a href="#" class="trackListButton trackListStar">&nbsp;</a>
                            </div>
                        </div>
 
                         <div class="span-16 trackListItem">
                            <div class="span-2">
                                <img src="../Images/no_artist_image.png" height="25" />
                            </div>
                            <div class="span-6">
                                Guns'n'Roses
                            </div>
                            <div class="span-6">
                                Track Name
                            </div>
                            <div class="span-2 last">
                                <a href="#" class="trackListButton trackListPlay">&nbsp;</a>
                                <a href="#" class="trackListButton trackListStar">&nbsp;</a>
                            </div>
                        </div>

                        <div class="span-16 trackListItem">
                            <div class="span-2">
                                <img src="../Images/no_artist_image.png" height="25" />
                            </div>
                            <div class="span-6">
                                Guns'n'Roses
                            </div>
                            <div class="span-6">
                                Track Name
                            </div>
                            <div class="span-2 last">
                                <a href="#" class="trackListButton trackListPlay">&nbsp;</a>
                                <a href="#" class="trackListButton trackListStar">&nbsp;</a>
                            </div>
                        </div>

                        <div><a href="#">more Traks</a></div>
                        <br />
                        <br />

                    </div>
                
                </div>
                
                <div class="span-8 last">
                    <div class="box-grey">
                        player goes here
                    </div>
                    
                    <div class="box-grey">
                        <h2>Discover new music support the artists and be a fan.</h2>
                        
                        <p>
                            <strong>browse by genre:</strong><br />
                            <a href="#">hip-hop</a> 
                            <a href="#">rock</a> 
                            <a href="#">electro</a> 
                            <a href="#">rap</a> 
                            <a href="#">alternative</a> 
                            <a href="#">funk</a> 
                            <a href="#">jazz</a> 
                            <a href="#">soul</a> 
                            <a href="#">folk</a> 
                            <a href="#">r&b</a> 
                            <a href="#">noise</a> 
                            <a href="#">dance</a> 
                            <a href="#">metal</a> 
                            <a href="#">indie</a> 
                            <a href="#">instrumental</a> 
                        </p>
                    </div>
                    
                    <div class="box-blue">
                        <h2>Wall of Fan</h2>
                        
                        <ul class="fanList">
                            <li>
                                 <img src="../Images/no_artist_image.png" height="25" />
                                 Joe Benso
                            </li>
                            <li>
                                 <img src="../Images/no_artist_image.png" height="25" />
                                 Franz Ferdinant
                            </li>
                            <li>
                                 <img src="../Images/no_artist_image.png" height="25" />
                                 Napoleon Ponaparte
                            </li>
                            <li>
                                 <img src="../Images/no_artist_image.png" height="25" />
                                 Max Mustermann
                            </li>
                            <li>
                                 <img src="../Images/no_artist_image.png" height="25" />
                                 Indiana Jones
                            </li>
                        </ul>
                        
                    </div>
                    
                </div>
            
            </div>




<!--
    <div id="contentTop">
		<div id="trackGridWrapper">
			<div id="trackGridHeadlineContainer">
        		<div id="trackGridHeadline">Most recent tracks:</div>
        		<div><a class="button blue" href="javascript:toggleMode();">Show most downloaded</a></div>
      			<div class="clear"></div>
      		</div>

      		<div id="chosenTrackHeadlineContainer" style="display:none">
        		<div id="chosenTrackHeadline">Track details:</div>
      		</div>

      		<div id="trackGrid"><div id="tracksContent"></div></div>

			<div id="paginationWrapper">
        		<div id="pagination"> </div>
      			<script type="text/javascript">initTracksPagination();</script>
			</div>

      		<div id="chosenTrackDetailsContainer" style="display:none">
        		<div id="chosenTrackDetails">Some track details<br></div>
        		<div class="clear"></div>
      		</div>
		</div>
	</div>
-->


	<? include ("footer.php"); ?>


		</div> <!-- bodyWrapperStart -->

		<?php writeGoogleAnalyticsStuff(); ?>
	</body>
</html>
