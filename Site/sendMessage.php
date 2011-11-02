<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/Message.php');


$senderUser = User::new_from_cookie();

if (!$senderUser) {
    show_fatal_error_and_exit('access denied, no user cookie found.');
}

$recipientUserId = get_numeric_param('raid');
if (!$recipientUserId) {
    show_fatal_error_and_exit('raid param is missing');
}

$recipientUser = User::fetch_for_id($recipientUserId);
if (!$recipientUser || !$recipientUser->id) {
    show_fatal_error_and_exit('recipient user not found for id: ' . $recipientUserId);
}


$sendMsgForm = processTpl('SendMessage/newMessageForm.html', array(
    '${recipientUserId}' => $recipientUser->id
), $showMobileVersion);


$statusMessage = '';

$action = get_param('action');
if ($action == 'send') {
    $subject = get_param('subject');
    $text    = get_param('text');

    if ($subject || $text) {
        $msg = new Message();
        $msg->sender_user_id    = $senderUser->id;
        $msg->recipient_user_id = $recipientUser->id;
        $msg->subject             = $subject;
        $msg->text                = $text;
        $msg->marked_as_read      = false;
        $msg->save();

        // append some footer text for the email
        $text   .= "\n\nSee ".$senderUser->name."'s profile page on Oneloudr.com\n--\nOneloudr - Social Music Making";
        $text   .= "\n\nYou can directly reply to this email to contact the sender";

        $email_sent = send_email($recipientUser->email_address,
                'Message from ' . $senderUser->name,
                'Hey ' . $recipientUser->name . "\n" .
                $senderUser->name . ' has just sent you a private Message.'. "\n" .
                'Subject: ' . $msg->subject . "\n" .
                'Message: ' . $msg->text,
                '','','',$senderUser->email_address);

        if (!$email_sent) {
            $logger->error('Failed to send "new message" notification email!');
        }

        $statusMessage = 'Your message has been sent.';
        $sendMsgForm = '';

    } else {
        $statusMessage = 'Your message is empty.';
    }
    echo $statusMessage;

} else {
    processAndPrintTpl('SendMessage/index.html', array(
        '${Common/pageHeader}'            => buildPageHeader('Send message', false, false, false, $showMobileVersion),
        '${Common/bodyHeader}'            => buildBodyHeader($user),
        '${Common/bodyFooter}'            => buildBodyFooter(),
        '${Common/pageFooter}'            => buildPageFooter(),
        '${recipientUserName}'            => $recipientUser->name,
        '${SendMessage/sendMessageForm}'  => $sendMsgForm
    ));
}
//print_r($senderUser);
//print_r($recipientUser);


/*
processAndPrintTpl('Features/index.html', array(
    '${Common/pageHeader}'                     => buildPageHeader('Features'),
    '${Common/bodyHeader}'                     => buildBodyHeader($user),
    '${Common/bodyFooter}'                     => buildBodyFooter(),
    '${Common/pageFooter}'                     => buildPageFooter()
));
*/


/*
include_once('../Includes/Init.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/Message.php');

$senderUser = User::new_from_cookie();

if (!$senderUser) {
    show_fatal_error_and_exit('access denied, no user cookie found.');
}

$recipientUserId = get_numeric_param('raid');
if (!$recipientUserId) {
    show_fatal_error_and_exit('raid param is missing');
}

$recipientUser = User::fetch_for_id($recipientUserId);
if (!$recipientUser || !$recipientUser->id) {
    show_fatal_error_and_exit('recipient user not found for id: ' . $recipientUserId);
}

$statusMessage = '';
$errorMessage  = '';

$action = get_param('action');
if ($action == 'send') {
    $subject = get_param('subject');
    $text    = get_param('text');

    if ($subject || $text) {
        $msg = new Message();
        $msg->sender_user_id    = $senderUser->id;
        $msg->recipient_user_id = $recipientUser->id;
        $msg->subject             = $subject;
        $msg->text                = $text;
        $msg->marked_as_read      = false;
        $msg->save();

        $email_sent = send_email($recipientUser->email_address, 'Message from ' . $senderUser->name,
                'Hey ' . $recipientUser->name . "\n" .
                'A message from ' . $senderUser->name . ' has been stored in your message inbox on ' . $GLOBALS['DOMAIN']);

        if (!$email_sent) {
            $logger->error('Failed to send "new message" notification email!');
        }

        $statusMessage = 'Your message has been sent.';

    } else {
        $errorMessage = 'Your message is empty.';
    }
}

$subject = '';
$text    = '';
$replyToMsgId = get_numeric_param('replyToMsgId');
if ($replyToMsgId) {
    $replyToMsg = Message::fetch_for_id($replyToMsgId);
    ensureMessageBelongsToUser($replyToMsg, $senderUser);
    $subject = 'Re: '   . $replyToMsg->subject;
    $text    = "\n\n\n---- Original message ----\n" .
               'From: ' . $replyToMsg->sender_user_name . "\n" .
               'Subject: ' . $replyToMsg->subject . "\n\n" .
               $replyToMsg->text;
}

writePageDoctype();

if ($statusMessage && !$errorMessage) {
    echo '<html><body onload="javascript:window.close();"><p><b>' . $statusMessage . '</b></p></body></html>';
    exit;
}

?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <?php writePageMetaTags(); ?>
        <title><?php writePageTitle(); ?></title>
        <link rel="stylesheet" href="../Styles/main.css" type="text/css">
    </head>
    <body>
        <div id="sendMessageInner">
<?php

if (!$statusMessage || $errorMessage) {

?>
            <h1><img border="0" src="../Images/Mail_Icon_big.png">&nbsp;Message to <?php echo escape($recipientUser->name); ?>:</h1>
            <br/>
<?php

} // end of if (!$statusMessage) {

?>
            <form name="sendMessageForm" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                <input type="hidden" name="action" value="send">
                <input type="hidden" name="raid" value="<?php echo $recipientUser->id; ?>">
                <table>
<?php

if (!$statusMessage || $errorMessage) {

?>
                    <tr>
                        <td>Subject:&nbsp;</td>
                        <td><input type="text" name="subject" maxlength="255" size="60" value="<?php echo escape($subject); ?>"></td>
                    </tr>
                    <tr>
                        <td valign="top">Text:&nbsp;</td>
                        <td>
                            <textarea name="text" rows="10" cols="50"><?php echo escape($text); ?></textarea>
                        </td>
                    </tr>
<?php

} // end of if (!$statusMessage) {

if ($errorMessage) {
    echo '<tr>' . "\n";
    echo '<td colspan="2" align="right"><span class="problemMessage">' . $errorMessage . '</span></td>' . "\n";
    echo '</tr>' . "\n";
}

if ($statusMessage) {
    echo '<tr>' . "\n";
    echo '<td colspan="2" align="right"><span class="noticeMessage">' . $statusMessage . '</span></td>' . "\n";
    echo '</tr>' . "\n";
}

if (!$statusMessage || $errorMessage) {

?>
                    <tr>
                        <td colspan="2" align="right"><input type="submit" value="Send"></td>
                    </tr>
<?php

} // end of if (!$statusMessage) {

?>
                </table>
            </form>

            <!-- <div id="senMessageClose">Close</div> -->

        </div> <!-- sendMessageInner -->
        <?php writeGoogleAnalyticsStuff(); ?>

    </body>
</html>
*/

