<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');

$user = null;
$unpersistedUser = null;
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

$passwordSent = false;

if ($action == 'generatePwd') {
    $username = get_param('username');
    $email = get_param('email');
    if ($username != '') {
        $user = User::fetch_for_username($username);
        if (!$user) {
            $problemOccured = true;
            $errorMsg = 'No user found for username: ' . $username;
        }
    } else if ($email != '') {
        $user = User::fetch_for_email_address($email);
        if (!$user) {
            $problemOccured = true;
            $errorMsg = 'No user found for email address: ' . $email;
        }
    } else {
        $problemOccured = true;
        $errorMsg = 'No username or email address specified';
    }

    if (!$problemOccured) {
        $logger->info('creating new password for user');

        // generate new password and save it in user record
        $newPassword = generatePassword();
        $user->password_md5 = md5($newPassword);
        $user->save();

        $logger->info('sending new password for user "' . $username . '" with email "' . $user->email_address . '"');

        $text = 'Please do not respond to this email. This is an automatically generated response.' . "\n";
        $text .= 'You received this email because you requested a reset of your account password for http://www.notethrower.com' . "\n";
        $text .= 'Please log in with the new secure password.  If you want to change your new password, you can do so in your "edit profile" tab.' . "\n\n";
        $text .= 'Your username: ' . $user->username . "\n";
        $text .= 'Your new password: ' . $newPassword  . "\n\n";
        $text .= 'Thank you!' . "\n";
        $text .= 'The Notethrower Team';

        $emailSent = send_email($user->email_address, 'Your new password', $text);

        if (!$emailSent) {
            show_fatal_error_and_exit('failed to send new password email!');

        } else {
            $passwordSent = true;
        }
    }
}

$messageList = '';
if ($errorMsg) {
    $messageList .= processTpl('Common/message_error.html', array(
        '${msg}' => escape($errorMsg)
    ));
}

$resetPasswordFormBlock = '';
$newPasswordWasSentBlock = '';
if ($passwordSent) {
    $newPasswordWasSentBlock = processTpl('GenerateNewPassword/newPasswordWasSent.html', array());

} else {
    $resetPasswordFormBlock = processTpl('GenerateNewPassword/resetPasswordForm.html', array(
        '${formAction}' => $_SERVER['PHP_SELF']
    ));
}

processAndPrintTpl('GenerateNewPassword/index.html', array(
    '${Common/pageHeader}'                               => buildPageHeader('Reset password'),
    '${Common/bodyHeader}'                               => buildBodyHeader($user),
    '${Common/message_choice_list}'                      => $messageList,
    '${GenerateNewPassword/newPasswordWasSent_optional}' => $newPasswordWasSentBlock,
    '${GenerateNewPassword/resetPasswordForm_optional}'  => $resetPasswordFormBlock,
    '${Common/bodyFooter}'                               => buildBodyFooter(),
    '${Common/pageFooter}'                               => buildPageFooter()
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