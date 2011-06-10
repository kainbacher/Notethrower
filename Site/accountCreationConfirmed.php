<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');

if (isset($_GET['x']) && isset($_GET['c']) && md5('TheSparrowsAreFlyingAgain!' . $_GET['x']) == $_GET['c']) {
    $logger->info('activation requested for account: ' . $_GET['x']);

    $user = User::fetch_for_id($_GET['x']);
    if (!$user) {
        $logger->warn('user not found: ' . $_GET['x']);
        exit;

    } else if ($user->status != 'inactive') { // make sure that eg a banned used cannot reactivate his account be clicking the confirmation link again
        $logger->warn('user was found but status was not "inactive", exit');
        exit;
    }

    $user->status = 'active';
    $user->save();

} else {
    $logger->warn('Invalid confirmation request: ' . $_SERVER['QUERY_STRING']);
    exit;
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
            
            
                <div id="standardInfoDivStart"></div>
                <div id="standardInfoDiv">
                    <div id="container">
                        <br>
                        <h1>Welcome to Notethrower</h1>
                        <br/>
                        <h2>You're done!</h2>
                        <br>
                        You just successfully activated your account. Please log in on the start page now.<br>
                        <br>
                        <a href="index.php">Proceed to start page</a>
                    </div>
                </div>
            
                <div id="standardInfoDivEnd"></div>
            
                <br/>
            </div> <!-- pageMainContent -->
            
            <? include ("footer.php"); ?>

        </div> <!-- bodyWrapper -->

        <?php writeGoogleAnalyticsStuff(); ?>

    </body>
</html>
