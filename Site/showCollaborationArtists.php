<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Artist.php');
include_once('../Includes/DB/AudioTrackArtistVisibility.php');

$artistId = get_numeric_param('aid');
if (!$artistId) {
    show_fatal_error_and_exit('aid param is missing');
}

$artist = Artist::fetch_for_id($artistId);
if (!$artist || !$artist->id) {
    show_fatal_error_and_exit('artist not found for id: ' . $artistId);
}

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
    margin: 10px;
    background: #FFFFFF url(../Images/background_04.jpg) no-repeat;
    text-align: left;
}

.artistListTable td {
    padding-right: 5px;
}

    </style>
  </head>
  <body>
    <h1><?php echo escape($artist->name); ?>'s friends:</h1><br>

    <table class="artistListTable">
<?php

    $collaborators = AudioTrackArtistVisibility::fetch_all_collaboration_artists_of_artist_id($artistId);

    foreach ($collaborators as $collaborator) {
        echo '<tr>';

        // linked image
        echo '<td><a href="artistInfo.php?aid=' . $collaborator->collaborating_artist_id . '" target="_blank">';
        echo getArtistImageHtml($collaborator->artist_image_filename, $collaborator->artist_name, 'tiny');
        echo '</a></td>';

        // name
        echo '<td><a href="artistInfo.php?aid=' . $collaborator->collaborating_artist_id . '" target="_blank">' . escape($collaborator->artist_name) . '</a></td>';

        echo '</tr>';
    }

?>
    </table>

    <?php writeGoogleAnalyticsStuff(); ?>

  </body>
</html>
