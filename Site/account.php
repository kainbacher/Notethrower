<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Config.php');
include_once('../Includes/FormUtil.php');
include_once('../Includes/recaptchalib.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');

$user = null;
$unpersistedUser = null;
$message = get_param('msg');
$problemOccured = false;
$errorFields = Array();

$userIsLoggedIn = false;
$user = User::new_from_cookie();
if ($user) {
    $userIsLoggedIn = true;
    $logger->info('user is logged in');

} else {
    $logger->info('user is NOT logged in');
}

if (get_param('action') == 'save') {
    $logger->info('attempting to save user account data ...');
    if (inputDataOk($errorFields, $user, $userIsLoggedIn)) {
        if (!$userIsLoggedIn) {
            $user = new User();
        }

        $oldPasswordMd5 = $user->password_md5;

        processParams($user, true, $userIsLoggedIn);

        // check if a url was entered or if there's still only the predefined value
        if ($user->webpage_url == 'http://') $user->webpage_url = '';

        // the newly created account needs to be activated first
        if (!$userIsLoggedIn) {
            $user->status = 'inactive';
        }

        $user->save();

        $newPasswordMd5 = $user->password_md5;

        $message = 'Successfully created user account!';
        if ($userIsLoggedIn) {
            $message = 'Successfully updated user account!';

            if ($oldPasswordMd5 != $newPasswordMd5) {
                $user->doLogin();
                $logger->info('password change was successful, reloading page to set cookie');
                header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=' . urlencode($message));
                exit;
            }

        } else {
            $logger->info('created user record with id: ' . $user->id);

            $email_sent = send_email($user->email_address, 'Please activate your notethrower.com account',
                    'Please click the link below to confirm your notethrower.com account creation:' . "\n\n" .
                    $GLOBALS['BASE_URL'] . 'Site/accountCreationConfirmed.php' .
                    '?x=' . $user->id . '&c=' . md5('TheSparrowsAreFlyingAgain!' . $user->id));

            if (!$email_sent) {
                $message = 'Failed to send confirmation email after creation of account!'; // FIXME - test behaviour in this case
                $problemOccured = true;

            } else {
                header('Location: accountCreated.php');
                exit;
            }
        }

    } else {
        $logger->info('input data was invalid: ' . join(', ', $errorFields));
        $unpersistedUser = new User();
        processParams($unpersistedUser, false, $userIsLoggedIn);
        $message = 'Please correct the highlighted problems!';
        $problemOccured = true;
    }
}

// prefill form with some values if present
if (!$user) {
    $user = new User();
    $user->webpageUrl = 'http://';
    processParams($user, false, $userIsLoggedIn);
}

$messageList = '';
if ($message) {
    if ($problemOccured) {
        $messageList .= processTpl('Common/message_error.html', array(
            '${msg}' => escape($message)
        ));
    } else {
        $messageList .= processTpl('Common/message_success.html', array(
            '${msg}' => escape($message)
        ));
    }
}

$pageMode = getPageMode($userIsLoggedIn, $user);

$headline = '';
if ($userIsLoggedIn) {
    $headline = 'Update user account';
} else {
    if ($pageMode == 'artist') {
        $headline = 'Create new artist account';
    } else {
        $headline = 'Create new fan account';
    }
}

// we have two form element sections because we show the user image next to the image upload field
$formElementsSection1 = '';
$formElementsSection2 = '';

if ($pageMode == 'artist') {
    $formElementsSection1 .= getFormFieldForParams(array(
        'propName'               => 'name',
        'label'                  => 'Artist/Band name',
        'mandatory'              => true,
        'maxlength'              => 255,
        'obj'                    => $user,
        'unpersistedObj'         => $unpersistedUser,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'Please put your band or artist name in the field.'
    ));
}

$formElementsSection1 .= getFormFieldForParams(array(
    'propName'               => 'username',
    'label'                  => 'Username',
    'mandatory'              => true,
    'maxlength'              => 255,
    'obj'                    => $user,
    'unpersistedObj'         => $unpersistedUser,
    'errorFields'            => $errorFields,
    'workWithUnpersistedObj' => $problemOccured,
    'infoText'               => 'This is your username for logging in to Notethrower.' . ($pageMode == 'artist' ? '<br>If you want to provide a different name than your artist profile, you may do so.' : '')
));

