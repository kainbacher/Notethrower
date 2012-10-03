<?php

include_once('../Includes/Init.php'); // must be included first

include_once($INCLUDE_PATH . 'Config.php');
include_once($INCLUDE_PATH . 'FormUtil.php');
//include_once('../Includes/recaptchalib.php');
include_once($INCLUDE_PATH . 'Snippets.php');
include_once($INCLUDE_PATH . 'TemplateUtil.php');
include_once($INCLUDE_PATH . 'DB/Attribute.php');
include_once($INCLUDE_PATH . 'DB/Genre.php');
include_once($INCLUDE_PATH . 'DB/Invitation.php');
include_once($INCLUDE_PATH . 'DB/Message.php');
include_once($INCLUDE_PATH . 'DB/Project.php');
include_once($INCLUDE_PATH . 'DB/ProjectUserVisibility.php');
include_once($INCLUDE_PATH . 'DB/Tool.php');
include_once($INCLUDE_PATH . 'DB/User.php');
include_once($INCLUDE_PATH . 'DB/UserAttribute.php');
include_once($INCLUDE_PATH . 'DB/UserGenre.php');
include_once($INCLUDE_PATH . 'DB/UserTool.php');
include_once($INCLUDE_PATH . 'Mailer/MailUtil.php');

$MAX_UPLOAD_FILESIZE = 3145728; // 3MB

//$logger->set_debug_level();

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

        processParams($user, $userIsLoggedIn);

        // check if a url was entered or if there's still only the predefined value
        if ($user->webpage_url  == 'http://') $user->webpage_url  = '';
        if ($user->facebook_url == 'http://') $user->facebook_url = '';

        // sanitize twitter username (according to joe/eric this must not start with a @ char)
        if (strpos($user->twitter_username, '@') === 0) $user->twitter_username = substr($user->twitter_username, 1);

        // the newly created account needs to be activated first
        if (!$userIsLoggedIn) {
            $user->status = 'inactive';
        }

        $user->save();

        // handle user image upload or facebook image download
        handleUserImageUploadOrFacebookImageDownload($user);
        $user->save();

        $newPasswordMd5 = $user->password_md5;

        $message = '';
        if ($userIsLoggedIn) {
            $message = 'Successfully updated user account!';

            if ($oldPasswordMd5 != $newPasswordMd5) {
                $user->doLogin();
                $logger->info('password change was successful, reloading page to set cookie');
                header('Location: ' . basename($_SERVER['PHP_SELF'], '.php') . '?msg=' . urlencode($message));
                exit;
            }

        } else {
            $message = 'Successfully created user account!';
            
            $logger->info('created user record with id: ' . $user->id);

            if (get_numeric_param('invitedToProject')) {
                processPendingInvitations($user);
            }

            createWelcomeMessage($user->id);

            $logger->info('sending account creation confirmation email');

            $email_sent = sendEmail(
                    $user->email_address, 
                    'Please activate your oneloudr.com account',
                    'Please click the link below to activate your oneloudr.com account:' . "\n" .
                    ($user->username && $user->username != $user->email_address ? 'Username: ' . $user->username . "\n" : '') .
                    'Email: ' . $user->email_address . "\n\n" .
                    $GLOBALS['BASE_URL'] . 'accountCreationConfirmed' .
                    '?x=' . $user->id . '&c=' . md5('TheSparrowsAreFlyingAgain!' . $user->id) . "\n\n"
            );

            if (!$email_sent) {
                $message = 'Failed to send confirmation email after creation of account!'; // FIXME - test behaviour in this case
                $problemOccured = true;

            } else {
                header('Location: ' . $GLOBALS['BASE_URL'] . 'accountCreated?email=' . urlencode($user->email_address));
                exit;
            }
        }

    } else {
        $logger->info('input data was invalid: ' . join(', ', $errorFields));
        $unpersistedUser = new User();
        processParams($unpersistedUser, $userIsLoggedIn);
        $message = 'Please correct the highlighted problems!';
        $problemOccured = true;
    }
}

// prefill form with some values if present (eg. email_address, facebook_url, webpage_url)
if (!$user) {
    $user = new User();
    $user->webpageUrl  = 'http://';
    $user->facebookUrl = 'http://';
    $user->wants_newsletter = true;
    processParams($user, $userIsLoggedIn);
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
    'propName'               => 'email_address',
    'label'                  => 'Email address',
    'mandatory'              => $userIsLoggedIn ? false : true,
    'maxlength'              => 255,
    'obj'                    => $user,
    'unpersistedObj'         => $unpersistedUser,
    'errorFields'            => $errorFields,
    'workWithUnpersistedObj' => $problemOccured,
    'infoHtml'               => 'We will send you a verification link to this address to login. If another user sends you a message, a notification will also be sent here.<br>We will never give out your email or spam you. Aren\'t we nice?',
    'readonly'               => $userIsLoggedIn ? true : false
));

