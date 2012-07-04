<?php

include_once ('../Includes/Init.php');
// must be included first

include_once('../Includes/InvitationUtil.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/Message.php');
include_once('../Includes/DB/ProjectUserVisibility.php');
include_once('../Includes/Mailer/MailUtil.php');

$loggedInUser = User::new_from_cookie();

//ajax - invite user to project based on userid
if (get_param('action') == 'inviteInternal') {
    ensureUserIsLoggedIn($loggedInUser);
    $artistId = get_param('userid');
    $senderUserId = $loggedInUser -> id;
    $projectId = get_param('projectId');

    ensureProjectIdBelongsToUserId($projectId, $senderUserId);

    $test = ProjectUserVisibility::fetch_for_user_id_project_id($artistId, $projectId);
    if ($test -> project_id) {
        $response = array('type' => 'userAlreadyInvited');
        echo json_encode($response);
        exit ;
    } else {
        //send internal message with activation link - easy
        $invitedArtist = User::fetch_for_id($artistId);
        $senderUser = User::fetch_for_id($senderUserId);
        $projectTitle = Project::fetch_for_id($projectId) -> title;
        $invitationurl = getUrlForInvitationToProject($senderUserId, $invitedArtist -> email_address, $projectId);

        $msg = new Message();
        $msg -> sender_user_id = $senderUserId;
        $msg -> recipient_user_id = $artistId;
        $msg -> subject = $projectTitle;
        $msg -> text = $invitationurl;
        $msg -> marked_as_read = false;
        $msg -> type = 'invite';
        $msg -> save();
        
        $email_subject = $senderUser -> name . ' invited you to the oneloudr.com "' . $projectTitle . '" project.';
        $email_text = 'Please click the link below to accept the invitation' . "\n" . $invitationurl;
        

        $email_sent = sendEmail($invitedArtist->email_address, $email_subject, $email_text);
        //$email_sent = true;
        if (!$email_sent) {
            $logger -> error('Failed to send "new message" notification email!');
        } else {
            $response = array('type' => 'inviteSuccess');
            echo json_encode($response);
            exit ;
        }
    }
}

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
        $response = array('type' => 'userExists', 'id' => $userCheck -> id, 'username' => $userCheck -> name);
        echo json_encode($response);
        exit ;
    }

    //create invitation mail with invite url to project and send it to $emailAddr
    $invitationurl = getUrlForInvitationToProject($senderUserId, $emailAddr, $projectId);
    $projectTitle = Project::fetch_for_id($projectId) -> title;

    $email_subject = $loggedInUser -> name . ' invited you to the oneloudr.com project "' . $projectTitle . '"';
    $email_text = 'Please click the link below to sign up.' . "\n" . $invitationurl . "\n" . 'Your new oneloudr.com Account will automatically be associated with the Project "' . $projectTitle . '"';
    $email_sent = sendEmail($emailAddr, $email_subject, $email_text);

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

if (get_param('action') == 'updateRecommendations') {
    $projectId = get_param('projectId');
    $additionalAttributes = get_param('attributes');
    $additionalAttributes = explode(',', $additionalAttributes);
    $projectRecommendedArtists = User::fetchAllThatOfferSkillsForProjectId($loggedInUser -> id, $projectId, $additionalAttributes);

    $projectRecommendedArtistsList = '';
    foreach ($projectRecommendedArtists as $projectRecommendedArtist) {
        //attribute list
        $recommendedArtistAttributes = implode(',', $projectRecommendedArtist -> offersAttributeNamesList);

        //userimagepath
        $recommendedArtistImage = (!empty($projectRecommendedArtist -> image_filename) ? $GLOBALS['BASE_URL'] . 'Content/UserImages/' . $projectRecommendedArtist -> image_filename : $GLOBALS['BASE_URL'] . 'Images/testimages/profile-testimg-75x75.png');
        $projectRecommendedArtistsList .= processTpl('Project/recommendedArtistElement.html', array('${recommendedArtistId}' => $projectRecommendedArtist -> id, '${recommendedArtistName}' => $projectRecommendedArtist -> name, '${recommendedArtistAttributes}' => $recommendedArtistAttributes, '${recommendedArtistProfileImage}' => $recommendedArtistImage));
    }
    echo $projectRecommendedArtistsList;
}

if (get_param('action') == 'searchRecommendation') {
    $searchTerm = get_param('searchTerm');
    if(strlen($searchTerm)>0){
        $projectRecommendedArtists = User::fetch_all_for_name_like($searchTerm, 10);
    
        $projectRecommendedArtistsList = '';
        foreach ($projectRecommendedArtists as $projectRecommendedArtist) {
            //attribute list
            //$recommendedArtistAttributes = implode(',', $projectRecommendedArtist -> offersAttributeNamesList);
            $recommendedArtistAttributes = null;
            //userimagepath
            $recommendedArtistImage = (!empty($projectRecommendedArtist -> image_filename) ? $GLOBALS['BASE_URL'] . 'Content/UserImages/' . $projectRecommendedArtist -> image_filename : $GLOBALS['BASE_URL'] . 'Images/testimages/profile-testimg-75x75.png');
            $projectRecommendedArtistsList .= processTpl('Project/recommendedArtistElement.html', array('${recommendedArtistId}' => $projectRecommendedArtist -> id, '${recommendedArtistName}' => $projectRecommendedArtist -> name, '${recommendedArtistAttributes}' => $recommendedArtistAttributes, '${recommendedArtistProfileImage}' => $recommendedArtistImage));
        }
        echo $projectRecommendedArtistsList;
    }
}
