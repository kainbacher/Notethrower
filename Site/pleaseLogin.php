<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');

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
                    <h1>Please log in first ...</h1>
                    <br/>
                    Sorry, you need to be logged in to view the requested page.<br>
                    <br/>
                    <a href="index.php">Back to start page</a>
                </div>
            </div>
        
            <div id="standardInfoDivEnd"></div>

        </div>

        <? include ("footer.php"); ?>
        
    </div> <!-- bodyWrapper -->

    <?php writeGoogleAnalyticsStuff(); ?>

  </body>
</html>
