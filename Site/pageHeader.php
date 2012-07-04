<div id="pageHeaderWrapper">
<div id="pageHeader">

<?php

include_once('../Includes/DB/User.php');
include_once('../Includes/DB/Message.php');

$msgCount = 0;
$loggedInUserId = 0;

if (!$userIsLoggedIn) { // this can either mean that the user is not logged in or the login check was not yet done on the surrounding page. so we have to check it (again).
    $logger->info('$userIsLoggedIn var is false, so we have to find out if the user is not defined yet or ifhe\'s really not logged in.');
    $pageHeaderUser = User::new_from_cookie(); // don't use $user as variable name here because it may influence the behaviour of the surrounding php page
    if ($pageHeaderUser) {
        $logger->info('user is logged in, $userIsLoggedIn var is now true');
        $userIsLoggedIn = true;
        $loggedInUserId = $pageHeaderUser->id;
        $msgCount = Message::count_all_unread_msgs_for_recipient_user_id($pageHeaderUser->id);

    } else {
        $logger->info('user is not logged in, $userIsLoggedIn var is still false');
    }

} else {
    if ($user) {
        $loggedInUserId = $user->id;
        $logger->info('user is logged in.');
        $msgCount = Message::count_all_unread_msgs_for_recipient_user_id($user->id);

    } else {
        $logger->error('$userIsLoggedIn is true but we have no $user var, WTF?!');
    }
}

/* show_header_logo(); */

echo '<div id="logo">ONELOUDR <span class="blue">BETA</span></div>';

if (!$userIsLoggedIn) {
    echo '<div id="signUpTeaser"><a href="account.php">...</a></div>' . "\n";
}

?>
    <div id="loginWrapper">
        <div id="loginStatusDiv">


<?php

if ($userIsLoggedIn) {

    if ($msgCount > 0) {
        echo '<div class="topMenuItem">' . "\n";
        echo '<a href="collaboration.php#Inbox">' . $msgCount . ' new messages</a>' . "\n";
        echo '</div>' . "\n";
    }

	echo '<div class="topMenuItem">' . "\n";
	echo '<a href="projectList.php">Me</a>' . "\n";
	echo '<div class="topMenuSub">' . "\n";

	echo '<div class="topMenuSubItem">' . "\n";
	echo '<a href="artist.php?aid=' . $loggedInUserId . '">Artistpage</a>' . "\n";
	echo '</div>' . "\n";

	echo '<div class="topMenuSubItem">' . "\n";
	echo '<a href="projectList.php">My projects</a>' . "\n";
	echo '</div>' . "\n";

	echo '<div class="topMenuSubItem">' . "\n";
	echo '<a href="collaboration.php">Collaboration</a>' . "\n";
	echo '</div>' . "\n";
	echo '</div>' . "\n";
	echo '</div>' . "\n";



	echo '<div class="topMenuItem">' . "\n";
	echo '<a href="account.php">Settings</a>' . "\n";
	echo '<div class="topMenuSub">' . "\n";
	echo '<div class="topMenuSubItem">' . "\n";
	echo '<a href="account.php">Profile</a>' . "\n";
	echo '</div>' . "\n";
	echo '</div>' . "\n";
	echo '</div>' . "\n";

	echo '<div class="topMenuItem">' . "\n";
	echo '<a href="logout.php">Log out</a>' . "\n";
	echo '</div>' . "\n";


echo '<div class="topMenuItemSearch">' . "\n";
echo '<div class="topSearch">' . "\n";
echo '<form action="search.php" method="get">' . "\n";
echo '<input name="s" id="topSearchInput" onclick="ClearInput(this.id);" type="text" value="Search" class="field" maxlength="50" />' . "\n";
echo '<input type="submit" value="" id="topSearchSubmit" />' . "\n";
echo '</form>' . "\n";
echo '</div>' . "\n";
echo '</div>' . "\n";


} else {


    echo '<div class="topMenuItem">' . "\n";
    echo '<a href="account.php">Sign up for free</a>' . "\n";
    echo '</div>' . "\n";
    echo '<div class="topMenuItem">' . "\n";
    echo '<a href="javascript:showLogin();">Log in</a>' . "\n";
    echo '<div class="topMenuSub topMenuSubLogin">' . "\n";
    echo '<form name="loginForm" action="index.php" method="POST">' . "\n"; // this was $_SERVER['PHP_SELF'] instead of index.php before, but it turned out that this behaves oddly on the pleaseLogin.php page


    echo '<input type="hidden" name="action" value="login">' . "\n";

    echo '<input id="loginUsername" type="text" name="username" value="' . get_param('username') . '">' . "\n";
    echo '<span class="loginFormLabel">Username</span><br/>' . "\n";


    echo '<input id="loginPassword" type="password" name="password" value="">' . "\n";
    echo '<span class="loginFormLabel">Password</span><br/>' . "\n";

    echo '<input class="button blue small" type="submit" value="login">' . "\n";

    echo '</form>' . "\n";
    echo '</div>' . "\n";
    echo '</div>' . "\n";


echo '<div class="topMenuItemSearch">' . "\n";
echo '<div class="topSearch">' . "\n";
echo '<form action="search.php" method="get">' . "\n";
echo '<input name="s" id="topSearchInput" onclick="ClearInput(this.id);" type="text" value="Search" class="field" maxlength="50" />' . "\n";
echo '<input type="submit" value="" id="topSearchSubmit" />' . "\n";
echo '</form>' . "\n";
echo '</div>' . "\n";
echo '</div>' . "\n";

}

?>



        </div>
    </div> <!-- loginWrapper -->
</div> <!-- pageHeader -->
</div><!-- pageHeaderWrapper -->
