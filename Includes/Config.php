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
$WEBAPP_BASE                  = null;
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

/* include local Settings */
include 'Config_local.php';

$TEMPLATES_BASE_PATH = $BASE_PATH . 'Templates' . $PATH_SEPARATOR;

$TMP_UPLOAD_PATH = $BASE_PATH . 'Uploader' . $PATH_SEPARATOR . 'uploads' . $PATH_SEPARATOR;

$CONTENT_BASE_PATH      = $BASE_PATH . 'Content' . $PATH_SEPARATOR . 'Tracks' . $PATH_SEPARATOR;
$LOGFILE_BASE_PATH      = $BASE_PATH . 'Log' . $PATH_SEPARATOR;
$USER_IMAGE_BASE_PATH = $BASE_PATH . 'Content' . $PATH_SEPARATOR . 'UserImages' . $PATH_SEPARATOR;

$USER_IMAGE_BASE_URL  = $BASE_URL . 'Content/UserImages/';

$RETURN_URL = $BASE_URL . 'Backend/downloadStart.php';

$SELLER_EMAIL = 'joebenso@gmail.com';
if ($SANDBOX_MODE) {
    $SELLER_EMAIL = 'hanno__1207670272_biz@rastaduck.org';
}

$parts = explode('@', $SELLER_EMAIL);
$SELLER_EMAIL_NAME_PART   = $parts[0];
$SELLER_EMAIL_DOMAIN_PART = $parts[1];

$NEWS_PER_PAGE = 5;

$GENRES = array('Pop' => 'Pop', 'Rock' => 'Rock', 'Punk' => 'Punk', 'Country' => 'Country', 'Electronic' => 'Electronic', 'Blues' => 'Blues', 'Hip-Hop' => 'Hip-Hop', 'Jazz' => 'Jazz', 'Alternative' => 'Alternative', 'Singer/Songwriter' => 'Singer/Songwriter', 'Instrumental' => 'Instrumental', 'Beats' => 'Beats', 'Experimental' => 'Experimental', 'Samples or libraries' => 'Samples or libraries');
$OFFER_CATEGORIES = array('Documentary/Indie/Student Film', 'Feature Film', 'Corporate CD/DVD', 'TV and Radio Advertising', 'Web', 'Live Events', 'Sample and Remix', 'Audio Projects', 'Video Game', 'TV Show', 'Music Compilation', 'Telephony/Music on Hold', 'Video Clip/Webisode');

$COOKIE_NAME_AUTHENTICATION = 'notethrower';
$COOKIE_NAME_GENRE          = 'notethrower_genre';

$FACEBOOK_APP_ID     = '135156203220376';
$FACEBOOK_APP_SECRET = '9d58d6c9e8fb4bdf9eec658ccba46b91';

?>