<?php

include_once('../Includes/Init.php');
include_once('../Includes/Paginator.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/News.php');
include_once('../Includes/DB/AudioTrackAttribute.php');

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

$containsTrackAttributes = AudioTrackAttribute::fetchShownFor('contains');
$needsTrackAttributes = AudioTrackAttribute::fetchShownFor('needs');



function showAttributesList($fieldName, &$trackAttributes, $othersFieldName) {
    
    echo '<table class="searchAttributes"><tr>';
    for($i = 1; $i <= sizeof($trackAttributes); $i++) {
        echo '<td><input type="checkbox" name="' . $fieldName . '" id="' . $fieldName . '" value="' . $trackAttributes[$i-1]->id . '"';
        echo '> ' . $trackAttributes[$i-1]->name . '</td>';

        // end of the row?
        if ((($i % 3) == 0) && ($i > 0)) {
            echo '</tr>';
        }
        // do we have to make a new row?
        if ((($i % 3) == 0) && ($i < sizeof($trackAttributes))) {
            echo "\n" . '<tr>';
        }
    }

    // handle the case where we have to add emty cells to complete the last row
    if (((sizeof($trackAttributes)) % 3) != 0) {
        $rest = 3 - ((sizeof($trackAttributes)) % 3);
        for ($i = 0; $i < $rest; ++$i) {
            echo '<td>&nbsp;</td>';
        }
            echo "\n" . '<tr>';
    }
    
    // display the "others" field in a own row, with only one td
    echo '<tr><td colspan="3">Others: ';
    echo '<input type="text" id="' . $othersFieldName . '"></td></tr>';
    
    echo '</table>';
}

function showGenreList() {
        
    echo '<table class="searchAttributes"><tr>';
    $i = 1;
    foreach($GLOBALS['GENRES'] as $key => $value) {
        echo '<td><input type="checkbox" name="genres" id="genres" value="' . $key . '"';
        echo '> ' . $value . '</td>';

        // end of the row?
        if ((($i % 3) == 0) && ($i > 0)) {
            echo '</tr>';
        }
        // do we have to make a new row?
        if ((($i % 3) == 0) && ($i < sizeof($GLOBALS['GENRES']))) {
            echo "\n" . '<tr>';
        }
        $i++;
    }

    // handle the case where we have to add emty cells to complete the last row
    if (((sizeof($GLOBALS['GENRES'])) % 3) != 0) {
        $rest = 3 - ((sizeof($GLOBALS['GENRES'])) % 3);
        for ($i = 0; $i < $rest; ++$i) {
            echo '<td>&nbsp;</td>';
        }
        echo '</tr>';
    }
        
    echo '</table>';    
}

$aidForWidget = null;

if ($userIsLoggedIn) {
    $aidForWidget = $user->id;

} else {
    if (count($tracks) > 0) {
        $aidForWidget = $tracks[0]->user_id;
    }
}

writePageDoctype();

?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <?php writePageMetaTags(); ?>
    <title><?php writePageTitle(); ?></title>
  
    <link rel="stylesheet" href="../Styles/buttons.css" type="text/css">  
    <link rel="stylesheet" href="../Styles/main.css" type="text/css">
    <link rel="stylesheet" href="../Styles/datatables.css" type="text/css">
    
    
    <script type="text/javascript" src="../Javascripts/jquery-1.6.1.min.js"></script>
    <script type="text/javascript" src="../Javascripts/jquery.main.js"></script>
    <script type="text/javascript" src="../Javascripts/jquery.dataTables.js"></script>

    <script type="text/javascript">

// stores values of the form elements when clicking on "search"
var genres = [];
var searchNeedsAttributIds = [];
var searchContainsAttributIds = [];
var userOrTitle = <?php echo '"' . get_param('s') . '"' ?>;
var visitorUserId = -1;
var containsOthers = '';
var needsOthers = '';

var dataTable;



