<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/Mailer/MailUtil.php');

$user = null;
$problemOccured = false;
$errorMsg = '';

$userIsLoggedIn = false;
$user = User::new_from_cookie();
if ($user) {
    $userIsLoggedIn = true;
    $logger->info('user is logged in');

} else {
    $logger->info('user is NOT logged in');
}

$action = get_param('action');

$instructionsSent       = false;
$passwordWillBeEntered  = false;
$passwordWasSet         = false;
$email = '';
$checksum = '';

if ($action == 'sendInstructions') {
    $email = get_param('email');
    if ($email) {
        $user = User::fetch_for_email_address($email);
        if (!$user) {
            $problemOccured = true;
            $errorMsg = 'No user found for email address: ' . $email;
        }

    } else {
        $problemOccured = true;
        $errorMsg = 'Please enter your email address.';
    }

    if (!$problemOccured) {
        $logger->info('sending pwd reset instructions to user with email "' . $user->email_address . '"');

        $resetPasswordUrl = $GLOBALS['BASE_URL'] . 'resetPassword' .
                            '?action=enterPwd' .
                            '&email=' . urlencode($user->email_address) .
                            '&cs=' . md5('HurziHurziBrrrigidigibab!' . $user->email_address);

        $text = processTpl('ResetPassword/instructionsEmail.txt', array(
            '${resetPasswordUrl}' => $resetPasswordUrl
        ));

        $emailSent = sendEmail($user->email_address, 'Password reset instructions', $text);

        if ($emailSent) {
            $instructionsSent = true;
        } else {
            show_fatal_error_and_exit('failed to send password reset instructions email!');
        }
    }

} else if ($action == 'enterPwd') {
    $email = get_param('email');
    $checksum = get_param('cs');

    if (!$email) {
        show_fatal_error_and_exit('email param is missing!');
    }

    if (!$checksum) {
        show_fatal_error_and_exit('cs param is missing!');
    }

    // validate checksum
    if (md5('HurziHurziBrrrigidigibab!' . $email) != $checksum) {
        show_fatal_error_and_exit('checksum validation failed!');
    }

    $user = User::fetch_for_email_address($email);
    if (!$user) {
        show_fatal_error_and_exit('No user found for email address: ' . $email);
    }

    $passwordWillBeEntered = true;

} else if ($action == 'setPwd') {
    $email = get_param('email');
    $checksum = get_param('cs');
    $newPassword = get_param('newPassword');

    if (!$email) {
        show_fatal_error_and_exit('email param is missing!');
    }

    if (!$checksum) {
        show_fatal_error_and_exit('cs param is missing!');
    }

    // validate checksum
    if (md5('HurziHurziBrrrigidigibab!' . $email) != $checksum) {
        show_fatal_error_and_exit('checksum validation failed!');
    }

    $user = User::fetch_for_email_address($email);
    if (!$user) {
        show_fatal_error_and_exit('No user found for email address: ' . $email);
    }

    $logger->info('setting new password for user');
    $user->password_md5 = md5($newPassword);
    $user->save();

    $passwordWasSet = true;
}

$messageList = '';
if ($errorMsg) {
    $messageList .= processTpl('Common/message_error.html', array(
        '${msg}' => escape($errorMsg)
    ));
}

$instructionsWereSentBlock    = '';
$enterNewPasswordFormBlock    = '';
$passwordWasSetBlock          = '';
$resetPasswordFormBlock       = '';
if ($instructionsSent) {
    $instructionsWereSentBlock = processTpl('ResetPassword/instructionsWereSent.html', array());

} else if ($passwordWillBeEntered) {
    $enterNewPasswordFormBlock = processTpl('ResetPassword/enterNewPasswordForm.html', array(
        '${formAction}' => '', // self
        '${email}'      => $email,
        '${checksum}'   => $checksum
    ));

} else if ($passwordWasSet) {
    $passwordWasSetBlock = processTpl('ResetPassword/passwordWasSet.html', array());

} else {
    $resetPasswordFormBlock = processTpl('ResetPassword/resetPasswordForm.html', array(
        '${formAction}' => '' // self
    ));
}

processAndPrintTpl('ResetPassword/index.html', array(
    '${Common/pageHeader}'                           => buildPageHeader('Reset password'),
    '${Common/bodyHeader}'                           => buildBodyHeader(null), // never put the $user var here because on this page the user is never logged in
    '${Common/message_choice_list}'                  => $messageList,
    '${ResetPassword/instructionsWereSent_optional}' => $instructionsWereSentBlock,
    '${ResetPassword/enterNewPasswordForm_optional}' => $enterNewPasswordFormBlock,
    '${ResetPassword/passwordWasSet_optional}'       => $passwordWasSetBlock,
    '${ResetPassword/resetPasswordForm_optional}'    => $resetPasswordFormBlock,
    '${Common/bodyFooter}'                           => buildBodyFooter(),
    '${Common/pageFooter}'                           => buildPageFooter()
));

// END

// functions
// -----------------------------------------------------------------------------
function generatePassword($length = 8) {
    // start with a blank password
    $password = "";

    // define possible characters
    $possible = "0123456789bcdfghjkmnpqrstvwxyz";

    // set up a counter
    $i = 0;

    // add random characters to $password until $length is reached
    while ($i < $length) {
        // pick a random character from the possible ones
        $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);

        // we don't want this character if it's already in the password
        if (!strstr($password, $char)) {
            $password .= $char;
            $i++;
        }
    }

    return $password;
}

?>
