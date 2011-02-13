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
                    
                        <br/>
                        <h1>You're almost there!</h1>
                        <br/>
                        Your account was created successfully but you need to activate it first.<br>
                        <br/>
                        Please activate your account by clicking the link in the email which was just sent to you.<br>
                        <br/>
                        <a href="index.php">Back to start page</a>
                    
                    </div> <!-- container -->
                </div> <!-- standardInfoDiv -->
                
                <div id="standardInfoDivEnd"></div>
                
                <br/>
            
            </div> <!-- pageMainContainer -->

        <? include ("footer.php"); ?>
	
    </div> <!-- bodyWrapper -->
	
    <?php writeGoogleAnalyticsStuff(); ?>

    </body>
</html>
