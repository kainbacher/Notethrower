<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');

writePageDoctype();

?>
<html>
  <head>
      <? include ("headerData.php"); ?>
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

		<div id="mainColumnLeft">

      <div id="standardInfoDiv">
        <div id="container">




        <br><br>

        <h1>oneloudr’s Legale Notice</h1>
        <br>
The following frequently asked questions and corresponding
answers were created to provide you with further insight about
becoming a member and licensing your music with oneloudr.<br>
<br>
<br>
<br>
<br>

      </div>

      </div> <!-- standrardInfoDiv -->


        </div> <!-- mainColumnLeft -->

		<div id="mainColumnRight">
            <? include ("sidebar.php"); ?>
		</div> <!-- mainColumnRight -->

      	<div style="clear:both"></div>


	</div> <!-- pageMainContent -->


	<? include ("footer.php"); ?>


	</div> <!-- bodyWrapper -->
  </body>
</html>
