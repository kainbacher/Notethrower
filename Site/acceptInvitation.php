<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/InvitationUtil.php');
include_once('../Includes/DB/Invitation.php');
include_once('../Includes/DB/ProjectUserVisibility.php');
include_once('../Includes/DB/User.php');

if (isset($_GET['iid']) && isset($_GET['cs']) && md5('R.I.P.SuperSic!' . $_GET['iid']) == $_GET['cs']) {
    $logger->info('processing request for invitation id: ' . $_GET['iid']);

    $inv = Invitation::fetchForId($_GET['iid']);
    if (!$inv) {
        show_fatal_error_and_exit('invitation not found for id: ' . $_GET['iid']);
    }

    // check if user with this email already exists
    $checkUser = User::fetch_for_email_address($inv->recipient_email_address);
    if ($checkUser && $checkUser->id) {
        // the user already exists -> create visibility entry for invitation project and we're done.
        $logger->info('invited user already exists in system');

        $puv = ProjectUserVisibility::fetch_for_user_id_project_id($checkUser->id, $inv->project_id);
        if (!$puv || !$puv->project_id) {
            $puv = new ProjectUserVisibility();
            $puv->user_id    = $checkUser->id;
            $puv->project_id = $inv->project_id;
            $puv->save();
            $logger->info('saved project/user visibility record');

        } else {
            $logger->info('invited user is already associated with project');
        }

        redirectTo('project.php?action=edit&pid=' . $inv->project_id);

    } else {
        // invited user does not exist in system (which is the regular case)
        // send him to the signup form and prefill the email address field
        // the invitation project is passed thru
        redirectTo('account.php' .
                   '?email_address=' . $inv->recipient_email_address .
                   '&invitedToProject=1');
    }

} else {
    show_fatal_error_and_exit('Invalid ' . $_SERVER['PHP_SELF'] . ' request: ' . $_SERVER['QUERY_STRING']);
}

// END

?>                    
