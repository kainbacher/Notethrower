<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Logger.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Attribute.php');
include_once('../Includes/DB/Message.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/User.php');

//$logger->set_debug_level();

$user = User::new_from_cookie();
ensureUserIsLoggedIn($user);

$action = get_param('action');
if ($action == 'deleteMsg') {
    $mid = get_numeric_param('mid');
    if ($mid) {
        $msg = Message::fetch_for_id($mid);

        if (!$msg || !$msg->id) {
            $logger->error('Message with ID ' . $mid . ' not found!');
        }

        ensureMessageBelongsToUser($msg, $user);

        $msg->deleted = true;
        $msg->save();
    }
}

// find projects where the user could collaborate
$projectListHtml = '';
$projects = Project::fetchAllThatNeedSkillsOfUser($user, 25); // FIXME - limit/paging?
foreach ($projects as $project) {
    $projectUserImgUrl = getUserImageUri($project->user_img_filename, 'tiny');

    $projectListHtml .= processTpl('Dashboard/projectListItem.html', array(
        '${userId}'       => $project->user_id,
        '${userName}'     => escape($project->user_name),
        '${userImgUrl}'   => $projectUserImgUrl,
        '${projectId}'    => $project->id,
        '${projectTitle}' => escape($project->title),
        '${projectNeeds}' => implode(', ', $project->needsAttributeNamesList)
    ));
}

if (count($projects) == 0) {
    $projectListHtml = 'No projects found. You might need to enter or refine your <a href="account.php">user profile</a> to get some project recommendations here.';
}

//// find artists which could help the user with his projects
//$artistListHtml = '';
//$collabArtists = User::fetchAllThatOfferSkillsForUsersProjects($user); // FIXME - limit/paging?
//foreach ($collabArtists as $collabArtist) {
//    $collabArtistImgUrl = getUserImageUri($collabArtist->imageFilename, 'tiny');
//
//    $artistListHtml .= processTpl('Dashboard/artistListItem.html', array(
//        '${userName}'     => escape($collabArtist->name),
//        '${userImgUrl}'   => $collabArtistImgUrl,
//        '${userOffers}'   => implode(', ', $collabArtist->offersAttributeNamesList)
//    ));
//}

// get list of messages for the user
$msgListHtml = '';
$noMessagesFound = '';
$msgs = Message::fetch_all_for_recipient_user_id($user->id, 20);
foreach ($msgs as $msg) {
    $senderImgUrl = getUserImageUri($msg->sender_image_filename, 'tiny');
    //echo '<pre>';
    //print_r($msg);
    //echo '</pre>';
    if($msg->type == 'invite'){
        $subject = 'Project Invite: "'.$msg->subject.'"';
        $text = '<a href="'.$msg->text.'&mid='.$msg->id.'" class="button-small grey">Accept</a>';
        
        $msgListHtml .= processTpl('Dashboard/messageListItemInvite.html', array(
            '${messageId}'                                      => $msg->id,
            '${senderId}'                                       => $msg->sender_user_id,
            '${timestamp}'                                      => reformat_sql_date($msg->entry_date),
            '${senderImgUrl}'                                   => $senderImgUrl,
            '${senderName}'                                     => escape($msg->sender_user_name),
            '${subject}'                                        => $subject,
            '${text}'                                           => $text
        ));
    } else {
        $showMoreLink = '';
        
        if (strlen($msg->text) > 200) {
            $showMoreLink = processTpl('Dashboard/messageListItemShowMoreLink.html', array());
        }
        
        $msgListHtml .= processTpl('Dashboard/messageListItem.html', array(
            '${messageId}'                                      => $msg->id,
            '${senderId}'                                       => $msg->sender_user_id,            
            '${timestamp}'                                      => reformat_sql_date($msg->entry_date),
            '${senderImgUrl}'                                   => $senderImgUrl,
            '${senderName}'                                     => escape($msg->sender_user_name),
            '${Dashboard/messageListItemShowMoreLink_optional}' => $showMoreLink,
            '${subject}'                                        => escape($msg->subject),
            '${textShort}'                                      => $showMoreLink ? substr($msg->text, 0, 200) . ' ...' : $msg->text,
            '${text}'                                           => $showMoreLink ? escape($msg->text) : '' // FIXME - ensure this cannot be more than 500 chars when the text is created
        ));        
    }
}

if (count($msgs) == 0) {
    $noMessagesFound = processTpl('Dashboard/noMessagesFound.html', array());
}

processAndPrintTpl('Dashboard/index.html', array(
    '${Common/pageHeader}'                     => buildPageHeader('Dashboard', false, false),
    '${Common/bodyHeader}'                     => buildBodyHeader($user),
    '${userId}'                                => $user->id,
    '${Dashboard/projectListItem_list}'        => $projectListHtml,
    //'${Dashboard/artistListItem_list}'         => $artistListHtml,
    '${Dashboard/messageListItem_list}'        => $msgListHtml,
    '${Dashboard/noMessagesFound_optional}'    => $noMessagesFound,
    '${Common/bodyFooter}'                     => buildBodyFooter(),
    '${Common/pageFooter}'                     => buildPageFooter()
));

?>        	