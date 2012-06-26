<?php

include_once('../Includes/Config.php');
include_once('../Includes/Init.php');
include_once('../Includes/recaptchalib.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');

$user = null;
$unpersistedUser = null;
$message = get_param('msg');
$problemOccured = false;
$errorFields = Array();

// FIXME - rename the script - it's also used for updates

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
                    $GLOBALS['BASE_URL'] . 'accountCreationConfirmed' .
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

// prefill form with some values if present
if (!$user) {
    $user = new User();
    processParams($user, false, $userIsLoggedIn);
}

writePageDoctype();

?>
<html>
	<head>
		<? include ("headerData.php"); ?>
	</head>

	<body>
		<div id="bodyWrapper">
            <? include ("pageHeader.php"); ?>
            <? include ("mainMenu.php"); ?>

    	<div class="container">


      		<div class="span-16 last box-grey">
      		    <div id="container">

<?php

$pageMode = getPageMode($userIsLoggedIn, $user);

if ($userIsLoggedIn) {
    echo '<h1>Update user account:</h1>';

} else {
    if ($pageMode == 'artist') {
        echo '<h1>Create new artist account:</h1>';

    } else {
        echo '<h1>Create new fan account:</h1>';
    }
}


?>
        

        <div id="userFormPlusImage">

          <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="signupAs" value="<?= get_param('signupAs') ?>">
            <table class="userFormTable">
              <tr>
                <td colspan="3">
<?php

if ($message) {
    if ($problemOccured) {
        echo '<span class="problemMessage">' . $message . '</span><br><br>';
    } else {
        echo '<span class="noticeMessage">'  . $message . '</span><br><br>';
    }
}

?>
                </td>
              </tr>
<?php

if ($pageMode == 'artist') {
    showFormField('Artist/Band name',       'text',     'name',             '', '<div class="toolTipWrapper"><div class="toolTip"><img src="../Images/icons/icon_info.png" alt="icon_info" width="16" height="16" /></div><div class="toolTipContent">Please put your band or artist name in the field.</div></div>', true,  255, $user, $unpersistedUser, $problemOccured, $errorFields);
}

showFormField('Username',                   'text',     'username',         '', '<div class="toolTipWrapper"><div class="toolTip"><img src="../Images/icons/icon_info.png" alt="icon_info" width="16" height="16" /></div><div class="toolTipContent">This is your username for logging in to Notethrower.' . ($pageMode == 'artist' ? '<br>If you want to provide a different name than your artist profile, you may do so.' : '') . '</div></div>', true,  255, $user, $unpersistedUser, $problemOccured, $errorFields);
showFormField('Email address',              'text',     'email_address',    '', '<div class="toolTipWrapper"><div class="toolTip"><img src="../Images/icons/icon_info.png" alt="icon_info" width="16" height="16" /></div><div class="toolTipContent">We will send you a verification link to this address to login.  If another user sends you a message, a notification will also be sent here.<br>We will never give out your email or spam you. Aren\'t we nice?</div></div>', true,  255, $user, $unpersistedUser, $problemOccured, $errorFields);

if ($pageMode == 'artist') {
    showFormField('Webpage URL',            'text',     'webpage_url',      '', '<div class="toolTipWrapper"><div class="toolTip"><img src="../Images/icons/icon_info.png" alt="icon_info" width="16" height="16" /></div><div class="toolTipContent">If you have another place you would like your fans to find you, please enter the link here. For example, your MySpace or Facebook page.</div></div>', false, 255, $user, $unpersistedUser, $problemOccured, $errorFields, 'http://');
    showFormField('Paypal account',         'text',     'paypal_account',   '', '<div class="toolTipWrapper"><div class="toolTip"><img src="../Images/icons/icon_info.png" alt="icon_info" width="16" height="16" /></div><div class="toolTipContent">This is where your earnings will be sent. <a href="http://www.paypal.com" target="_blank">Get a PayPal account!</a><br>You can always add this later, but we need it in order to pay you.  We\'ve made it extremely easy for you to get paid for licensing your work. If someone has remixed your work and made it available in their widget, You get paid 50% of the earnings from the sale of that work. Now imagine if there are hundreds of remixed versions of your track all available for licensing. Many more opportunities to get paid for your initial work.</div></div>', false, 255, $user, $unpersistedUser, $problemOccured, $errorFields);
}