$formElementsSection1 .= getFormFieldForParams(array(
    'propName'               => 'email_address',
    'label'                  => 'Email address',
    'mandatory'              => true,
    'maxlength'              => 255,
    'obj'                    => $user,
    'unpersistedObj'         => $unpersistedUser,
    'errorFields'            => $errorFields,
    'workWithUnpersistedObj' => $problemOccured,
    'infoText'               => 'We will send you a verification link to this address to login. If another user sends you a message, a notification will also be sent here.<br>We will never give out your email or spam you. Aren\'t we nice?'
));

if ($pageMode == 'artist') {
    $formElementsSection1 .= getFormFieldForParams(array(
        'propName'               => 'webpage_url',
        'label'                  => 'Webpage URL',
        'mandatory'              => false,
        'maxlength'              => 255,
        'obj'                    => $user,
        'unpersistedObj'         => $unpersistedUser,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'If you have another place you would like your fans to find you, please enter the link here. For example, your MySpace or Facebook page.'
    ));

    $formElementsSection1 .= getFormFieldForParams(array(
        'propName'               => 'paypal_account',
        'label'                  => 'Paypal account',
        'mandatory'              => false,
        'maxlength'              => 255,
        'obj'                    => $user,
        'unpersistedObj'         => $unpersistedUser,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'This is where your earnings will be sent. <a href="http://www.paypal.com" target="_blank">Get a PayPal account!</a><br>You can always add this later, but we need it in order to pay you.  We\'ve made it extremely easy for you to get paid for licensing your work. If someone has remixed your work and made it available in their widget, You get paid 50% of the earnings from the sale of that work. Now imagine if there are hundreds of remixed versions of your track all available for licensing. Many more opportunities to get paid for your initial work.'
    ));
}

$userImage = '';

if ($userIsLoggedIn) { // it's an update
    $formElementsSection1 .= getFormFieldForParams(array(
        'inputType'              => 'file',
        'propName'               => 'image_filename',
        'label'                  => 'User image',
        'mandatory'              => false,
        'maxlength'              => 255,
        'obj'                    => $user,
        'unpersistedObj'         => $unpersistedUser,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'You can add this later if you like. Photos may not contain nudity, violent or offensive material, or copyrighted images. If you violate these terms your account may be deleted.'
    ));

    if ($user->image_filename) {
        $userImage = processTpl('Account/userImage_found.html', array(
            '${imgSrc}'  => $USER_IMAGE_BASE_URL . $user->image_filename,
            '${altText}' => escape($user->name)
        ));

    } else {
        $userImage = processTpl('Account/userImage_notFound.html', array());
    }

    $formElementsSection2 .= getFormFieldForParams(array(
        'inputType'              => 'password',
        'propName'               => 'old_password',
        'label'                  => 'Password',
        'mandatory'              => false,
        'maxlength'              => 255,
        'obj'                    => $user,
        'unpersistedObj'         => $unpersistedUser,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'If you want to change your password, enter you old password here.'
    ));

    $formElementsSection2 .= getFormFieldForParams(array(
        'inputType'              => 'password',
        'propName'               => 'password',
        'label'                  => 'New password',
        'mandatory'              => false,
        'maxlength'              => 255,
        'obj'                    => $user,
        'unpersistedObj'         => $unpersistedUser,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'Enter your new password here if you want to change it.'
    ));

    $formElementsSection2 .= getFormFieldForParams(array(
        'inputType'              => 'password',
        'propName'               => 'password2',
        'label'                  => 'Password verification',
        'mandatory'              => false,
        'maxlength'              => 255,
        'obj'                    => $user,
        'unpersistedObj'         => $unpersistedUser,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'Enter your new password again, for verification.'
    ));

} else { // it's an insert
    $formElementsSection2 .= getFormFieldForParams(array(
        'inputType'              => 'password',
        'propName'               => 'password',
        'label'                  => 'Password',
        'mandatory'              => true,
        'maxlength'              => 255,
        'obj'                    => $user,
        'unpersistedObj'         => $unpersistedUser,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'Create your own password and use this for logging in. If you ever forget it, we can email you a new one or you can change it in the future.'
    ));

    $formElementsSection2 .= getFormFieldForParams(array(
        'inputType'              => 'password',
        'propName'               => 'password2',
        'label'                  => 'Password verification',
        'mandatory'              => true,
        'maxlength'              => 255,
        'obj'                    => $user,
        'unpersistedObj'         => $unpersistedUser,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'This should be pretty easy. Do you remember the password you just created? Type it here.'
    ));
}

