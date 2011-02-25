<?php

include_once('../Includes/Config.php');
include_once('../Includes/Init.php');
include_once('../Includes/recaptchalib.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');

$user = null;
$unpersistedUser = null;
$problemOccured = false;
$errorMsg = '';

// FIXME - rename the script - it's also used for updates

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
            $errorMsg = 'No user not found for username: ' . $username;
        }
    } else if ($email != '') {
        $user = User::fetch_for_email_address($email); 
        if (!$user) {
            $problemOccured = true;
            $errorMsg = 'No user not found for email address: ' . $email;
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
      		
      		<div id="userFormDivStart"></div>
      		<div id="userFormDiv"><div id="container">

<?php

echo '<br/><h1>Reset your password</h1>';
if ($passwordSent) {
    echo 'A new password was automatically generated for you and instructions have been sent to your email address.<br/>';
    echo 'Once logged in, you can change your password under your "edit profile" tab.';
} else {
    echo 'Forgot your password? Notethrower will send you password reset instructions to the email address you used to sign up.';
    echo '<br/>';
    echo 'Please type your Notethrower username or email account below';
        

?>
        <br><br>

        <div id="userFormPlusImage">

          <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="generatePwd">
            <table class="userFormTable">
              <tr>
                <td colspan="2">
<?php

if ($problemOccured) {
    echo '<span class="problemMessage">' . $errorMsg . '</span><br><br>';
}

?>
                </td>
              </tr>
<?php

    echo '<tr class="standardRow1"' .
         ' onmouseover="this.className=\'highlightedRow\';" onmouseout="this.className=\'standardRow1\';"' .
         '>' . "\n";
    echo '<td class="formLeftCol" style="vertical-align:top"><b>Username:</b></td>' . "\n";
    echo '<td class="formMiddleCol mandatoryFormField" style="vertical-align:top">';
    echo '<input class="inputTextField" type="text" name="username" maxlength="255" size="30">' . "\n";
    echo '</td>' . "\n";
    echo '</tr>' . "\n";
    echo '<tr><td colspan="2">or</td></tr>';
    echo '<tr class="standardRow1"' .
         ' onmouseover="this.className=\'highlightedRow\';" onmouseout="this.className=\'standardRow1\';"' .
         '>' . "\n";
    echo '<td class="formLeftCol" style="vertical-align:top"><b>Email address:</b></td>' . "\n";
    echo '<td class="formMiddleCol" style="vertical-align:top">';
    echo '<input class="inputTextField" type="text" name="email" maxlength="255" size="30">' . "\n";
    echo '</td>' . "\n";
    echo '</tr>' . "\n";
    

?>
              <tr>
                <td colspan="2">&nbsp;</td>
              </tr>
              <tr>
                <td colspan="2" align="right">

                <input class="updateAccountButton" type="submit" value="">

                </td>
              </tr>
              <tr>
                <td colspan="2">
                </td>
              </tr>
            </table>
          </form>

        </div>
<?php
} // end of the "if ($passwordSent)..."
?>
        <br>
        <br>      
        

      </div></div>
      <div id="userFormDivEnd"></div>


    </div>

    <? include ("footer.php"); ?>

	</div> <!-- bodyWrapper -->

	<?php writeGoogleAnalyticsStuff(); ?>
  </body>
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