$chooseLocationLink = '';

if ($userIsLoggedIn) { // it's an update
    if ($pageMode == 'artist') {
        $chooseLocationLink = processTpl('Account/chooseLocationLink.html', array(
        ));

        // skills
        $selectOptions = array();
        $aList = Attribute::fetchShownFor('contains');
        foreach ($aList as $a) {
            $selectOptions[$a->id] = escape($a->name);
        }

        $formElementsSection1 .= getFormFieldForParams(array(
            'inputType'              => 'multiselect2',
            'propName'               => 'attributes',
            'label'                  => 'Skills',
            'mandatory'              => false,
            'cssClassSuffix'         => 'chzn-select chzn-modify', // this triggers a conversion to a "chosen" select field
            'obj'                    => $user,
            'unpersistedObj'         => $unpersistedUser,
            'selectOptions'          => $selectOptions,
            'objValues'              => $problemOccured ? $unpersistedUser->unpersistedUserAttributes : UserAttribute::getAttributeIdsForUserIdAndState($user->id, 'offers'),
            'errorFields'            => $errorFields,
            'workWithUnpersistedObj' => $problemOccured,
            'infoText'               => 'List your skills here.'
        ));

        // "create new skill"
        /* handled with chosen.js
        $formElementsSection1 .= getFormFieldForParams(array(
            'propName'               => 'newAttribute',
            'label'                  => 'Add new skill',
            'mandatory'              => false,
            'maxlength'              => 50,
            'obj'                    => $user,
            'unpersistedObj'         => $unpersistedUser,
            'errorFields'            => $errorFields,
            'workWithUnpersistedObj' => $problemOccured,
            'infoHtml'               => 'If you can\'t find your skill in the selection above, you can add it here. It will be added to the skills list, when you click <b>Save</b>.'
        ));
        */

        // tools
        $formElementsSection1 .= getFormFieldForParams(array(
            'inputType'              => 'multiselect2',
            'propName'               => 'tools',
            'label'                  => 'Tools',
            'mandatory'              => false,
            'cssClassSuffix'         => 'chzn-select chzn-modify', // this triggers a conversion to a "chosen" select field
            'obj'                    => $user,
            'unpersistedObj'         => $unpersistedUser,
            'selectOptions'          => Tool::getSelectorOptionsArray(),
            'objValues'              => $problemOccured ? $unpersistedUser->unpersistedUserTools : UserTool::getToolIdsForUserId($user->id),
            'errorFields'            => $errorFields,
            'workWithUnpersistedObj' => $problemOccured,
            'infoText'               => 'List the tools you use here.'
        ));

        // user genres
        $formElementsSection1 .= getFormFieldForParams(array(
            'inputType'              => 'multiselect2',
            'propName'               => 'genres',
            'label'                  => 'Genres',
            'mandatory'              => false,
            'cssClassSuffix'         => 'chzn-select chzn-modify', // this triggers a conversion to a "chosen" select field
            'obj'                    => $user,
            'unpersistedObj'         => $unpersistedUser,
            'selectOptions'          => Genre::getSelectorOptionsArray(),
            'objValues'              => $problemOccured ? $unpersistedUser->unpersistedUserGenres : UserGenre::getGenreIdsForUserId($user->id),
            'errorFields'            => $errorFields,
            'workWithUnpersistedObj' => $problemOccured,
            'infoText'               => 'Please choose the genres that best describe what type of musician you are. We will provide recommendations on which projects to work on based on your selections.'
        ));

        $formElementsSection1 .= getFormFieldForParams(array(
            'propName'               => 'webpage_url',
            'label'                  => 'Webpage URL',
            'mandatory'              => false,
            'maxlength'              => 255,
            'obj'                    => $user,
            'unpersistedObj'         => $unpersistedUser,
            'errorFields'            => $errorFields,
            'workWithUnpersistedObj' => $problemOccured,
            'infoText'               => 'If you have another place you would like your fans to find you, please enter the link here.'
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
            'infoHtml'               => 'This is where your earnings will be sent. <a href="http://www.paypal.com" target="_blank">Get a PayPal account!</a><br>You can always add this later, but we need it in order to pay you.  We\'ve made it extremely easy for you to get paid for licensing your work. If someone has remixed your work and made it available in their widget, You get paid 50% of the earnings from the sale of that work. Now imagine if there are hundreds of remixed versions of your track all available for licensing. Many more opportunities to get paid for your initial work.'
        ));
    }

} else { // it's an insert
    if ($user->webpage_url) {
        $formElementsSection1 .= getFormFieldForParams(array(
            'propName'               => 'webpage_url',
            'label'                  => 'Webpage URL',
            'mandatory'              => false,
            'maxlength'              => 255,
            'obj'                    => $user,
            'unpersistedObj'         => $unpersistedUser,
            'errorFields'            => $errorFields,
            'workWithUnpersistedObj' => $problemOccured,
            'infoText'               => 'If you have another place you would like your fans to find you, please enter the link here.'
        ));
    }
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
        'inputFieldSuffix'       => 'Max. 3 MB',
        'infoText'               => 'You can add this later if you like. Photos may not contain nudity, violent or offensive material, or copyrighted images. If you violate these terms your account may be deleted. The max. file size is 3 MB.',
        'maxFileSizeForUpload'   => $GLOBALS['MAX_UPLOAD_FILESIZE']
    ));

    if ($user->image_filename) {
        $userImage = processTpl('Account/userImage_found.html', array(
            '${imgSrc}'  => $USER_IMAGE_BASE_URL . $user->image_filename . '?nocache=' . @filemtime($USER_IMAGE_BASE_PATH . $user->image_filename),
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
        'label'                  => 'Confirm password',
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
        'label'                  => 'Confirm password',
        'mandatory'              => true,
        'maxlength'              => 255,
        'obj'                    => $user,
        'unpersistedObj'         => $unpersistedUser,
        'errorFields'            => $errorFields,
        'workWithUnpersistedObj' => $problemOccured,
        'infoText'               => 'This should be pretty easy. Do you remember the password you just created? Type it here.'
    ));
}