if ($pageMode == 'artist') {
    $formElementsSection2 .= getFormFieldForParams(array(
        'inputType'              => 'textarea',
        'propName'               => 'artist_info',
        'label'                  => 'Artist/Band information',
        'mandatory'              => false,
        'obj'                    => $user,
        'unpersistedObj'         => $unpersistedUser,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'Tell us a bit about yourself or your band. What other bands or music influenced you? Where are you from?'
    ));

    $formElementsSection2 .= getFormFieldForParams(array(
        'inputType'              => 'textarea',
        'propName'               => 'additional_info',
        'label'                  => 'Additional information',
        'mandatory'              => false,
        'obj'                    => $user,
        'unpersistedObj'         => $unpersistedUser,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'Anything else we should know?'
    ));
}

if (!$userIsLoggedIn) {
    if ($pageMode == 'artist') {
        $formElementsSection2 .= getFormFieldForParams(array(
            'inputType'              => 'checkbox',
            'propName'               => 'terms_accepted',
            'label'                  => 'Artist Agreement',
            'mandatory'              => true,
            'obj'                    => $user,
            'unpersistedObj'         => $unpersistedUser,
            'errorFields'            => $errorFields,
            'workWithUnpersistedObj' => $problemOccured,
            'infoText'               => 'I\'ve read and agree to <a href="javascript:showTermsAndConditions();">Notethrower\'s Artist Agreement</a>.'
        ));
    }

    $formElementsSection2 .= getFormFieldForParams(array(
        'inputType'              => 'recaptcha',
        'propName'               => 'captcha',
        'label'                  => 'Verification',
        'recaptchaPublicKey'     => $GLOBALS['RECAPTCHA_PUBLIC_KEY'],
        'mandatory'              => true,
        'obj'                    => $user,
        'unpersistedObj'         => $unpersistedUser,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'Show us that you are human. After you create your account, check your email for a verification link to sign in.'
    ));
}

processAndPrintTpl('Account/index.html', array(
    '${Common/pageHeader}'                    => buildPageHeader('Account', true, false),
    '${Common/bodyHeader}'                    => buildBodyHeader(),
    '${headline}'                             => $headline,
    '${Common/message_choice_list}'           => $messageList,
    '${formAction}'                           => $_SERVER['PHP_SELF'],
    '${signupAs}'                             => get_param('signupAs'),
    '${Common/formElement_section1_list}'     => $formElementsSection1,
    '${userImage_choice}'                     => $userImage,
    '${Common/formElement_section2_list}'     => $formElementsSection2,
    '${submitButtonClass}'                    => $userIsLoggedIn ? 'updateAccountButton' : 'createAccountButton',
    '${submitButtonValue}'                    => $userIsLoggedIn ? 'update Account' : 'create Account',
    '${Common/bodyFooter}'                    => buildBodyFooter(),
    '${Common/pageFooter}'                    => buildPageFooter()
));

// END

