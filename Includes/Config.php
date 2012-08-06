<?php

$SESSION_LIFETIME_SECONDS = 180 * 60;

$USER_IMG_MAX_WIDTH  = 300;
$USER_IMG_MAX_HEIGHT = 300;

$USER_THUMB_MAX_WIDTH  = 100;
$USER_THUMB_MAX_HEIGHT = 100;

$STAGING_ENV                  = '-';
$SANDBOX_MODE                 = false;
$DOMAIN                       = null;
$BASE_URL                     = null;
$WEBAPP_BASE                  = null; // must start and end with a slash
$PATH_SEPARATOR               = null;
$DEBUG_LOG_FILE               = null;
$DATABASE_HOST                = null;
$DATABASE_USERNAME            = null;
$DATABASE_PASSWORD            = null;
$DATABASE_NAME                = null;
$BASE_PATH                    = null;
$EMAIL_DELIVERY_MODE          = null;
$EMAIL_DELIVERY_OVERRIDE_ADDR = null;
$MAIL_FROM                    = null;

$LOGFILE_TTL_DAYS = 90; // ~ 3 months

/* include local Settings */
include 'Config_local.php';

$TEMPLATES_BASE_PATH = $BASE_PATH . 'Templates' . $PATH_SEPARATOR;

$TMP_UPLOAD_PATH = $BASE_PATH . 'Uploader' . $PATH_SEPARATOR . 'uploads' . $PATH_SEPARATOR;

$CONTENT_BASE_PATH    = $BASE_PATH . 'Content' . $PATH_SEPARATOR . 'Tracks' . $PATH_SEPARATOR;
$LOGFILE_BASE_PATH    = $BASE_PATH . 'Log' . $PATH_SEPARATOR;
$USER_IMAGE_BASE_PATH = $BASE_PATH . 'Content' . $PATH_SEPARATOR . 'UserImages' . $PATH_SEPARATOR;
$TEMP_FILES_BASE_PATH = $BASE_PATH . 'Tmp' . $PATH_SEPARATOR;

$USER_IMAGE_BASE_URL  = $BASE_URL . 'Content/UserImages/';
$TEMP_FILES_BASE_URL  = $BASE_URL . 'Tmp/';

$RETURN_URL = $BASE_URL . 'Backend/downloadStart.php';

$SELLER_EMAIL = 'joebenso@gmail.com';
if ($SANDBOX_MODE) {
    $SELLER_EMAIL = 'hanno__1207670272_biz@rastaduck.org';
}

$parts = explode('@', $SELLER_EMAIL);
$SELLER_EMAIL_NAME_PART   = $parts[0];
$SELLER_EMAIL_DOMAIN_PART = $parts[1];

$NEWS_PER_PAGE = 5;

$OFFER_CATEGORIES = array('Documentary/Indie/Student Film', 'Feature Film', 'Corporate CD/DVD', 'TV and Radio Advertising', 'Web', 'Live Events', 'Sample and Remix', 'Audio Projects', 'Video Game', 'TV Show', 'Music Compilation', 'Telephony/Music on Hold', 'Video Clip/Webisode');

$COOKIE_NAME_AUTHENTICATION = 'oneloudr';
$COOKIE_NAME_GENRE          = 'oneloudr_genre';

$RECAPTCHA_PUBLIC_KEY = '6LcNIgoAAAAAAP0BgB5wNty92PiCewdRq7y5L6qw';

$ALLOWED_UPLOAD_EXTENSIONS = array('wav', 'aif', 'aiff', 'mp3', 'mid', 'midi', 'txt');

// Transcoder settings
// Full path to lame
$TRANSCODER_COMMAND = '/home/benso/lame/bin/lame'; // FIXME - this should be in the local config instead
// options for the transcoder, based on the type of the projectFile type
$TRANSCODER_OPTIONS = array('raw'=>'-S -b 160', 'mix'=>'-S -b 320');

// the email address which shall get the feedback via the contact form
$CONTACT_FORM_RECIPIENT_EMAIL = 'go@oneloudr.com';

$EDITOR_INFO_ID_ABOUT_TEXT_4_ARTISTS = 'aboutText4Artists';
$EDITOR_INFO_ID_ABOUT_TEXT_4_LABELS  = 'aboutText4Labels';
$EDITOR_INFO_ID_TERMS_OF_USE         = 'termsOfUse';
$EDITOR_INFO_ID_FAQ                  = 'faq';
$EDITOR_INFO_ID_PROUDLOUDR_INFO      = 'proudLoudrInfo';

$EDITOR_INFO_ID_LIST = array(
    $EDITOR_INFO_ID_ABOUT_TEXT_4_ARTISTS => 'Oneloudr for artists page text',
    $EDITOR_INFO_ID_ABOUT_TEXT_4_LABELS  => 'Oneloudr for labels page text',
    $EDITOR_INFO_ID_TERMS_OF_USE         => 'Terms of use text',
    $EDITOR_INFO_ID_FAQ                  => 'FAQ text',
    $EDITOR_INFO_ID_PROUDLOUDR_INFO      => 'ProudLoudr info text'
);

$MISSING_EDITOR_INFO_TEXT = '[No editor info text available]';

?>