if ($userIsLoggedIn) { // it's an update
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
            'propName'               => 'facebook_url',
            'label'                  => 'Facebook URL',
            'mandatory'              => false,
            'maxlength'              => 255,
            'obj'                    => $user,
            'unpersistedObj'         => $unpersistedUser,
            'errorFields'            => $errorFields,
            'workWithUnpersistedObj' => $problemOccured,
            'infoText'               => 'Enter your facebook link here if you have one.'
        ));

        $formElementsSection2 .= getFormFieldForParams(array(
            'propName'               => 'twitter_username',
            'label'                  => 'Twitter username',
            'mandatory'              => false,
            'maxlength'              => 255,
            'obj'                    => $user,
            'unpersistedObj'         => $unpersistedUser,
            'errorFields'            => $errorFields,
            'workWithUnpersistedObj' => $problemOccured,
            'infoText'               => 'Enter your twitter username here if you have one.'
        ));

// currently not used
//        $formElementsSection2 .= getFormFieldForParams(array(
//            'inputType'              => 'textarea',
//            'propName'               => 'additional_info',
//            'label'                  => 'Additional information',
//            'mandatory'              => false,
//            'obj'                    => $user,
//            'unpersistedObj'         => $unpersistedUser,
//            'errorFields'            => $errorFields,
//            'workWithUnpersistedObj' => $problemOccured,
//            'infoText'               => 'Anything else we should know?'
//        ));

        $formElementsSection2 .= getFormFieldForParams(array(
            'inputType'              => 'text',
            'propName'               => 'video_url',
            'label'                  => 'Youtube Video',
            'mandatory'              => false,
            'obj'                    => $user,
            'unpersistedObj'         => $unpersistedUser,
            'errorFields'            => $errorFields,
            'workWithUnpersistedObj' => $problemOccured,
            'infoText'               => 'If you have a Youtube video, put the URL here.'
        ));

        $formElementsSection2 .= getFormFieldForParams(array(
            'inputType'              => 'textarea',
            'propName'               => 'influences',
            'label'                  => 'Influences',
            'mandatory'              => false,
            'obj'                    => $user,
            'unpersistedObj'         => $unpersistedUser,
            'errorFields'            => $errorFields,
            'workWithUnpersistedObj' => $problemOccured,
            'infoText'               => 'List the artists here which influenced you.'
        ));
    }

} else { // it's an insert
    if ($user->facebook_url) {
        $formElementsSection2 .= getFormFieldForParams(array(
            'propName'               => 'facebook_url',
            'label'                  => 'Facebook URL',
            'mandatory'              => false,
            'maxlength'              => 255,
            'obj'                    => $user,
            'unpersistedObj'         => $unpersistedUser,
            'errorFields'            => $errorFields,
            'workWithUnpersistedObj' => $problemOccured,
            'infoText'               => 'Enter your facebook link here if you have one.'
        ));
    }
}