if ($userIsLoggedIn) { // it's an update
    showFormField('User image',             'file',     'image_filename',   '', '<div class="toolTipWrapper"><div class="toolTip"><img src="../Images/icons/icon_info.png" alt="icon_info" width="16" height="16" /></div><div class="toolTipContent">You can add this later if you like. Photos may not contain nudity, violent or offensive material, or copyrighted images. If you violate these terms your account may be deleted.</div></div>', false, 255, $user, $unpersistedUser, $problemOccured, $errorFields);

    echo '<tr><td>Current user image:</td>' . "\n";
    echo '<td>' . "\n";
    if ($user->image_filename) {
        echo '<img src="' . $USER_IMAGE_BASE_URL . $user->image_filename . '" alt="' . escape($user->name) . '">';
    } else {
        echo 'No user image uploaded yet.';
    }
    echo '</td><td>&nbsp;</td>' . "\n";
    echo '</tr>' . "\n";

    showFormField('Password',               'password', 'old_password',     '', '<div class="toolTipWrapper"><div class="toolTip"><img src="../Images/icons/icon_info.png" alt="icon_info" width="16" height="16" /></div><div class="toolTipContent">If you want to change your password, enter you old password here.</div></div>', false,  255, $user, $unpersistedUser, $problemOccured, $errorFields);
    showFormField('New Password',           'password', 'password',         '', '<div class="toolTipWrapper"><div class="toolTip"><img src="../Images/icons/icon_info.png" alt="icon_info" width="16" height="16" /></div><div class="toolTipContent">Enter your new password here if you want to change it.</div></div>', false, 255, $user, $unpersistedUser, $problemOccured, $errorFields);
    showFormField('Password verification',  'password', 'password2',        '', 'Enter your new password again, for verification.', false, 255, $user, $unpersistedUser, $problemOccured, $errorFields);

} else { // it's an insert
    showFormField('Password',               'password', 'password',         '', '<div class="toolTipWrapper"><div class="toolTip"><img src="../Images/icons/icon_info.png" alt="icon_info" width="16" height="16" /></div><div class="toolTipContent">Create your own password and use this for logging in. If you ever forget it, we can email you a new one or you can change it in the future.</div></div>', true,  255, $user, $unpersistedUser, $problemOccured, $errorFields);
    showFormField('Password verification',  'password', 'password2',        '', '<div class="toolTipWrapper"><div class="toolTip"><img src="../Images/icons/icon_info.png" alt="icon_info" width="16" height="16" /></div><div class="toolTipContent">This should be pretty easy. Do you remember the password you just created? Type it here.</div></div>', true,  255, $user, $unpersistedUser, $problemOccured, $errorFields);
}

if ($pageMode == 'artist') {
    showFormField('Artist/Band information', 'textarea', 'artist_info',     '', '<div class="toolTipWrapper"><div class="toolTip"><img src="../Images/icons/icon_info.png" alt="icon_info" width="16" height="16" /></div><div class="toolTipContent">Tell us a bit about yourself or your band. What other bands or music influenced you? Where are you from?</div></div>', false, 0,   $user, $unpersistedUser, $problemOccured, $errorFields);
    showFormField('Additional information',  'textarea', 'additional_info', '', '<div class="toolTipWrapper"><div class="toolTip"><img src="../Images/icons/icon_info.png" alt="icon_info" width="16" height="16" /></div><div class="toolTipContent">Anything else we should know?</div></div>', false, 0,   $user, $unpersistedUser, $problemOccured, $errorFields);
}

if (!$userIsLoggedIn) {
    if ($pageMode == 'artist') {
        showFormField('Artist Agreement', 'checkbox',  'terms_accepted', 'I\'ve read and agree to <a href="javascript:showTermsAndConditions();">Notethrower\'s Artist Agreement</a>.', '', true, 0, $user, $unpersistedUser, $problemOccured, $errorFields);
    }

    // captcha
    showFormField('Verification', 'recaptcha', 'captcha', '', '<div class="toolTipWrapper"><div class="toolTip"><img src="../Images/icons/icon_info.png" alt="icon_info" width="16" height="16" /></div><div class="toolTipContent">Show us that you are human. After you create your account, check your email for a verification link to sign in.</div></div>', true, 0, $user, $unpersistedUser, $problemOccured, $errorFields);
}

?>
              <tr>
                <td colspan="3">&nbsp;</td>
              </tr>
              <tr>
                <td colspan="3" align="left">

                <input class="<?php echo $userIsLoggedIn ? 'updateAccountButton' : 'createAccountButton'; ?> button blue" type="submit" value="<?php echo $userIsLoggedIn ? 'update Account' : 'create Account'; ?>">

                </td>
              </tr>
              <tr>
                <td colspan="3">