$(document).ready(function() {
    
    // set the value of the search field to the value of userOrTitle
    // because it might be passed in as query string
    $('#userOrTitleField').val(userOrTitle);
    
	dataTable = $('#resultTable').dataTable({
	    "bFilter": false,
	    "bProcessing": true,
		"bServerSide": true,
		"bSort": false,
		"aoColumns": [
		    null,
            null,
            null,
            {"bVisible": false},
            null
        ],
		"sAjaxSource": "../Backend/searchTracks.php",
		"fnServerData": function ( sSource, aoData, fnCallback ) {
			aoData.push( { "name": "userOrTitle", "value": userOrTitle },
			             { "name": "needsAttributIds", "value": searchNeedsAttributIds },
			             { "name": "containsAttributIds", "value": searchContainsAttributIds },
			             { "name": "needsOthers", "value": needsOthers },
			             { "name": "containsOthers", "value": containsOthers },
			             { "name": "genres", "value": genres },
			             { "name": "vaid", "value": visitorUserId }
			              );
			$.getJSON( sSource, aoData, function (json) {
				fnCallback(json)
			} );
		},
		"fnRowCallback": function( nRow, aData, iDisplayIndex ) {
		    $('td:eq(0)', nRow).html('<img width="30" src="' + aData[0] + '">');
		    $('td:eq(2)', nRow).html('<a href="#" onclick="featuredTrackClicked(' + aData[3] + '); return false;">' + aData[2] + '</a>');
		    $('td:eq(3)', nRow).html('<a class="button grey" href="#" onclick="reloadDataInWidget(' + aData[4] + ',' + aData[3] + '); return false;">Play</a>');
		    return nRow;
        }
		});
} );

// function which is called if the user clicks on the "search" button"
// it stores the values of the form fields in variables and redraws the table
function doSearch() {
    searchNeedsAttributIds = [];
    $('#needsAttributIds:checked').each(function() {
        searchNeedsAttributIds.push($(this).val());
    });
    searchContainsAttributIds = [];
    $('#containsAttributIds:checked').each(function() {
        searchContainsAttributIds.push($(this).val());
    });
    genres = [];
    $('#genres:checked').each(function() {
        genres.push($(this).val());
    });
    userOrTitle = $('#userOrTitleField').val();
    visitorUserId = $('#visitorUserId').val();
    containsOthers = $('#containsOthers').val();
    needsOthers = $('#needsOthers').val();
    dataTable.fnPageChange('first');
    dataTable.fnDraw();
    return false;
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

function showLogin() {
    document.getElementById("loginLinkDiv").style.display="none";
    document.getElementById("loginFormDiv").style.display="block";
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

        <div id="searchWrapper">
            <div id="container">
            


      <div id="chosenTrackHeadlineContainer" style="display:none">
        <div id="chosenTrackHeadline">
        </div>
      </div>
  


      <div class="searchDetails">

        <h1>Search</h1>
        <br/>

            <table width="600">
                <tr>
                    <td>Artist name or track title contains:</td>
                    <td><input type="text" name="userOrTitleField" id="userOrTitleField">
                        <input type="hidden" name="visitorUserId" id="visitorUserId" value="<?php echo $visitorUserId ?>">
                    </td>
                </tr>
                
                <tr><td colspan="3"><div class="tableSpacer"></div></td></tr>
                
                
                <tr>
                    <td>Songs that need:</td>
                    <td>
                    <?php
                        showAttributesList('needsAttributIds', $needsTrackAttributes, 'needsOthers');
                    ?>
                    <td>
                </tr>
                
                <tr><td colspan="3"><div class="tableSpacer"></div></td></tr>
                
                <tr>
                    <td>Songs that contain:</td>
                    <td>
                     <?php
                        showAttributesList('containsAttributIds', $containsTrackAttributes, 'containsOthers');
                    ?>
                    <td>
                </tr>
                
                <tr><td colspan="3"><div class="tableSpacer"></div></td></tr>
                
                <tr>
                    <td>Genre:</td>
                    <td>
                     <?php
                        showGenreList();
                    ?>
                    <td>
                </tr>
                
                <tr><td colspan="3"><div class="tableSpacer"></div></td></tr>
                
                <tr>
                    <td>&nbsp;</td>
                    <td><input class="button blue" type="submit" value="submit" onClick="doSearch()"/>
                    <td>
                </tr>
            </table>

 

      </div>


            </div> <!-- container -->
        </div> <!-- searchWrapper -->


        <div style="height:18px;"></div>

        <div id="mainColumnLeft">
            <div id="trackListDiv">
            

           <table cellpadding="0" cellspacing="0" border="0" class="display" id="resultTable">
	           <thead>
		          <tr>
			         <th></th>
			         <th>Artist</th>
			         <th>Track Title</th>
			         <th>Details</th>
			         <th></th>
		          </tr>
	           </thead>
	           <tbody>

	           </tbody>
            </table>



      <div id="chosenTrackDetailsContainer" style="display:none">
        <div id="chosenTrackDetails">
          Some track details<br>
        </div>

        <div class="clear"></div>

      </div>



      <div style="clear:both"></div>


            </div>
        </div> <!-- mainColumnLeft -->

        <div id="mainColumnRight">




      <div id="searchWidget">

<?php

$aidForWidget = null;

if ($userIsLoggedIn) {
    $aidForWidget = $user->id;

} else {
    if (count($tracks) > 0) {
        $aidForWidget = $tracks[0]->user_id;
    }
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
	   
      </div> <!-- searchWidget -->

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