if (!$userIsLoggedIn) {
    if ($pageMode == 'artist') {
        $formElementsSection2 .= getFormFieldForParams(array(
            'inputType'                 => 'checkbox',
            'propName'                  => 'terms_accepted',
            'label'                     => 'Artist Agreement',
            'inputFieldGroupSuffixHtml' => 'I\'ve read and agree to<br><a href="javascript:showArtistAgreement();">oneloudr\'s Artist Agreement</a>',
            'mandatory'                 => true,
            'obj'                       => $user,
            'unpersistedObj'            => $unpersistedUser,
            'errorFields'               => $errorFields,
            'workWithUnpersistedObj'    => $problemOccured,
            'objValueOverride'          => get_param('terms_accepted'), // since this field is not stored in the user obj, we need an override
            'infoText'                  => 'Please confirm that you\'ve read and agree to the oneloudr Artist Agreement.'
        ));
        
        $formElementsSection2 .= getFormFieldForParams(array(
            'inputType'                 => 'checkbox',
            'propName'                  => 'wants_newsletter',
            'label'                     => 'Newsletter',
            'inputFieldGroupSuffixHtml' => 'I want to get oneloudr.com updates via email.',
            'mandatory'                 => true,
            'obj'                       => $user,
            'unpersistedObj'            => $unpersistedUser,
            'errorFields'               => $errorFields,
            'workWithUnpersistedObj'    => $problemOccured
        ));
    }

//    $formElementsSection2 .= getFormFieldForParams(array(
//        'inputType'              => 'recaptcha',
//        'propName'               => 'captcha',
//        'label'                  => 'Verification',
//        'recaptchaPublicKey'     => $GLOBALS['RECAPTCHA_PUBLIC_KEY'],
//        'mandatory'              => true,
//        'obj'                    => $user,
//        'unpersistedObj'         => $unpersistedUser,
//        'errorFields'            => $errorFields,
//        'workWithUnpersistedObj' => $problemOccured,
//        'infoText'               => 'Show us that you are human. After you create your account, check your email for a verification link to sign in.'
//    ));
}

$latitude  = $problemOccured ? $unpersistedUser->latitude  : $user->latitude;
$longitude = $problemOccured ? $unpersistedUser->longitude : $user->longitude;

