<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/ProjectUserVisibility.php');

$userId = get_numeric_param('aid');
if (!$userId) {
    show_fatal_error_and_exit('aid param is missing');
}

$user = User::fetch_for_id($userId);
if (!$user || !$user->id) {
    show_fatal_error_and_exit('user not found for id: ' . $userId);
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

.userListTable td {
    padding-right: 5px;
}

    </style>
  </head>
  <body>
    <h1><?php echo escape($user->name); ?>'s friends:</h1><br>

    <table class="userListTable">
<?php

    $collaborators = ProjectUserVisibility::fetch_all_collaboration_users_of_user_id($userId);

    foreach ($collaborators as $collaborator) {
        echo '<tr>';

        // linked image
        echo '<td><a href="artist.php?aid=' . $collaborator->collaborating_user_id . '" target="_blank">';
        echo getUserImageHtml($collaborator->user_image_filename, $collaborator->user_name, 'tiny');
        echo '</a></td>';

        // name
        echo '<td><a href="artist.php?aid=' . $collaborator->collaborating_user_id . '" target="_blank">' . escape($collaborator->user_name) . '</a></td>';

        echo '</tr>';
    }

?>
    </table>

    <?php writeGoogleAnalyticsStuff(); ?>

  </body>
</html>