// functions
// -----------------------------------------------------------------------------
function inputDataOk(&$errorFields, &$user, $userIsLoggedIn) {
    global $logger;

    $pageMode = getPageMode($userIsLoggedIn, $user);

    $result = true;

    $pwd    = get_param('password');
    $pwd2   = get_param('password2');
    $oldPwd = get_param('old_password');

    if ($userIsLoggedIn) { // update operation - user has to specify old password -> update: currently not desired
        if (!$oldPwd) {
//            $errorFields['old_password'] = 'Please enter your current password!';
//            $result = false;

        } else if ($user->password_md5 !== md5($oldPwd)) {
            $errorFields['old_password'] = 'Invalid password!';
            $result = false;
        }
    }

    if ($userIsLoggedIn) { // update operation - user *can* change the password
        if (!$pwd && !$pwd2) {
            // noop

        } else if (!$pwd || !$pwd2) { // one of the pwds is missing
            $errorFields['password']  = 'Password or password verification is missing!';
            $errorFields['password2'] = 'Password or password verification is missing!';
            $result = false;

        } else { // both pwds are present
            if ($pwd != $pwd2) {
                $errorFields['password']  = 'Passwords do not match!';
                $errorFields['password2'] = 'Passwords do not match!';
                $result = false;
            }

            if (!$oldPwd) {
                $errorFields['old_password'] = 'Please enter your current password!';
                $result = false;
            }
        }

    } else { // insert operation
        if (!$pwd || !$pwd2) {
            $errorFields['password']  = 'Password or password verification is missing!';
            $errorFields['password2'] = 'Password or password verification is missing!';
            $result = false;

        } else {
            if ($pwd != $pwd2) {
                $errorFields['password']  = 'Passwords do not match!';
                $errorFields['password2'] = 'Passwords do not match!';
                $result = false;
            }
        }
    }

    // check (artist) name
    if ($pageMode == 'artist') {
        if (strlen(get_param('name')) < 1) {
            $errorFields['name'] = 'Name is missing!';
            $result = false;

        } else {
            $checkUser = User::fetch_for_name(get_param('name'));
            if ($checkUser) {
                if (!$userIsLoggedIn) { // if user is created from scratch
                    $errorFields['name'] = 'Name already in use! Please choose a different one.';
                    $result = false;

                } else { // user data update
                    if ($user->name != get_param('name')) { // display an error only if the name was changed in the update process
                        $errorFields['name'] = 'Name already in use! Please choose a different one.';
                        $result = false;
                    }
                }
            }
        }

    } else { // fan mode
        // if the user signs up as a fan only, the username is used as the (artist) name, too.
        if (strlen(get_param('username')) > 0) {
            $checkUser = User::fetch_for_name(get_param('username'));
            if ($checkUser) {
                if (!$userIsLoggedIn) { // if user is created from scratch
                    $errorFields['username'] = 'Name already in use! Please choose a different one.';
                    $result = false;

                } else { // user data update
                    if ($user->name != get_param('username')) { // display an error only if the name was changed in the update process
                        $errorFields['username'] = 'Name already in use! Please choose a different one.';
                        $result = false;
                    }
                }
            }
        }
    }

    if (strlen(get_param('username')) < 1) {
        $errorFields['username'] = 'Username is missing!';
        $result = false;
    }

    $checkUser = User::fetch_for_username(get_param('username'));
    if ($checkUser) {
        if (!$userIsLoggedIn) { // if user is created from scratch
            $errorFields['username'] = 'Name already in use! Please choose a different one.';
            $result = false;

        } else { // user data update
            if ($user->username != get_param('username')) { // display an error only if the name was changed in the update process
                $errorFields['username'] = 'Name already in use! Please choose a different one.';
                $result = false;
            }
        }
    }

    if (strlen(get_param('email_address')) < 1) {
        $errorFields['email_address'] = 'Email address is missing!';
        $result = false;

    } else {
        if (!email_syntax_ok(get_param('email_address'))) {
            $errorFields['email_address'] = 'Email address is invalid!';
            $result = false;

        } else {
            $checkUser = User::fetch_for_email_address(get_param('email_address'));
            if ($checkUser) {
                if (!$userIsLoggedIn) { // if user is created from scratch
                    $errorFields['email_address'] = 'Email address already in use! Please choose a different one.';
                    $result = false;

                } else { // user data update
                    if ($user->email_address != get_param('email_address')) { // display an error only if the address was changed in the update process
                        $errorFields['email_address'] = 'Email address already in use! Please choose a different one.';
                        $result = false;
                    }
                }
            }
        }
    }

    if (isset($_FILES['image_filename']['name']) && $_FILES['image_filename']['name'] && !preg_match('/jpg$/i', $_FILES['image_filename']['name'])) {
        $errorFields['image_filename'] = 'Image must be in JPG format!';
        $result = false;
    }

    if ($pageMode == 'artist') {
        if (!$userIsLoggedIn && get_param('terms_accepted') != 'yes') {
            $errorFields['terms_accepted'] = 'You need to agree to Notethrower\'s Artist Agreement in order to sign up.';
            $result = false;
        }
    }

    // check captcha input
    if (!$userIsLoggedIn && ($GLOBALS['STAGING_ENV'] == 'test' || $GLOBALS['STAGING_ENV'] == 'live')) {
        $privatekey = '6LcNIgoAAAAAACwnTjRcKFmzPy8G02o_n5AT_PX_';
        $resp = recaptcha_check_answer($privatekey,
                                       $_SERVER["REMOTE_ADDR"],
                                       $_POST["recaptcha_challenge_field"],
                                       $_POST["recaptcha_response_field"]);

        if (!$resp->is_valid) {
            $errorFields['captcha'] = 'The reCAPTCHA wasn\'t entered correctly.';
            $logger->warn('captcha check failed: ' . $resp->error);
            $result = false;
        }
    }

    return $result;
}

