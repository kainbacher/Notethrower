<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectAttribute.php');
include_once('../Includes/DB/ProjectUserVisibility.php');
include_once('../Includes/DB/User.php');

// FIXME - needed? if yes, rename stuff from "track" to "project"

$visitorUserId = -1;
$userIsLoggedIn  = false;

$user = User::new_from_cookie();
if ($user) {
    $visitorUserId = $user->id;
    $logger->info('visitor user id: ' . $visitorUserId);

    $userIsLoggedIn = true;
    $logger->info('user is logged in');

} else {
    $logger->info('user is NOT logged in');
}

$tid = get_numeric_param('tid');

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
<?php

$track = Project::fetch_project_details($tid, $visitorUserId);

if ($track) { // could be empty if wrong id or not visible for logged in user
    $ownerUser = User::fetch_for_id($track->user_id);

    echo '<h1>' . escape($track->title) . '</h1>';
    echo '<small>';

    if ($track->type == 'remix') {
        echo 'Remix';
        //$oa = User::fetch_for_id($track->originating_user_id);
        //if ($oa) {
        //    echo ' | Originating user: <a href="artist.php?aid=' . $oa->id . '" target="_blank">' . escape($oa->name) . '</a>' . "\n";
        //}

    } else {
        echo 'Original';
    }
    echo '</small>';

    // track attributes
    $containsAttrs = ProjectAttribute::getAttributeNamesForProjectIdAndState($track->id, 'contains');
    $needsAttrs    = ProjectAttribute::getAttributeNamesForProjectIdAndState($track->id, 'needs');

    if (count($containsAttrs) > 0) {
        echo '<br><br><b>This track contains:</b><br>' . "\n";
        echo join(', ', $containsAttrs) . "\n";
    }

    if (count($needsAttrs) > 0) {
        echo '<br><br><b>This track needs:</b><br>';
        echo join(', ', $needsAttrs) . "\n";
    }

    if ($track->additionalInfo) {
        echo '<br><br><b>Additional info:</b><br>' . "\n";
        echo escape($track->additionalInfo) . "\n";
    }

    if ($ownerUser->id != $user->id) {
        echo '<br><br>' . "\n";
        echo '<a href="javascript:showSendMessagePopup(' . $ownerUser->id . ');"><img border="0" src="../Images/Mail_Icon.png">&nbsp;Send message to ' . escape($ownerUser->name) . '&nbsp;(need to be logged in)</a>' . "\n";
    }

    // collaborators
    $collaborators = ProjectUserVisibility::fetch_all_collaboration_users_of_user_id($ownerUser->id, 10); // attention: if the limit of 10 is changed, the code below must be changed as well (row processing code and colspans)
    if (count($collaborators) > 0) {
        echo '<br><br><h1>' . escape($ownerUser->name) . '\'s friends:</h1>' . "\n";
        echo '<table>';
        // row 1
        echo '<tr>';
        for ($i = 0; $i < 5; $i++) {
            echo '<td>';
            if (isset($collaborators[$i])) {
                echo '<a href="artist.php?aid=' . $collaborators[$i]->collaborating_user_id . '" target="_blank">';
                echo getUserImageHtml($collaborators[$i]->user_image_filename, $collaborators[$i]->user_name, 'tiny');
                echo '</a>';

            } else {
                echo '&nbsp;';
            }
            echo '</td>' . "\n";
        }
        echo '</tr>' . "\n";

        // row 2
        echo '<tr>';
        for ($i = 5; $i < 10; $i++) {
            echo '<td>';
            if (isset($collaborators[$i])) {
                echo '<a href="artist.php?aid=' . $collaborators[$i]->collaborating_user_id . '" target="_blank">';
                echo getUserImageHtml($collaborators[$i]->user_image_filename, $collaborators[$i]->user_name, 'tiny');
                echo '</a>';

            } else {
                echo '&nbsp;';
            }
            echo '</td>' . "\n";
        }
        echo '</tr>' . "\n";

        if (count($collaborators) > 10) {
            echo '<tr><td colspan="5">' . "\n";
            echo '... and some more. <a href="javascript:showCollaborationUsersPopup();" target="_blank">';
            echo 'See all.';
            echo '</a>';
            echo '</td></tr>' . "\n";
        }
        echo '</table' . "\n";
    }
}

?>
    <script language="javascript">

function showTrackGrid() {
    var showHideMode = '';
    $('#chosenTrackDetailsContainer').hide(showHideMode);
    $('#chosenTrackHeadlineContainer').hide(showHideMode);

    $('#trackGrid').show(showHideMode);
    $('#trackGridHeadlineContainer').show(showHideMode);
    $('#paginationWrapper').show(showHideMode);
}

    </script>

    <br/>
    <br/>
    <a class="button" href="javascript:showTrackGrid();">&raquo; back to the Music</a>

  </body>
</html>