<?php

if ($message) {
    if ($problemOccured) {
        echo '<span class="problemMessage">' . $message . '</span>';
    } else {
        echo '<span class="noticeMessage">'  . $message . '</span>';
    }
}

?>
                </td>
              </tr>
            </table>
          </form>

        </div>

        <br>
        <br>


        </div>
      </div>


    </div>

    <? include ("footer.php"); ?>

	</div> <!-- bodyWrapper -->

	<?php writeGoogleAnalyticsStuff(); ?>
  </body>

  <script type="text/javascript">

function showTermsAndConditions() {
    window.open('termsAndConditions.php','TERMS_AND_CONDITIONS','scrollbars=yes,resizable=yes,status=0,width=1100,height=600');
}

  </script>
</html>

<?php

function showFormField($label, $inputType, $propName, $suffix, $helpTextHtml, $mandatory, $maxlength, &$user, &$unpersistedUser, $problemOccured, &$errorFields, $preconfiguredValue = null) {
    static $i = 0;

    $class_addon = isset($errorFields[$propName]) ? ' formFieldWithProblem' : '';

    echo '<tr class="standardRow1"' .
         ' onmouseover="this.className=\'highlightedRow\';" onmouseout="this.className=\'standardRow1\';"' .
         '>' . "\n";

    echo '<td class="formLeftCol' . $class_addon . '" style="vertical-align:top">' . ($mandatory ? '<b>' : '') . ($label ? $label . ':' : '&nbsp;') . ($mandatory ? '</b>' : '') . '</td>' . "\n";

    echo '<td class="formMiddleCol ' . ($mandatory ? 'mandatoryFormField' : 'optionalFormField') . '" style="vertical-align:top">';

    $normalClass  = 'inputTextField';
    $problemClass = 'inputTextFieldWithProblem';
    $size = 40;
    if ($inputType == 'file') {
        $normalClass  = 'inputFileField';
        $problemClass = 'inputFileFieldWithProblem';
        $size = 16;
    }

    if ($inputType == 'textarea') {
        echo '<textarea class="' . (isset($errorFields[$propName]) ? $problemClass : $normalClass) . '" name="' . $propName . '" rows="6" cols="40">';
        $userVal            = null;
        $unpersistedUserVal = null;
        eval('if ($user) $userVal = $user->' . $propName . ';');
        eval('if ($unpersistedUser) $unpersistedUserVal = $unpersistedUser->' . $propName . ';');
        echo !$problemOccured ? escape($userVal) : ($unpersistedUser ? escape($unpersistedUserVal) : '');
        echo '</textarea>' . "\n";

    } else if ($inputType == 'recaptcha') {
        $publickey = '6LcNIgoAAAAAAP0BgB5wNty92PiCewdRq7y5L6qw';
        echo recaptcha_get_html($publickey);

    } else {
        echo '<input class="' . (isset($errorFields[$propName]) ? $problemClass : $normalClass) . '" type="' . $inputType . '" name="' . $propName . '" maxlength="' . $maxlength . '" size="' . $size . '"';

        if ($inputType != 'password' && $inputType != 'file' && $inputType != 'checkbox') {
            echo ' value="';
            $userVal            = null;
            $unpersistedUserVal = null;
            eval('if ($user) $userVal = $user->' . $propName . ';');
            eval('if ($unpersistedUser) $unpersistedUserVal = $unpersistedUser->' . $propName . ';');
            echo !$problemOccured ? ($preconfiguredValue && !$userVal ? escape($preconfiguredValue) : escape($userVal)) : ($unpersistedUser ? escape($unpersistedUserVal) : '');
            echo '"';

        } else if ($inputType == 'checkbox') {
            echo ' value="yes"';
        }

        echo '>' . "\n";
    }

    if ($suffix) {
        echo '&nbsp;' . $suffix;
    }

    echo '</td>' . "\n";

    echo '<td style="vertical-align:top">';
//    if (isset($errorFields[$propName])) {
//        echo '&nbsp;' . $errorFields[$propName];
//    } else {
        if ($helpTextHtml) {
            echo '<span>' . $helpTextHtml . '</span>';
        } else {
            echo '&nbsp;';
        }
//    }
    echo '</td>';

    if (isset($errorFields[$propName])) {
        echo '<tr class="formFieldWithProblem"><td colspan="3">';
        echo $errorFields[$propName];
        echo '<br><br></td></tr>';
    }

    echo '</tr>' . "\n";

    $i++;
}

?>
