<?php

include_once ('../Includes/Init.php');
// must be included first

include_once ('../Includes/Snippets.php');
include_once ('../Includes/PermissionsUtil.php');
include_once ('../Includes/InvitationUtil.php');
include_once ('../Includes/DB/Invitation.php');
include_once ('../Includes/DB/ProjectUserVisibility.php');
include_once ('../Includes/DB/User.php');
include_once ('../Includes/DB/Project.php');

$loggedInUser = User::new_from_cookie();

//ajax - invite friend to project or reply with userid if email already in system
if (get_param('action') == 'inviteExternal') {

    ensureUserIsLoggedIn($loggedInUser);
    $emailAddr = get_param('email');
    $senderUserId = $loggedInUser -> id;
    $projectId = get_param('projectId');

    ensureProjectIdBelongsToUserId($projectId, $senderUserId);
    //check if user is already in database
    $userCheck = User::fetch_for_email_address($emailAddr);
    if ($userCheck) {
        $response = array('type' => 'userExists', 'id' => $userCheck -> id);
        echo json_encode($response);
        exit ;
    }

    //create invitation mail with invite url to project and send it to $emailAddr
    $invitationurl = getUrlForInvitationToProject($senderUserId, $emailAddr, $projectId);
    $projectTitle = Project::fetch_for_id($projectId) -> title;

    $email_subject = $loggedInUser -> name . ' invited you to the oneloudr.com project "' . $projectTitle . '"';
    $email_text = 'Please click the link below to sign up.' . "\n" . $invitationurl . "\n" . 'Your new oneloudr.com Account will automatically be associated with the Project "'.$projectTitle.'"';
    $email_sent = send_email($emailAddr, $email_subject, $email_text);

    if ($email_sent) {
        $response = array('type' => 'inviteSuccess', );
        echo json_encode($response);
        exit ;
    } else {
        $response = array('type' => 'inviteFail', );
        echo json_encode($response);
        exit ;
    }
}
