<?php

include_once('../Includes/Init.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');

$user = User::new_from_cookie();
if ($user) {
    $logger->info('user is logged in');
} else {
    show_fatal_error_and_exit('access denied for not-logged-in user'); // don't redirect to pleaseLogin page because this page is loaded in a popup
}

$trackId = get_numeric_param('tid');
if (!$trackId) {
    show_fatal_error_and_exit('tid param is missing');
}

ensureProjectIdBelongsToUserId($trackId, $user->id);

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

#userList {
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

// called when the user name field changed (onkeyup) to enable/disable the addButton
function userFieldChanged(newUserName) {
    if (document.getElementById('selectedUserNameField').value != document.getElementById('userNameField').value) {
        document.getElementById('addButton').disabled = true;
    } else {
 	    document.getElementById('addButton').disabled = false;
    }
}

// called when the user name field changed (onkeyup) to enable/disable the addButton
function userListSelectionChanged() {
    var saList = document.getElementById('selectedUserList');
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

// called when the user selects an user from the drop down list
// and stores the user id and the user name in the hidden fields
function selectUser(userId, userName) {
    document.getElementById('selectedUserNameField').value = userName;
    document.getElementById('selectedUserIdField').value = userId;
    document.getElementById('userNameField').value = userName;
    document.getElementById('userList').innerHTML = '';
    document.getElementById('addButton').disabled = false;
}

// called after the search user call to the backend happened to build the drop down list of users
function updateUserList() {
    if (xmlhttp.readyState == 4) {
        var users = eval('(' + xmlhttp.responseText + ')');
        var html = '';

        for (i = 0; i < users.length; i++) {
            nameAsHtml = users[i].name.substring(0, users[i].name.toUpperCase().indexOf(oldQueryString.toUpperCase())) +
                         '<b>' + users[i].name.substring(users[i].name.toUpperCase().indexOf(oldQueryString.toUpperCase()), users[i].name.toUpperCase().indexOf(oldQueryString.toUpperCase()) + oldQueryString.length) + '</b>' +
                         users[i].name.substring(users[i].name.toUpperCase().indexOf(oldQueryString.toUpperCase()) + oldQueryString.length);

            html = html + '<div style="cursor: pointer; cursor: hand;" onmouseover="this.style.backgroundColor=\'#aaaaaa\'" ' +
                   'onmouseout="this.style.backgroundColor=\'white\'" ' +
                   'onclick="selectUser(' + users[i].id + ', \'' + users[i].name + '\')">' +
                   '<table style="border-collapse: collapse; table-layout: fixed;"><tr><td style="width: 45px;"><img style="display: block;" border="0" src="' + users[i].imagePath + '" height="30"></td><td style="text-align: center; vertical-align: middle;"> ' + nameAsHtml + '</td></tr></table></div>';
        }
        //alert(html);
        if (users.length > 0) {
            document.getElementById("userList").innerHTML = html;
        } else {
            document.getElementById("userList").innerHTML = "Artist not found";
        }
    }
}

// called by the timeout function to check if input changed, if so
// the backend search is executed
function searchForUser() {
    var queryString = document.getElementById('userNameField').value;
    if (queryString != oldQueryString) {
        if (queryString.length == 0) {
            document.getElementById("userList").innerHTML = "Start typing a name";

        } else if (queryString.length < 3) {
 	        document.getElementById("userList").innerHTML = "Please type at least 3 letters";

        } else {
            xmlhttp = getXmlHttpObject();
            if (xmlhttp == null) {
                // ajax not supported
                return;
            }
            oldQueryString = queryString;
            var url = '../Backend/searchUser.php';
            url = url + '?q=' + queryString;
            // for not being cached somewhere
            url = url + '&sid=' + Math.random();
            xmlhttp.onreadystatechange = updateUserList;
            xmlhttp.open('GET', url, true);
            xmlhttp.send(null);
        }
    }
    setTimeout('searchForUser();', THROTTLE_PERIOD);
}

// called after the backend call to retrieve the selected users happened.
// will update the selection box for the selected users
function updateSelectedUserList() {
    var saList = document.getElementById('selectedUserList');
    if (xmlhttp.readyState == 4) {
        var users = eval('(' + xmlhttp.responseText + ')');
        // remove all options from the list
        saList.length = 0;
        for (i = 0; i < users.length; i++) {
            if (users[i].user_id != <?php echo $user->id; ?>) {
                saList.options[saList.length] = new Option(users[i].user_name, users[i].user_id);
            }
        }

        userListSelectionChanged();
    }
}

// will update the list of selected users by calling the backend
function getSelectedUserList() {
    xmlhttp = getXmlHttpObject();
    if (xmlhttp == null) {
        // ajax not supported
        return;
    }
    var url = '../Backend/getFriendsList.php';
    url = url + '?aid=<?php echo $user->id; ?>';
    url = url + '&tid=<?php echo $trackId; ?>';
    // for not being cached somewhere
    url = url + '&sid=' + Math.random();
    xmlhttp.onreadystatechange = updateSelectedUserList;
    xmlhttp.open('GET',url,true);
    xmlhttp.send(null);
}

// called when the user clicks on the "Add" button
// We could be sure, that the user exists, because the
// button is only active when the user selected the user from the list
//
// The backend gets called, with the userId to be added
// the "postAddUser()" method is called afterwards.
function addUser() {
    var usertId = document.getElementById('selectedUserIdField').value;
    xmlhttp = getXmlHttpObject();
    if (xmlhttp == null) {
        // ajax not supported
        return;
    }
    var url = '../Backend/addUserAsFriend.php';
    url = url + '?aid=' + userId;
    url = url + '&tid=<?php echo $trackId; ?>';
    // for not being cached somewhere
    url = url + '&sid=' + Math.random();
    xmlhttp.onreadystatechange = postAddUser;
    xmlhttp.open('GET', url, true);
    xmlhttp.send(null);
}

// called after the addUser call has been made
// reads the response and calls the getSelectedUserList to
// update the list of selected users
function postAddUser() {
    if (xmlhttp.readyState == 4) {
        if (xmlhttp.responseText == "Success") {
            getSelectedUserList();
        }
    }
}

// called if the user clicks on the "remove" button
// will call the backend to actually remove the selected users from the list
function removeUsers() {
    var ids = '';
    var saList = document.getElementById('selectedUserList');
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
    var url = '../Backend/removeUsersAsFriends.php';
    url = url + '?aids=' + ids;
    url = url + '&tid=<?php echo $trackId; ?>';
    // for not being cached somewhere
    url = url + '&sid=' + Math.random();
    xmlhttp.onreadystatechange = postRemoveUsers;
    xmlhttp.open('GET', url, true);
    xmlhttp.send(null);
}

// called after the remove users call to the backend happened.
// does call the getSelectedUserList to update the selection box
function postRemoveUsers() {
    if (xmlhttp.readyState == 4) {
        if (xmlhttp.responseText == "Success") {
            getSelectedUserList();
        }
    }
}

    </script>
  </head>
  <body onload="getSelectedUserList(); searchForUser();">

    <div id="friendSelection">
      <input id="selectedUserIdField" type="hidden">
      <input id="selectedUserNameField" type="hidden">

      <b>Search:</b><br>
      <input style="width:220px;" id="userNameField" type="text" onkeyup="userFieldChanged(this.value)" onfocus="searchForUser()"><input id="addButton" type="submit" value="Add" onclick="addUser()" disabled="true"><br>
      <div id="userList"></div><br>
      <b>Currently assigned users:</b><br>
      <select style="width:222px;" id="selectedUserList" multiple="true" onchange="userListSelectionChanged()"></select><br>
      <small>Hold CTRL key to select multiple artists</small><br>
      <br>
      <input id="removeButton" type="submit" value="Remove selected artists" onclick="removeUsers()" disabled="true">
    </div>

    <?php writeGoogleAnalyticsStuff(); ?>

  </body>
</html>