function processParams(&$user, $uploadAllowed, $userIsLoggedIn) {
    global $logger;

    $pageMode = getPageMode($userIsLoggedIn, $user);

    $user->username        = get_param('username');
    $user->email_address   = get_param('email_address');

    if ($pageMode == 'artist') {
        $user->is_artist       = true;
        $user->webpage_url     = get_param('webpage_url');
        $user->name            = get_param('name');
        $user->artist_info     = get_param('artist_info');
        $user->additional_info = get_param('additional_info');
        $user->paypal_account  = get_param('paypal_account');

    } else {
        $user->is_artist       = false;
        $user->name            = get_param('username'); // use the username as (artist) name as long as the user is just a fan
    }

    if (get_param('password')) { // this can be empty when an account is updated without a password change. we musst not save an empty password then.
        $user->password_md5 = md5(get_param('password'));
    }

    // handle user image upload
    if ($uploadAllowed && isset($_FILES['image_filename']['name']) && $_FILES['image_filename']['name']) {
        $logger->info('processing file upload: ' . $_FILES['image_filename']['name']);

        $userImgSubdir = null;
        if (ini_get('safe_mode')) {
            $userImgSubdir = ''; // in safe mode we're not allowed to create directories
        } else {
            $userImgSubdir = md5('Wuizi' . $user->id);
        }
        $upload_dir = $GLOBALS['USER_IMAGE_BASE_PATH'] . $userImgSubdir;

        // upload to tmp file
        $upload_filename = $user->id . '_' . time() . '.jpg';
        do_upload($upload_dir, 'image_filename', $upload_filename);
        $upload_img_file = $upload_dir . $GLOBALS['PATH_SEPARATOR'] . $upload_filename;
        $final_img_filename = md5('Wuizi' . $user->id) . '.jpg'; // must be unique (see safe mode logic above)
        $final_thumb_img_filename = md5('Wuizi' . $user->id) . '_thumb.jpg'; // must be unique (see safe mode logic above)
        $final_img_file = $upload_dir . $GLOBALS['PATH_SEPARATOR'] . $final_img_filename;
        $final_thumb_img_file = $upload_dir . $GLOBALS['PATH_SEPARATOR'] . $final_thumb_img_filename;

        $logger->info('resizing uploaded image');
        umask(0777); // most probably ignored on windows systems
        create_resized_jpg($upload_img_file, $final_img_file, $GLOBALS['USER_IMG_MAX_WIDTH'], $GLOBALS['USER_IMG_MAX_HEIGHT']);
        create_resized_jpg($upload_img_file, $final_thumb_img_file, $GLOBALS['USER_THUMB_MAX_WIDTH'], $GLOBALS['USER_THUMB_MAX_HEIGHT']);
        chmod($final_img_file, 0666);
        chmod($final_thumb_img_file, 0666);

        unlink($upload_img_file);

        $user->image_filename = ($userImgSubdir ? $userImgSubdir . '/' : '') . $final_img_filename;

        $logger->info('user image filename: ' . $user->image_filename);
    }
}

function getPageMode($userIsLoggedIn, &$user) {
    $mode = 'artist';
    if (get_param('signupAs') && get_param('signupAs') == 'fan') $mode = 'fan';
    if ($userIsLoggedIn && $user->is_artist) $mode = 'artist';

    return $mode;
}

?>