<?php

include_once('../Includes/Init.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Artist.php');

$artist = Artist::new_from_cookie();
if ($artist) {
    $logger->info('user is logged in');
} else {
    show_fatal_error_and_exit('access denied for not-logged-in user');
}

$trackId = get_numeric_param('tid');
if (!$trackId) {
    show_fatal_error_and_exit('tid param is missing');
}

ensureTrackIdBelongsToArtistId($trackId, $artist->id);

writePageDoctype();

?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <?php writePageMetaTags(); ?>
    <title><?php writePageTitle(); ?></title>
    <link rel="stylesheet" href="../Styles/main.css" type="text/css">
 	<style type="text/css">

body {
    background: #FFFFFF url(../Images/background_04.jpg) no-repeat;
}

#artistList {
  	margin: 0px;
  	width: 222px;
  	border: 1px solid #A5ACB2;
    background-color: white;
}

#friendSelection {
    text-align: left;
  	padding: 10px;
}

    </style>
    <script type="text/javascript">

var xmlhttp;
var oldQueryString  = Math.random(); // initialize with some random value
var THROTTLE_PERIOD = 1000;

// returns a new AJAX object
function getXmlHttpObject() {
    if (window.XMLHttpRequest) {
        // code for IE7+, Firefox, Chrome, Opera, Safari
        return new XMLHttpRequest();
    }
    if (window.ActiveXObject) {
        // code for IE6, IE5
        return new ActiveXObject("Microsoft.XMLHTTP");
    }
    return null;
}

// called when the artist name field changed (onkeyup) to enable/disable the addButton
function artistFieldChanged(newArtistName) {
    if (document.getElementById('selectedArtistNameField').value != document.getElementById('artistNameField').value) {
        document.getElementById('addButton').disabled = true;
    } else {
 	    document.getElementById('addButton').disabled = false;
    }
}

// called when the artist name field changed (onkeyup) to enable/disable the addButton
function artistListSelectionChanged() {
    var saList = document.getElementById('selectedArtistList');
    var atLeastOneSelected = false;
    for (i = 0; i < saList.length; i++) {
        if (saList.options[i].selected) {
 	        atLeastOneSelected = true;
 	        break;
 	    }
    }
    if (atLeastOneSelected) {
        document.getElementById('removeButton').disabled = false;
    } else {
 	    document.getElementById('removeButton').disabled = true;
    }
}

// called when the user selects an artist from the drop down list
// and stores the artist id and the artist name in the hidden fields
function selectArtist(artistId, artistName) {
    document.getElementById('selectedArtistNameField').value = artistName;
    document.getElementById('selectedArtistIdField').value = artistId;
    document.getElementById('artistNameField').value = artistName;
    document.getElementById('artistList').innerHTML = '';
    document.getElementById('addButton').disabled = false;
}

// called after the search artist call to the backend happened to build the drop down list of artists
function updateArtistList() {
    if (xmlhttp.readyState == 4) {
        var artists = eval('(' + xmlhttp.responseText + ')');
        var html = '';

        for (i = 0; i < artists.length; i++) {
            nameAsHtml = artists[i].name.substring(0, artists[i].name.toUpperCase().indexOf(oldQueryString.toUpperCase())) +
                         '<b>' + artists[i].name.substring(artists[i].name.toUpperCase().indexOf(oldQueryString.toUpperCase()), artists[i].name.toUpperCase().indexOf(oldQueryString.toUpperCase()) + oldQueryString.length) + '</b>' +
                         artists[i].name.substring(artists[i].name.toUpperCase().indexOf(oldQueryString.toUpperCase()) + oldQueryString.length);

            html = html + '<div style="cursor: pointer; cursor: hand;" onmouseover="this.style.backgroundColor=\'#aaaaaa\'" ' +
                   'onmouseout="this.style.backgroundColor=\'white\'" ' +
                   'onclick="selectArtist(' + artists[i].id + ', \'' + artists[i].name + '\')">' +
                   '<table style="border-collapse: collapse; table-layout: fixed;"><tr><td style="width: 45px;"><img style="display: block;" border="0" src="' + artists[i].imagePath + '" height="30"></td><td style="text-align: center; vertical-align: middle;"> ' + nameAsHtml + '</td></tr></table></div>';
        }
        //alert(html);
        if (artists.length > 0) {
            document.getElementById("artistList").innerHTML = html;
        } else {
            document.getElementById("artistList").innerHTML = "Artist not found";
        }
    }
}

// called by the timeout function to check if input changed, if so
// the backend search is executed
function searchForArtist() {
    var queryString = document.getElementById('artistNameField').value;
    if (queryString != oldQueryString) {
        if (queryString.length == 0) {
            document.getElementById("artistList").innerHTML = "Start typing a name";

        } else if (queryString.length < 3) {
 	        document.getElementById("artistList").innerHTML = "Please type at least 3 letters";

        } else {
            xmlhttp = getXmlHttpObject();
            if (xmlhttp == null) {
                // ajax not supported
                return;
            }
            oldQueryString = queryString;
            var url = '../Backend/searchArtist.php';
            url = url + '?q=' + queryString;
            // for not being cached somewhere
            url = url + '&sid=' + Math.random();
            xmlhttp.onreadystatechange = updateArtistList;
            xmlhttp.open('GET', url, true);
            xmlhttp.send(null);
        }
    }
    setTimeout('searchForArtist();', THROTTLE_PERIOD);
}

