<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/Mailer/MailUtil.php');

$user = User::new_from_cookie();

$message = null;
$notice  = null;
$error   = null;

if (get_param('action') == 'send') {
    $firstName = get_param('firstName');
    $lastName  = get_param('lastName');
    $email     = get_param('email');
    $msg       = get_param('msg');

    if ($msg) {
        $stagingInfo = '';
        if ($GLOBALS['STAGING_ENV'] != 'live') $stagingInfo = ' (' . $GLOBALS['STAGING_ENV'] . ' system)';

        $mailtext = 'Message via contact form' . $stagingInfo . ':'  . "\n\n";
        $mailtext .= 'First name: ' . $firstName . "\n\n";
        $mailtext .= 'Last name: '  . $lastName  . "\n\n";
        $mailtext .= 'Email: '      . $email     . "\n\n";
        $mailtext .= 'Message: '    . $msg       . "\n\n";

        $email_sent = sendEmail($GLOBALS['CONTACT_FORM_RECIPIENT_EMAIL'], 'User message (via contact form)', $mailtext);

        if (!$email_sent) {
            $error = 'Failed to send message to oneloudr.com! Please try again later.';

        } else {
            $message = 'Your message was sent. Thank you!';
        }

    } else {
        $notice = 'Please enter a message.';
    }
}

$msgsAndErrorsList = '';

if ($error) {
    $msgsAndErrorsList .= processTpl('Common/message_error.html', array(
        '${msg}' => escape($error)
    ));
}

if ($notice) {
    $msgsAndErrorsList .= processTpl('Common/message_notice.html', array(
        '${msg}' => escape($notice)
    ));
}

if ($message) {
    $msgsAndErrorsList .= processTpl('Common/message_success.html', array(
        '${msg}' => escape($message)
    ));
}

processAndPrintTpl('Contact/index.html', array(
    '${Common/pageHeader}'          => buildPageHeader('Contact'),
    '${Common/bodyHeader}'          => buildBodyHeader($user),
    '${Common/message_choice_list}' => $msgsAndErrorsList,
    '${formAction}'                 => basename($_SERVER['PHP_SELF'], '.php'),
    '${email_optional}'             => $user ? escape($user->email_address) : '',
    '${Common/bodyFooter}'          => buildBodyFooter(),
    '${Common/pageFooter}'          => buildPageFooter()
));

// END
