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

		<div id="mainColumnLeft">



      <div id="standardInfoDiv">
        <div id="container">




        <br><br>

        <h1>Contact</h1>
        <iframe src="http://spreadsheets.google.com/embeddedform?key=thN79U430lcxoxd1CUsvqyw" width="610" height="791" frameborder="0" marginheight="0" marginwidth="0">Loading...</iframe>
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
