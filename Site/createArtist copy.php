<?php

include_once('../Includes/Config.php');
include_once('../Includes/Init.php');
include_once('../Includes/recaptchalib.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Artist.php');

$artist = null;
$unpersistedArtist = null;
$message = get_param('msg');
$problemOccured = false;
$errorFields = Array();

// FIXME - rename the script - it's also used for updates

$userIsLoggedIn = false;
$artist = Artist::new_from_cookie();
if ($artist) {
    $userIsLoggedIn = true;
    $logger->info('user is logged in');

} else {
    $logger->info('user is NOT logged in');
}

if (get_param('action') == 'save') {
    $logger->info('attempting to save artist account data ...');
    if (inputDataOk($errorFields, $artist, $userIsLoggedIn)) {
        if (!$userIsLoggedIn) {
            $artist = new Artist();
        }

        $oldPasswordMd5 = $artist->password_md5;

        processParams($artist, true);

        // check if a url was entered or if there's still only the predefined value
        if ($artist->webpage_url == 'http://') $artist->webpage_url = '';

        // the newly created account needs to be activated first
        if (!$userIsLoggedIn) {
            $artist->status = 'inactive';
        }

        $artist->save();

        $newPasswordMd5 = $artist->password_md5;

        $message = 'Successfully created artist account!';
        if ($userIsLoggedIn) {
            $message = 'Successfully updated artist account!';

            if ($oldPasswordMd5 != $newPasswordMd5) {
                $artist->doLogin();
                $logger->info('password change was successful, reloading page to set cookie');
                header('Location: ' . $_SERVER['PHP_SELF'] . '?msg=' . urlencode($message));
                exit;
            }

        } else {
            $logger->info('created artist record with id: ' . $artist->id);

            $email_sent = send_email($artist->email_address, 'Please activate your notethrower.com account',
                    'Please click the link below to confirm your notethrower.com account creation:' . "\n\n" .
                    $GLOBALS['BASE_URL'] . 'Site/accountCreationConfirmed.php' .
                    '?x=' . $artist->id . '&c=' . md5('TheSparrowsAreFlyingAgain!' . $artist->id));

            if (!$email_sent) {
                $message = 'Failed to send confirmation email after creation of account!'; // FIXME - test behaviour in this case
                $problemOccured = true;

            } else {
                header('Location: accountCreated.php');
                exit;
            }
        }

    } else {
        $logger->info('input data was invalid');
        $unpersistedArtist = new Artist();
        processParams($unpersistedArtist, false);
        $message = 'Please correct the highlighted problems!';
        $problemOccured = true;
    }
}

