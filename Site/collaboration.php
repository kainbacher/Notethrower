<?php

include_once('../Includes/Init.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Message.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/User.php');

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

ensureUserIsLoggedIn($user);

$action = get_param('action');
if ($action == 'delete') {
    $mid = get_numeric_param('mid');
    if ($mid) {
        $msg = Message::fetch_for_id($mid);

        if (!$msg || !$msg->id) {
            show_fatal_error_and_exit('Message with ID ' . $mid . ' not found!');
        }

        ensureMessageBelongsToUser($msg, $user);

        $msg->deleted = true;
        $msg->save();
    }
}

$privTrackCount = Project::count_all_private_projects_the_user_can_access($user->id);
$logger->info('private track count: ' . $privTrackCount);

writePageDoctype();

?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <?php writePageMetaTags(); ?>
    <title><?php writePageTitle(); ?></title>
    <link rel="stylesheet" href="../Styles/main.css" type="text/css">
    <link rel="stylesheet" href="../Styles/ajaxpagination.css" type="text/css">

    <script type="text/javascript" src="../Javascripts/jquery-1.6.1.min.js"></script>
    <script type="text/javascript" src="../Javascripts/jquery.main.js"></script>

    <script src="../Javascripts/ajaxpagination.js" type="text/javascript">

    /***********************************************
    * Ajax Pagination script- (c) Dynamic Drive DHTML code library (www.dynamicdrive.com)
    * This notice MUST stay intact for legal use
    * Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
    ***********************************************/

    </script>
    <script type="text/javascript">

// the count of the tracks
var tracksCount = <?php echo $privTrackCount; ?>;

// count of pages
var pageCount = Math.ceil(tracksCount / 16);

// the json object for the pagination
var trackLinks = {pages: [], selectedpage: 0};

// method that adds the pages to the trackLinks json object
// based on how many pages have to be displayed
function initTrackLinks() {
	for (var i=0; i < pageCount; i++) {
		trackLinks.pages[i] = 'tracksSnippet.php?page=' + (i+1) + '&mode=privateTracks';
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

function featuredTrackClicked(tid) {
    var showHideMode = '';
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
            $('#chosenTrackDetails').html('<table><tr><td valign="middle"><!-- TODO - put loading anim gif here --></td><td valign="middle">' +
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

function showSendMessagePopup(raid, replyToMsgId) {
    window.open('sendMessage.php' +
                '?raid=' + raid +
                '&replyToMsgId=' + replyToMsgId,
                'NT_SEND_MESSAGE', 'scrollbars=yes,resizable=yes,status=0,width=600,height=400');
}

    </script>
  </head>
  <body>

    <div id="bodyWrapper">
            <? include ("pageHeader.php"); ?>
            <? include ("mainMenu.php"); ?>

    <div class="container">


        <div id="mainColumnLeft">
            <div id="trackListDiv">



      <div id="trackGridHeadlineContainer">
        <div id="trackGridHeadline">
        	Private tracks shared with you:
        </div>
        <div id="trackGridHeadlineControls">
        </div>
      </div>

      <div id="chosenTrackHeadlineContainer" style="display:none">
        <div id="chosenTrackHeadline">
        </div>
      </div>

      <div class="clear"></div>

      <div id="trackGrid">
      	<div id="tracksContent"> </div>
      	<div id="pagination"> </div>

      	<script type="text/javascript">
      		initTracksPagination();
      	</script>

        <div style="clear:both"></div>

      </div>

      <div id="chosenTrackDetailsContainer" style="display:none">
        <div id="chosenTrackDetails" style="overflow:auto">
          ...<br>
        </div>

        <div class="clear"></div>

      </div>



      <a name="Inbox"></a>
      <h1>Messages</h1><br>

<?php

$msgs = Message::fetch_all_for_recipient_user_id($user->id);
if (count($msgs) > 0) {
    echo '<table class="messageInboxTable">' . "\n";
    echo '<tr>' .
         '<td class="tableHeading" style="width:20%"><b>From:</b></td>' .
         '<td class="tableHeading" style="width:20%"><b>Subject:</b></td>' .
         '<td class="tableHeading" style="width:55%"><b>Text:</b></td>' .
         '<td class="tableHeading" style="width:5%"><b>Action:</b></td>' .
         '</tr>' . "\n";

    foreach ($msgs as $msg) {
        $b1 = $msg->marked_as_read ? '' : '<b>';
        $b2 = $msg->marked_as_read ? '' : '</b>';

        echo '<tr>' . "\n";
        echo '<td>' . $b1 . escape($msg->sender_user_name) . $b2 . '</td>' . "\n";
        echo '<td>' . $b1 . escape($msg->subject) . $b2 . '</td>' . "\n";
        echo '<td>' . escape($msg->text) . '</td>' . "\n";
        echo '<td>' .
             '<a class="buttonsmall" href="javascript:showSendMessagePopup(' .
                 $msg->sender_user_id . ',' .
                 $msg->id .
             ');">Reply</a>' .
             '' .
             '<a class="buttonsmall" href="' . $_SERVER['PHP_SELF'] . '?action=delete&mid=' . $msg->id . '">Delete</a>' .
             '</td>' . "\n";
        echo '</tr>' . "\n";
    }

    echo '</table>' . "\n";

    Message::mark_all_as_read_for_recipient_user_id($user->id);

} else {
    echo 'No new messages.';
}

?>



    </div> <!-- trackListDiv -->





        </div> <!-- mainColumnLeft -->

        <div id="mainColumnRight">


      <div id="widgetSearch">
<?php

$newestPrivateTrack = Project::fetch_all_private_projects_the_user_can_access(0, 0, $user->id);
if (count($newestPrivateTrack) > 0) {
    $aidForWidget = $newestPrivateTrack[0]->user_id;
} else {
    $aidForWidget = $user->id;
}

?>
	      <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="300" height="400" id="NTWidget" align="middle">
	        <param name="allowScriptAccess" value="always" />
	        <param name="allowFullScreen" value="false" />
	        <param name="movie" value="../Widget/PpWidget.swf?aid=<?php echo $aidForWidget; ?>" />
	        <param name="loop" value="false" />
	        <param name="quality" value="high" />
	        <param name="wmode" value="transparent" />
	        <param name="bgcolor" value="#ffffff" />
	        <embed src="../Widget/PpWidget.swf?aid=<?php echo $aidForWidget; ?>" loop="false" quality="high" wmode="transparent" bgcolor="#ffffff" width="300" height="400" name="NTWidget" align="middle" allowScriptAccess="always" allowFullScreen="false" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
	      </object>

      </div>

      <div style="clear:both"></div>

      <br/>
      <br/>

    </div>


    </div> <!-- mainColumnRight -->

    <div class="clear"></div>
    <br/>

    <? include ("footer.php"); ?>

    </div> <!-- bodyWrapper -->

    <?php writeGoogleAnalyticsStuff(); ?>

  </body>
</html>
