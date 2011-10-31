<?php

include_once('Config.php');
include_once('Snippets.php');
include_once('DB/Invitation.php');
include_once('DB/User.php');

function getUrlForInvitationToProject($senderUserId, $recipientEmailAddr, $projectId) {
    global $logger;

    // save data in invitation table
    $i = new Invitation();
    $i->sender_user_id          = $senderUserId;
    $i->recipient_email_address = $recipientEmailAddr;
    $i->project_id              = $projectId;
    $i->creation_date           = date('y-m-d H:i:s');
    $i->save();

    // generate url with invitation table id and checksum
    $url = $GLOBALS['BASE_URL'] . 'Site/acceptInvitation.php' .
           '?iid=' . $i->id .
           '&cs=' . md5('R.I.P.SuperSic!' . $i->id);

    $logger->info('invitation url: ' . $url);

    return $url;
}

?>