function inputDataOk(&$errorFields, &$artist, $userIsLoggedIn) {
    global $logger;

    $result = true;

    $pwd    = get_param('password');
    $pwd2   = get_param('password2');
    $oldPwd = get_param('old_password');

    if ($userIsLoggedIn) { // update operation - user has to specify old password -> update: currently not desired
        if (!$oldPwd) {
//            $errorFields['old_password'] = 'Please enter your current password!';
//            $result = false;

        } else if ($artist->password_md5 !== md5($oldPwd)) {
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

    if (strlen(get_param('name')) < 1) {
        $errorFields['name'] = 'Name is missing!';
        $result = false;
    }

    if (strlen(get_param('username')) < 1) {
        $errorFields['username'] = 'Username is missing!';
        $result = false;
    }

    $checkArtist = Artist::fetch_for_username(get_param('username'));
    if ($checkArtist) {
        if (!$userIsLoggedIn) { // if artist is created from scratch
            $errorFields['username'] = 'Username already in use! Please choose a different one.';
            $result = false;

        } else { // artist data update
            if ($artist->username != get_param('username')) { // display an error only if the name was changed in the update process
                $errorFields['username'] = 'Username already in use! Please choose a different one.';
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
        }
    }

    if (isset($_FILES['image_filename']['name']) && $_FILES['image_filename']['name'] && !preg_match('/jpg$/i', $_FILES['image_filename']['name'])) {
        $errorFields['image_filename'] = 'Image must be in JPG format!';
        $result = false;
    }

    if (!$userIsLoggedIn && get_param('terms_accepted') != 'yes') {
        $errorFields['terms_accepted'] = 'You need to agree to Notethrower\'s Artist Agreement in order to sign up.';
        $result = false;
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

function processParams(&$artist, $uploadAllowed) {
    global $logger;

    $artist->username        = get_param('username');
    $artist->email_address   = get_param('email_address');
    $artist->name            = get_param('name');
    $artist->artist_info     = get_param('artist_info');
    $artist->additional_info = get_param('additional_info');
    $artist->webpage_url     = get_param('webpage_url');
    $artist->paypal_account  = get_param('paypal_account');

    if (get_param('password')) { // this can be empty when an account is updated without a password change. we musst not save an empty password then.
        $artist->password_md5 = md5(get_param('password'));
    }

    // handle artist image upload
    if ($uploadAllowed && isset($_FILES['image_filename']['name']) && $_FILES['image_filename']['name']) {
        $logger->info('processing file upload: ' . $_FILES['image_filename']['name']);

        $artistImgSubdir = null;
        if (ini_get('safe_mode')) {
            $artistImgSubdir = ''; // in safe mode we're not allowed to create directories
        } else {
            $artistImgSubdir = md5('Wuizi' . $artist->id);
        }
        $upload_dir = $GLOBALS['ARTIST_IMAGE_BASE_PATH'] . $artistImgSubdir;

        // upload to tmp file
        $upload_filename = $artist->id . '_' . time() . '.jpg';
        do_upload($upload_dir, 'image_filename', $upload_filename);
        $upload_img_file = $upload_dir . $GLOBALS['PATH_SEPARATOR'] . $upload_filename;
        $final_img_filename = md5('Wuizi' . $artist->id) . '.jpg'; // must be unique (see safe mode logic above)
        $final_thumb_img_filename = md5('Wuizi' . $artist->id) . '_thumb.jpg'; // must be unique (see safe mode logic above)
        $final_img_file = $upload_dir . $GLOBALS['PATH_SEPARATOR'] . $final_img_filename;
        $final_thumb_img_file = $upload_dir . $GLOBALS['PATH_SEPARATOR'] . $final_thumb_img_filename;

        $logger->info('resizing uploaded image');
        umask(0777); // most probably ignored on windows systems
        create_resized_jpg($upload_img_file, $final_img_file, $GLOBALS['ARTIST_IMG_MAX_WIDTH'], $GLOBALS['ARTIST_IMG_MAX_HEIGHT']);
        create_resized_jpg($upload_img_file, $final_thumb_img_file, $GLOBALS['ARTIST_THUMB_MAX_WIDTH'], $GLOBALS['ARTIST_THUMB_MAX_HEIGHT']);
        chmod($final_img_file, 0666);
        chmod($final_thumb_img_file, 0666);

        unlink($upload_img_file);

        $artist->image_filename = ($artistImgSubdir ? $artistImgSubdir . '/' : '') . $final_img_filename;

        $logger->info('artist image filename: ' . $artist->image_filename);
    }
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

    	<div id="pageMainContent">

      
      
            <div class="horizontalMenu">
                <ul>
                    <li><a href="index.php">Startpage</a></li>
                </ul>
            </div> 
      		
      		<div id="artistFormDivStart"></div>
      		<div id="artistFormDiv"><div id="container">

<?php

if ($userIsLoggedIn) {
    echo '<br/><h1>Update artist account:</h1>';
} else {
    echo '<br/><h1>Share Your Frequency! Music Collaboration and Licensing...made easy.</h1><br>' .
         'We are a team of music lovers that believe that musicians deserve to get paid for their work. We created Notethrower to help them find other artists to share in the music making and music selling process.  When artists from different backgrounds collaborate together, the potential for great creative works increases significantly. Plus, it is just fun to hear how another artist transforms or works your guitar riff or vocal track into a completely new song!<br>' .
         '<br>' .
         'There are many remix contests and sites on the web, but they all seem to ask the artist to be happy with giving away their music for free.<br>' .
         'Notethrower recognizes the hard work and talent of it\'s community and wants to reward musicians by providing an innovative platform to collaborate and share in the ownership of new work, and of course, get paid.<br>' .
         '<br>' .
         'Think of us as a Co-Writers community.  We provide the legal framework regarding the copyrights and ownership of work so artists can spend their time creating music instead of dealing with the complicated paperwork of music licensing, especially with another artist.  With Notethrower, artists agree to work together and co-write new pieces of music that are then made available to the global marketplace of music licensing.<br>' .
         'Artists upload their music and easily create their own Notethrower music collaboration and licensing widget that can be embedded everywhere like Facebook, Myspace and countless other sites.  Just click the grab this button and follow the easy instructions.  Once an artists widget is visible online, anyone who sees it can listen to, purchase, and download a music track for commercial licensing.  The artist or bands who created the music get paid, and Notethrower only keeps 10% of the profit. That\'s 90% directly to the artists!<br>' .
         '<br>' .
         'Just fill in the details below.  You can update your information at any time.<br>' .
         '<br>' .
         'A verification email will be sent to you to log in. Once you have successfully done so, you can start your musical journey by uploading your first mp3 and artist info. Now let\'s make some music together!<br>';
    echo '<br>';
    echo '<h1>Create new artist account:</h1>';
}


?>
        <br><br>

        <div id="artistFormPlusImage">

          <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save">
            <table class="artistFormTable">
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

showFormField('Artist/Band name',           'text',     'name',             '', 'Please put your band or artist name in the field.', true,  255, $artist, $unpersistedArtist, $problemOccured, $errorFields);
showFormField('Username',                   'text',     'username',         '', 'This is your username for logging in to Notethrower.<br>If you want to provide a different name than your artist profile, you may do so.', true,  255, $artist, $unpersistedArtist, $problemOccured, $errorFields);
showFormField('Email address',              'text',     'email_address',    '', 'We will send you a verification link to this address to login.  If another user sends you a message, a notification will also be sent here.<br>We will never give out your email or spam you. Aren\'t we nice?', true,  255, $artist, $unpersistedArtist, $problemOccured, $errorFields);
showFormField('Webpage URL',                'text',     'webpage_url',      '', 'If you have another place you would like your fans to find you, please enter the link here. For example, your MySpace or Facebook page.', false, 255, $artist, $unpersistedArtist, $problemOccured, $errorFields, 'http://');
showFormField('Paypal account',             'text',     'paypal_account',   '', 'This is where your earnings will be sent. <a href="http://www.paypal.com" target="_blank">Get a PayPal account!</a><br>You can always add this later, but we need it in order to pay you.  We\'ve made it extremely easy for you to get paid for licensing your work. If someone has remixed your work and made it available in their widget, You get paid 50% of the earnings from the sale of that work. Now imagine if there are hundreds of remixed versions of your track all available for licensing. Many more opportunities to get paid for your initial work.', false, 255, $artist, $unpersistedArtist, $problemOccured, $errorFields);

if ($userIsLoggedIn) { // it's an update
    showFormField('Artist image',           'file',     'image_filename',   '', 'You can add this later if you like. Photos may not contain nudity, violent or offensive material, or copyrighted images. If you violate these terms your account may be deleted ', false, 255, $artist, $unpersistedArtist, $problemOccured, $errorFields);

    echo '<tr><td>Current artist image:</td>' . "\n";
    echo '<td>' . "\n";
    if ($artist->image_filename) {
        echo '<img src="' . $ARTIST_IMAGE_BASE_URL . $artist->image_filename . '" alt="' . htmlentities($artist->name) . '">';
    } else {
        echo 'No artist image uploaded yet.';
    }
    echo '</td><td>&nbsp;</td>' . "\n";
    echo '</tr>' . "\n";

    showFormField('Password',               'password', 'old_password',     '', 'If you want to change your password, enter you old password here.', false,  255, $artist, $unpersistedArtist, $problemOccured, $errorFields);
    showFormField('New Password',           'password', 'password',         '', 'Enter your new password here if you want to change it.', false, 255, $artist, $unpersistedArtist, $problemOccured, $errorFields);
    showFormField('Password verification',  'password', 'password2',        '', 'Enter your new password again, for verification.', false, 255, $artist, $unpersistedArtist, $problemOccured, $errorFields);

} else { // it's an insert
    showFormField('Password',               'password', 'password',         '', 'Create your own password and use this for logging in. If you ever forget it, we can email you a new one or you can change it in the future.', true,  255, $artist, $unpersistedArtist, $problemOccured, $errorFields);
    showFormField('Password verification',  'password', 'password2',        '', 'This should be pretty easy. Do you remember the password you just created? Type it here.', true,  255, $artist, $unpersistedArtist, $problemOccured, $errorFields);
}

showFormField('Artist/Band information',    'textarea', 'artist_info',      '', 'Tell us a bit about yourself or your band. What other bands or music influenced you? Where are you from?', false, 0,   $artist, $unpersistedArtist, $problemOccured, $errorFields);
showFormField('Additional information',     'textarea', 'additional_info',  '', 'Anything else we should know?', false, 0,   $artist, $unpersistedArtist, $problemOccured, $errorFields);

if (!$userIsLoggedIn) {
    showFormField('Artist Agreement', 'checkbox',  'terms_accepted', 'I\'ve read and agree to <a href="javascript:showTermsAndConditions();">Notethrower\'s Artist Agreement</a>.', '', true, 0, $artist, $unpersistedArtist, $problemOccured, $errorFields);

    // captcha
    showFormField('Verification', 'recaptcha', 'captcha', '', 'Show us that you are human. After you create your account, check your email for a verification link to sign in. Once you are logged in, you may then upload your first track in mp3 format, as well as your artist picture and bio. See you soon!', true, 0, $artist, $unpersistedArtist, $problemOccured, $errorFields);
}

?>
              <tr>
                <td colspan="3">&nbsp;</td>
              </tr>
              <tr>
                <td colspan="3" align="right">

                <input class="<?php echo $userIsLoggedIn ? 'updateAccountButton' : 'createAccountButton'; ?>" type="submit" value="">

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
        

      </div></div>
      <div id="artistFormDivEnd"></div>


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

function showFormField($label, $inputType, $propName, $suffix, $helpTextHtml, $mandatory, $maxlength, &$artist, &$unpersistedArtist, $problemOccured, &$errorFields, $preconfiguredValue = null) {
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
        $artistVal            = null;
        $unpersistedArtistVal = null;
        eval('if ($artist) $artistVal = $artist->' . $propName . ';');
        eval('if ($unpersistedArtist) $unpersistedArtistVal = $unpersistedArtist->' . $propName . ';');
        echo !$problemOccured ? htmlentities($artistVal) : ($unpersistedArtist ? htmlentities($unpersistedArtistVal) : '');
        echo '</textarea>' . "\n";

    } else if ($inputType == 'recaptcha') {
        $publickey = '6LcNIgoAAAAAAP0BgB5wNty92PiCewdRq7y5L6qw';
        echo recaptcha_get_html($publickey);

    } else {
        echo '<input class="' . (isset($errorFields[$propName]) ? $problemClass : $normalClass) . '" type="' . $inputType . '" name="' . $propName . '" maxlength="' . $maxlength . '" size="' . $size . '"';

        if ($inputType != 'password' && $inputType != 'file' && $inputType != 'checkbox') {
            echo ' value="';
            $artistVal            = null;
            $unpersistedArtistVal = null;
            eval('if ($artist) $artistVal = $artist->' . $propName . ';');
            eval('if ($unpersistedArtist) $unpersistedArtistVal = $unpersistedArtist->' . $propName . ';');
            echo !$problemOccured ? ($preconfiguredValue && !$artistVal ? htmlentities($preconfiguredValue) : htmlentities($artistVal)) : ($unpersistedArtist ? htmlentities($unpersistedArtistVal) : '');
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
            echo '<span style="font-size:11px">' . $helpTextHtml . '</span>';
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