// called after the backend call to retrieve the selected artists happened.
// will update the selection box for the selected artists
function updateSelectedArtistList() {
    var saList = document.getElementById('selectedArtistList');
    if (xmlhttp.readyState == 4) {
        var artists = eval('(' + xmlhttp.responseText + ')');
        // remove all options from the list
        saList.length = 0;
        for (i = 0; i < artists.length; i++) {
            if (artists[i].artist_id != <?php echo $artist->id; ?>) {
                saList.options[saList.length] = new Option(artists[i].artist_name, artists[i].artist_id);
            }
        }

        artistListSelectionChanged();
    }
}

// will update the list of selected artists by calling the backend
function getSelectedArtistList() {
    xmlhttp = getXmlHttpObject();
    if (xmlhttp == null) {
        // ajax not supported
        return;
    }
    var url = '../Backend/getFriendsList.php';
    url = url + '?aid=<?php echo $artist->id; ?>';
    url = url + '&tid=<?php echo $trackId; ?>';
    // for not being cached somewhere
    url = url + '&sid=' + Math.random();
    xmlhttp.onreadystatechange = updateSelectedArtistList;
    xmlhttp.open('GET',url,true);
    xmlhttp.send(null);
}

// called when the user clicks on the "Add" button
// We could be sure, that the artist exists, because the
// button is only active when the user selected the artist from the list
//
// The backend gets called, with the artistId to be added
// the "postAddArtist()" method is called afterwards.
function addArtist() {
    var artistId = document.getElementById('selectedArtistIdField').value;
    xmlhttp = getXmlHttpObject();
    if (xmlhttp == null) {
        // ajax not supported
        return;
    }
    var url = '../Backend/addArtistAsFriend.php';
    url = url + '?aid=' + artistId;
    url = url + '&tid=<?php echo $trackId; ?>';
    // for not being cached somewhere
    url = url + '&sid=' + Math.random();
    xmlhttp.onreadystatechange = postAddArtist;
    xmlhttp.open('GET', url, true);
    xmlhttp.send(null);
}

// called after the addArtist call has been made
// reads the response and calls the getSelectedArtistList to
// update the list of selected artists
function postAddArtist() {
    if (xmlhttp.readyState == 4) {
        if (xmlhttp.responseText == "Success") {
            getSelectedArtistList();
        }
    }
}

// called if the user clicks on the "remove" button
// will call the backend to actually remove the selected artists from the list
function removeArtists() {
    var ids = '';
    var saList = document.getElementById('selectedArtistList');
    for (i = 0; i < saList.length; i++) {
        if (saList.options[i].selected) {
 	        if (ids.length > 0) {
 	 	 	    ids = ids + ',';
 	 	    }
 	 	    ids = ids + saList.options[i].value;
 	    }
    }

    if (ids.length == 0) {
 	    return;
    }

    // call the backend to remove the ids
    xmlhttp = getXmlHttpObject();
    if (xmlhttp == null) {
        // ajax not supported
        return;
    }
    var url = '../Backend/removeArtistsAsFriends.php';
    url = url + '?aids=' + ids;
    url = url + '&tid=<?php echo $trackId; ?>';
    // for not being cached somewhere
    url = url + '&sid=' + Math.random();
    xmlhttp.onreadystatechange = postRemoveArtists;
    xmlhttp.open('GET', url, true);
    xmlhttp.send(null);
}

// called after the remove artists call to the backend happened.
// does call the getSelectedArtistList to update the selection box
function postRemoveArtists() {
    if (xmlhttp.readyState == 4) {
        if (xmlhttp.responseText == "Success") {
            getSelectedArtistList();
        }
    }
}

    </script>
  </head>
  <body onload="getSelectedArtistList(); searchForArtist();">

    <div id="friendSelection">
      <input id="selectedArtistIdField" type="hidden">
      <input id="selectedArtistNameField" type="hidden">

      <b>Search:</b><br>
      <input style="width:220px;" id="artistNameField" type="text" onkeyup="artistFieldChanged(this.value)" onfocus="searchForArtist()"><input id="addButton" type="submit" value="Add" onclick="addArtist()" disabled="true"><br>
      <div id="artistList"></div><br>
      <b>Currently assigned artists:</b><br>
      <select style="width:222px;" id="selectedArtistList" multiple="true" onchange="artistListSelectionChanged()"></select><br>
      <small>Hold CTRL key to select multiple artists</small><br>
      <br>
      <input id="removeButton" type="submit" value="Remove selected artists" onclick="removeArtists()" disabled="true">
    </div>

    <?php writeGoogleAnalyticsStuff(); ?>

  </body>
</html>