$sidebarHtml = '';
if ($userIsLoggedIn) {
    // user image
    $userImgUrl = getUserImageUri($user->image_filename, 'regular');

    // webpage url
    $webpageLink = '';
    if ($user->webpage_url) {
        $webpageUrl = $user->webpage_url;
        if (substr($user->webpage_url, 0, 7) != 'http://' && substr($user->webpage_url, 0, 8) != 'https://') {
            $webpageUrl = 'http://' . $user->webpage_url;
        }

        $webpageLink = processTpl('Common/externalWebLink.html', array(
            '${href}'  => escape($webpageUrl),
            '${label}' => escape($user->webpage_url)
        )) . '<br />'; // we don't put the newlines into the template because we probably need the link without them on a different page.
    }

    // facebook url
    $facebookLink = '';
    if ($user->facebook_url) {
        $facebookUrl = $user->facebook_url;
        if (substr($user->facebook_url, 0, 7) != 'http://' && substr($user->facebook_url, 0, 8) != 'https://') {
            $facebookUrl = 'http://' . $user->facebook_url;
        }

        $facebookLink = processTpl('Common/externalFacebookLink.html', array(
            '${href}'  => escape($facebookUrl)
        )) . '<br />'; // we don't put the newlines into the template because we probably need the link without them on a different page.
    }

    // twitter url
    $twitterLink = '';
    if ($user->twitter_username) {
        $twitterUrl = 'http://twitter.com/' . $user->twitter_username;
        $twitterLink = processTpl('Common/externalTwitterLink.html', array(
            '${href}'  => escape($twitterUrl)
        )) . '<br />'; // we don't put the newlines into the template because we probably need the link without them on a different page.
    }

    // artist info
    $artistInfo = '';
    if ($user->artist_info) {
        $artistInfo = processTpl('Account/artistInfo.html', array(
            '${artistInfo}' => nl2br(escape($user->artist_info))
        ));
    }

    // influences
    $influences = '';
    if ($user->influences) {
        $influences = processTpl('Account/influences.html', array(
            '${influences}' => nl2br(escape($user->influences))
        ));
    }

    // video
    $video = '';
    if ($user->video_url) {
        preg_match("#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $user->video_url, $video_match);
        $video = processTpl('Common/video.html', array(
            '${videoId}' => escape($video_match[0])
        ), $showMobileVersion);
    }
    
    // location
    $locationMap = '';
    if ($user->latitude && $user->longitude) {
        $locationMap = processTpl('Common/locationMap.html', array(
            '${latitude}'  => $user->latitude,
            '${longitude}' => $user->longitude,
        ), $showMobileVersion);
    }

    // currently hidden
    //// additional info
    //$additionalInfo = '';
    //if ($user->additional_info) {
    //    $additionalInfo = processTpl('Artist/additionalInfo.html', array(
    //        '${additionalInfo}' => escape($user->additional_info)
    //    ));
    //}

    $skills = implode(', ', UserAttribute::getAttributeNamesForUserIdAndState($user->id, 'offers'));
    $skillsElement = '';
    if ($skills) {
        $skillsElement = processTpl('Common/sidebarSkillsElement.html', array(
            '${skills}' => $skills
        ));
    }

    $genres = implode(', ', UserGenre::getGenreNamesForUserId($user->id));
    $genresElement = '';
    if ($genres) {
        $genresElement = processTpl('Common/sidebarGenresElement.html', array(
            '${genres}' => $genres
        ));
    }
    
    $tools  = implode(', ', UserTool::getToolNamesForUserId($user->id));
    $toolsElement = '';
    if ($tools) {
        $toolsElement = processTpl('Common/sidebarToolsElement.html', array(
            '${tools}' => $tools
        ));
    }

    $sidebarHtml = processTpl('Account/sidebar.html', array(
        '${userName}'                             => escape($user->name),
        '${userImgUrl}'                           => $userImgUrl,
        '${Common/externalWebLink_list}'          => $webpageLink . $facebookLink . $twitterLink,
        '${Account/artistInfo_optional}'          => $artistInfo,
        '${Account/influences_optional}'          => $influences,
        //'${Account/additionalInfo_optional}'      => $additionalInfo, // currently hidden
        '${Common/video_optional}'                => $video,
        '${Common/locationMap_optional}'          => $locationMap,
        '${Common/sidebarSkillsElement_optional}' => $skillsElement,
        '${Common/sidebarGenresElement_optional}' => $genresElement,
        '${Common/sidebarToolsElement_optional}'  => $toolsElement
    ));
}

processAndPrintTpl('Account/index.html', array(
    '${Common/pageHeader}'                    => buildPageHeader('Account', false, false, true, true),
    '${Common/bodyHeader}'                    => buildBodyHeader($userIsLoggedIn ? $user : null),
    '${headline}'                             => $headline,
    '${Common/message_choice_list}'           => $messageList,
    '${formAction}'                           => '',
    '${invitedToProject}'                     => get_numeric_param('invitedToProject'),
    '${signupAs}'                             => get_param('signupAs'),
    '${facebookId}'                           => get_param('facebook_id'),
    '${Common/formElement_section1_list}'     => $formElementsSection1,
    '${Account/chooseLocationLink_optional}'  => $chooseLocationLink,
    '${latitude}'                             => $latitude,
    '${longitude}'                            => $longitude,
    '${userImage_choice}'                     => $userImage,
    '${Common/formElement_section2_list}'     => $formElementsSection2,
    '${submitButtonClass}'                    => $userIsLoggedIn ? 'updateAccountButton' : 'createAccountButton',
    //'${submitButtonValue}'                    => $userIsLoggedIn ? 'update Account' : 'create Account',
    '${Account/sidebar_optional}'             => $sidebarHtml,
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
//        if (!$pwd) {
//            // noop
//
//        } else { // pwd is present
//            if (!$oldPwd) {
//                $errorFields['old_password'] = 'Please enter your current password!';
//                $result = false;
//            }
//        }

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
//        if (!$pwd) {
//            $errorFields['password']  = 'Password is missing!';
//            $result = false;
//        }
    }

    // check artist name and username
    $usernameParam = get_param('username');
    if (!$usernameParam) $usernameParam = get_param('email_address');

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
        // if the user signs up as a fan only, the username is used as the artist name, too.
        if (strlen($usernameParam) > 0) {
            $checkUser = User::fetch_for_name($usernameParam);
            if ($checkUser) {
                if (!$userIsLoggedIn) { // if user is created from scratch
                    $errorFields['username'] = 'Name already in use! Please choose a different one.';
                    $result = false;

                } else { // user data update
                    if ($user->name != $usernameParam) { // display an error only if the name was changed in the update process
                        $errorFields['username'] = 'Name already in use! Please choose a different one.';
                        $result = false;
                    }
                }
            }
        }
    }

    if (strlen($usernameParam) < 1) {
        $errorFields['username'] = 'Username is missing!';
        $result = false;
    }

    $checkUser = User::fetch_for_username($usernameParam);
    if ($checkUser) {
        if (!$userIsLoggedIn) { // if user is created from scratch
            $errorFields['username'] = 'Name already in use! Please choose a different one.';
            $result = false;

        } else { // user data update
            if ($user->username != $usernameParam) { // display an error only if the name was changed in the update process
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

    //if (isset($_FILES['image_filename']['name']) && $_FILES['image_filename']['name'] && !preg_match('/png$/i', $_FILES['image_filename']['name'])) {
    $allowed_types = array(
        '.jpg',
        '.jpeg',
        '.gif',
        '.png'
    );
    preg_match('/\.[^\.]+$/i',$_FILES['image_filename']['name'],$image_ext);

    if (isset($_FILES['image_filename']['name']) && $_FILES['image_filename']['name'] && !in_array($image_ext[0], $allowed_types)) {
        $errorFields['image_filename'] = 'Image must be in JPG, GIF or PNG format!';
        $result = false;
    }

    // check filesize
    if ($_FILES['image_filename']['error'] == 2 || filesize($_FILES['image_filename']['tmp_name']) > $GLOBALS['MAX_UPLOAD_FILESIZE']) {
        $errorFields['image_filename'] = 'Max. image file size exceeded!';
        $result = false;
    }

    if ($pageMode == 'artist') {
        if (!$userIsLoggedIn && !get_param('terms_accepted')) {
            $errorFields['terms_accepted'] = 'You need to agree to oneloudr\'s Artist Agreement in order to sign up.';
            $result = false;
        }

        if ($userIsLoggedIn) {
            if (strpos(get_param('userAttributesList'), 'new_') === false) { // if no new value was added
                if (preg_match('/[^0-9,]/', get_param('userAttributesList'))) {
                    $errorFields['attributes'] = 'Invalid attributes list'; // can only happen when someone plays around with the post data
                    $result = false;
                }
            }

            if (strpos(get_param('userToolsList'), 'new_') === false) { // if no new value was added
                if (preg_match('/[^0-9,]/', get_param('userToolsList'))) {
                    $errorFields['tools'] = 'Invalid tools list'; // can only happen when someone plays around with the post data
                    $result = false;
                }
            }

            if (strpos(get_param('userGenresList'), 'new_') === false) { // if no new value was added
                if (preg_match('/[^0-9,]/', get_param('userGenresList'))) {
                    $errorFields['genres'] = 'Invalid genres list'; // can only happen when someone plays around with the post data
                    $result = false;
                }
            }
        }
    }

//    // check captcha input
//    if (!$userIsLoggedIn && ($GLOBALS['STAGING_ENV'] == 'test' || $GLOBALS['STAGING_ENV'] == 'live')) {
//        $privatekey = '6LcNIgoAAAAAACwnTjRcKFmzPy8G02o_n5AT_PX_';
//        $resp = recaptcha_check_answer($privatekey,
//                                       $_SERVER["REMOTE_ADDR"],
//                                       $_POST["recaptcha_challenge_field"],
//                                       $_POST["recaptcha_response_field"]);
//
//        if (!$resp->is_valid) {
//            $errorFields['captcha'] = 'The reCAPTCHA wasn\'t entered correctly.';
//            $logger->warn('captcha check failed: ' . $resp->error);
//            $result = false;
//        }
//    }

    return $result;
}

function processParams(&$user, $userIsLoggedIn) {
    global $logger;

    $pageMode = getPageMode($userIsLoggedIn, $user);

    $user->email_address   = get_param('email_address');
    $user->username        = get_param('username');

    if (!$user->username) $user->username = $user->email_address;

    if ($pageMode == 'artist') {
        $user->is_artist        = true;
        $user->webpage_url      = get_param('webpage_url');
        $user->facebook_id      = get_param('facebook_id'); // not a user obj property, just used temporarily
        $user->facebook_url     = get_param('facebook_url');
        $user->twitter_username = get_param('twitter_username');
        $user->name             = get_param('name');
        $user->artist_info      = get_param('artist_info');
        $user->additional_info  = get_param('additional_info');
        //$user->video_url        = (substr(get_param('video_url'),0,4)=='http' ? get_param('video_url') : 'http://'.get_param('video_url'));
        $user->video_url        = get_param('video_url');
        $user->influences       = get_param('influences');
        $user->paypal_account   = get_param('paypal_account');
        if (isParamSet('wants_newsletter')) $user->wants_newsletter = get_numeric_param('wants_newsletter');

        if ($userIsLoggedIn) {
            // create attributes list and save new skills if entered
            $attributes = explode(',', get_param('userAttributesList'));
            $newAttributeList = array();
            $userAttributesList = array();
            foreach ($attributes as $attribute) {
                $newCheck = strstr($attribute, 'new_');
                if ($newCheck){
                    $newAttribute = new Attribute();
                    $newAttribute->name = substr($newCheck, 4, strlen($newCheck));
                    $newAttribute->shown_for = 'both';
                    $newAttribute->insert();
                    $newAttributeList[] = $newAttribute->id;

                } else {
                    $userAttributesList[] = $attribute;
                }
            }

            // create tools list and save new tools if entered
            $tools = explode(',', get_param('userToolsList'));
            $newToolList = array();
            $userToolsList = array();
            foreach ($tools as $tool) {
                $newCheck = strstr($tool, 'new_');
                if ($newCheck) {
                    $newTool = new Tool();
                    $newTool->name = substr($newCheck, 4, strlen($newCheck));
                    $newTool->insert();
                    $newToolList[] = $newTool->id;

                } else {
                    $userToolsList[] = $tool;
                }
            }

            // create genre list and save new genres if entered
            $genres = explode(',', get_param('userGenresList'));
            $newGenreList = array();
            $userGenresList = array();
            foreach ($genres as $genre) {
                $newCheck = strstr($genre, 'new_');
                if ($newCheck) {
                    $newGenre = new Genre();
                    $newGenre->name = substr($newCheck, 4, strlen($newCheck));
                    $newGenre->insert();
                    $newGenreList[] = $newGenre->id;

                } else {
                    $userGenresList[] = $genre;
                }
            }

            // handle user attributes, tools & genres
            $userAttributesList = array_merge($userAttributesList, $newAttributeList);
            $userAttributesList = array_unique($userAttributesList);

            $userToolsList = array_merge($userToolsList, $newToolList);
            $userToolsList = array_unique($userToolsList);

            $userGenresList = array_merge($userGenresList, $newGenreList);
            $userGenresList = array_unique($userGenresList);

            if ($user->id) {
                UserAttribute::deleteForUserId($user->id); // first, delete all existing
                UserAttribute::addAll($userAttributesList, $user->id, 'offers'); // then save the selected attributes (including the new one, if one was entered)

                UserTool::deleteForUserId($user->id); // first, delete all existing
                UserTool::addAll($userToolsList, $user->id); // then save the selected tools (including the new one, if one was entered)

                UserGenre::deleteForUserId($user->id); // first, delete all existing genres
                UserGenre::addAll($userGenresList, $user->id); // then save the selected genres (including the new one, if one was entered)

            } else { // work with unpersisted obj
                $user->unpersistedUserAttributes = $userAttributesList;
                $user->unpersistedUserTools      = $userToolsList;
                $user->unpersistedUserGenres     = $userGenresList;
            }

            // save location
            $user->latitude  = get_numeric_param('latitude');
            $user->longitude = get_numeric_param('longitude');
        }

    } else {
        $user->is_artist = false;
        $user->name      = get_param('username'); // use the username as (artist) name as long as the user is just a fan

        if (!$user->name) $user->name = $user->email_address;
    }

    if (get_param('password')) { // this can be empty when an account is updated without a password change. we musst not save an empty password then.
        $user->password_md5 = md5(get_param('password'));
    }
}

function handleUserImageUploadOrFacebookImageDownload(&$user) {
    global $logger;

    $userImgSubdir = null;
    if (ini_get('safe_mode')) {
        $userImgSubdir = ''; // in safe mode we're not allowed to create directories
    } else {
        $userImgSubdir = md5('Wuizi' . $user->id);
    }
    $upload_dir = $GLOBALS['USER_IMAGE_BASE_PATH'] . $userImgSubdir;

    $upload_filename = $user->id . '_' . time() . '.jpg';

    // make sure the directory exists
    create_directory($upload_dir . $GLOBALS['PATH_SEPARATOR']);

    $upload_img_file = $upload_dir . $GLOBALS['PATH_SEPARATOR'] . $upload_filename;
    $final_img_filename = md5('Wuizi' . $user->id) . '.jpg'; // must be unique (see safe mode logic above)
    $final_thumb_img_filename = md5('Wuizi' . $user->id) . '_thumb.jpg'; // must be unique (see safe mode logic above)
    $final_img_file = $upload_dir . $GLOBALS['PATH_SEPARATOR'] . $final_img_filename;
    $final_thumb_img_file = $upload_dir . $GLOBALS['PATH_SEPARATOR'] . $final_thumb_img_filename;

    $logger->info('fb id: ' . get_param('facebook_id'));
    $logger->info('user img filename: ' . $user->image_filename);

    if (isset($_FILES['image_filename']['name']) && $_FILES['image_filename']['name']) { // regular upload
        $logger->info('processing file upload: ' . $_FILES['image_filename']['name']);

        // upload to tmp file
        do_upload($upload_dir, 'image_filename', $upload_filename);

        $logger->info('resizing uploaded image');
        umask(0777); // most probably ignored on windows systems

        create_resized_jpg($upload_img_file, $final_img_file, $GLOBALS['USER_IMG_MAX_WIDTH'], $GLOBALS['USER_IMG_MAX_HEIGHT'], $_FILES['image_filename']['type']);
        create_resized_jpg($upload_img_file, $final_thumb_img_file, $GLOBALS['USER_THUMB_MAX_WIDTH'], $GLOBALS['USER_THUMB_MAX_HEIGHT'], $_FILES['image_filename']['type']);
        chmod($final_img_file, 0666);
        chmod($final_thumb_img_file, 0666);

        unlink($upload_img_file);

        $user->image_filename = ($userImgSubdir ? $userImgSubdir . '/' : '') . $final_img_filename;

        $logger->info('user image filename: ' . $user->image_filename);

    } else if (get_param('facebook_id') && !$user->image_filename) { // new signup via facebook - we fetch the facebook profile image
        $fbImgUrl = 'http://graph.facebook.com/' . get_param('facebook_id') . '/picture?type=large';
        $logger->info('getting user profile picture from facebook: ' . $fbImgUrl);
        $data = file_get_contents($fbImgUrl);
        if ($data) {
            $logger->info('saving downloaded facebook user profile image to file: ' . $upload_img_file);
            $ok = file_put_contents($upload_img_file, $data);
            if ($ok === false) {
                $logger->warn('failed to save downloaded facebook user profile image to file: ' . $upload_img_file);
            } else {
                chmod($upload_img_file, 0666);
            }

            $logger->info('resizing uploaded image');
            umask(0777); // most probably ignored on windows systems
            create_resized_jpg($upload_img_file, $final_img_file, $GLOBALS['USER_IMG_MAX_WIDTH'], $GLOBALS['USER_IMG_MAX_HEIGHT']);
            create_resized_jpg($upload_img_file, $final_thumb_img_file, $GLOBALS['USER_THUMB_MAX_WIDTH'], $GLOBALS['USER_THUMB_MAX_HEIGHT']);
            chmod($final_img_file, 0666);
            chmod($final_thumb_img_file, 0666);

            unlink($upload_img_file);

            $user->image_filename = ($userImgSubdir ? $userImgSubdir . '/' : '') . $final_img_filename;

            $logger->info('user image filename: ' . $user->image_filename);

        } else {
            $logger->warn('unable to get user profile picture data from facebook!');
        }
    }
}

function getPageMode($userIsLoggedIn, &$user) {
    $mode = 'artist';
    if (get_param('signupAs') && get_param('signupAs') == 'fan') $mode = 'fan';
    if ($userIsLoggedIn && $user->is_artist) $mode = 'artist';

    return $mode;
}

function processPendingInvitations(&$user) {
    global $logger;

    // check if there are invitations for this new user and accept all of them
    $invs = Invitation::fetchAllForRecipientEmailAddress($user->email_address);
    foreach ($invs as $inv) {
        // check if project is is still valid
        $checkProject = Project::fetch_for_id($inv->project_id);
        if ($checkProject && $checkProject->id) {
            $logger->info('accepting invitation with id: ' . $inv->id);
            $puv = ProjectUserVisibility::fetch_for_user_id_project_id($user->id, $inv->project_id);
            if (!$puv || !$puv->project_id) {
                $puv = new ProjectUserVisibility();
                $puv->user_id    = $user->id;
                $puv->project_id = $inv->project_id;
                $puv->save();

                $logger->info('saved project/user visibility record');

            } else {
                $logger->info('invited user is already associated with project');
            }

        } else {
            $logger->warn('project with id ' . $inv->project_id . ' doesn\'t exist anymore! ignoring invitation.');
        }
    }
}

function createWelcomeMessage($newUserId) {
    global $logger;

    $subject = 'Welcome to Oneloudr!';
    $text = 'Start making music together with our growing community of talented musicians. Create a new project ' .
            'by uploading your stem files or join a public project that needs your talent. Remember, we\'re ' .
            'currently in "Beta mode", so it\'s possible you might find a bug here and there. Please contact ' .
            'us through our feedback tab on the left to let us know of any feature requests or bug fixes so we ' .
            'can continue to make Oneloudr the best network for musicians to create and earn together.' . "\n\n" .
            'Glad you are here. Let\'s make some Music!' . "\n\n" .
            '-Joe' . "\n" .
            'Founder, Oneloudr';

    $msg = new Message();
    $msg->sender_user_id    = $senderUser->id;
    $msg->recipient_user_id = $newUserId;
    $msg->subject           = $subject;
    $msg->text              = $text;
    $msg->marked_as_read    = false;
    $msg->save();

    $logger->info('created welcome message for new user');
}

?>
