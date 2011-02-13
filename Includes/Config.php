<?php

$SESSION_LIFETIME_SECONDS = 180 * 60;

$ARTIST_IMG_MAX_WIDTH  = 300;
$ARTIST_IMG_MAX_HEIGHT = 300;

$ARTIST_THUMB_MAX_WIDTH  = 100;
$ARTIST_THUMB_MAX_HEIGHT = 100;

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

if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_ADDR'] == '127.0.0.1') { // localhost
    $includes_base_path = dirname(__FILE__);

    if (file_exists($includes_base_path . '/USE_HJ_VAIO_CONFIG.txt')) {
        // local config - laptop
        // --------------------------------------------------------------------------------------
        $STAGING_ENV = 'dev_hj_laptop';

        $WEBAPP_BASE = 'notethrower/';
        $DOMAIN      = 'localhost';
        $BASE_URL    = 'http://' . $DOMAIN . '/' . $WEBAPP_BASE;
        $BASE_PATH   = 'C:\\xampp\\htdocs\\notethrower\\';

        $PATH_SEPARATOR    = '\\'; // change to a forward slash if installed on a unix machine
        $DEBUG_LOG_FILE    = 'D:\\projects\\_notethrower\\WebsitePlusWidget\\htdocs\\debug.log';
        $DATABASE_HOST     = 'localhost';
        $DATABASE_USERNAME = 'root';
        $DATABASE_PASSWORD = '';
        $DATABASE_NAME     = 'podperfect';

        $EMAIL_DELIVERY_MODE             = 'inactive'; // 'active', 'inactive' or 'override' (send all mails to override address)
        $EMAIL_DELIVERY_OVERRIDE_ADDR    = 'hanno@rastaduck.org';
        $MAIL_FROM                       = '"notethrower.com" <noreply@notethrower.com>';

        $SANDBOX_MODE = true;

    } else {
        // local config - workstation
        // --------------------------------------------------------------------------------------
        $STAGING_ENV = 'dev_hj_workstation';

        $WEBAPP_BASE = 'notethrower/';
        $DOMAIN      = 'localhost';
        $BASE_URL    = 'http://' . $DOMAIN . '/' . $WEBAPP_BASE;
        $BASE_PATH   = 'C:\\xampp\\htdocs\\notethrower\\';

        $PATH_SEPARATOR    = '\\'; // change to a forward slash if installed on a unix machine
        $DEBUG_LOG_FILE    = 'E:\\projects\\_notethrower\\WebsitePlusWidget\\htdocs\\debug.log';
        $DATABASE_HOST     = 'localhost';
        $DATABASE_USERNAME = 'root';
        $DATABASE_PASSWORD = '';
        $DATABASE_NAME     = 'notethrower';

        $EMAIL_DELIVERY_MODE             = 'inactive'; // 'active', 'inactive' or 'override' (send all mails to override address)
        $EMAIL_DELIVERY_OVERRIDE_ADDR    = 'hanno@rastaduck.org';
        $MAIL_FROM                       = '"notethrower.com" <noreply@notethrower.com>';

        $SANDBOX_MODE = true;
    }

} else if (strpos($_SERVER['SERVER_NAME'], 'rastaduck.org') !== false) {
    // rastaduck.org config
    // --------------------------------------------------------------------------------------
    $STAGING_ENV = 'test_rastaduck';

    $WEBAPP_BASE = 'tmp/notethrower/';
    $DOMAIN      = 'www.rastaduck.org';
    $BASE_URL    = 'http://' . $DOMAIN . '/' . $WEBAPP_BASE;
    $BASE_PATH   = '/var/www/vhosts/rastaduck.org/httpdocs/tmp/notethrower/';

    $PATH_SEPARATOR    = '/'; // use \\ for a windows machine
    $DEBUG_LOG_FILE    = 'debug.log';
    $DATABASE_HOST     = 'localhost';
    $DATABASE_USERNAME = 'usr_web1186_4';
    $DATABASE_PASSWORD = 'RaDu86';
    $DATABASE_NAME     = 'usr_web1186_4';

    $EMAIL_DELIVERY_MODE             = 'active'; // 'active', 'inactive' or 'override' (send all mails to override address)
    $EMAIL_DELIVERY_OVERRIDE_ADDR    = 'hanno@rastaduck.org';
    $MAIL_FROM                       = '"notethrower.com" <noreply@notethrower.com>';

    $SANDBOX_MODE = true;

} else {
    if (preg_match('#/NTTest/Includes/Config.php$#', __FILE__)) {
        // notethrower.com Test system config
        // --------------------------------------------------------------------------------------
        $STAGING_ENV = 'test';

        $WEBAPP_BASE = 'NTTest/';
        $DOMAIN      = 'www.notethrower.com';
        $BASE_URL    = 'http://' . $DOMAIN .'/' . $WEBAPP_BASE;
        $BASE_PATH   = '/home/voicehero/podperfect.com/NTTest/';

        $PATH_SEPARATOR    = '/'; // use \\ for a windows machine
        $DEBUG_LOG_FILE    = 'debug.log';
        $DATABASE_HOST     = 'mysql.podperfect.com';
        $DATABASE_USERNAME = 'widget';
        $DATABASE_PASSWORD = 'PpW1dg3t!';
        $DATABASE_NAME     = 'podperfect_data_test';

        $EMAIL_DELIVERY_MODE             = 'active'; // 'active', 'inactive' or 'override' (send all mails to override address)
        $EMAIL_DELIVERY_OVERRIDE_ADDR    = 'hanno@rastaduck.org';
        $MAIL_FROM                       = '"notethrower.com" <postmaster@notethrower.com>';

        $SANDBOX_MODE = true;

    } else {
        // live (notethrower.com) config
        // --------------------------------------------------------------------------------------
        $STAGING_ENV = 'live';

        $WEBAPP_BASE = 'NT/';
        $DOMAIN      = 'www.notethrower.com';
        $BASE_URL    = 'http://' . $DOMAIN . '/' . $WEBAPP_BASE;
        $BASE_PATH   = '/home/voicehero/podperfect.com/NT/';

        $PATH_SEPARATOR    = '/'; // use \\ for a windows machine
        $DEBUG_LOG_FILE    = 'debug.log';
        $DATABASE_HOST     = 'mysql.podperfect.com';
        $DATABASE_USERNAME = 'widget';
        $DATABASE_PASSWORD = 'PpW1dg3t!';
        $DATABASE_NAME     = 'podperfect_data';

        $EMAIL_DELIVERY_MODE             = 'active'; // 'active', 'inactive' or 'override' (send all mails to override address)
        $EMAIL_DELIVERY_OVERRIDE_ADDR    = 'hanno@rastaduck.org';
        $MAIL_FROM                       = '"notethrower.com" <postmaster@notethrower.com>';

        $SANDBOX_MODE = false;
    }
}

$TMP_UPLOAD_PATH = $BASE_PATH . 'Uploader' . $PATH_SEPARATOR . 'uploads' . $PATH_SEPARATOR;

$CONTENT_BASE_PATH      = $BASE_PATH . 'Content' . $PATH_SEPARATOR . 'Tracks' . $PATH_SEPARATOR;
$LOGFILE_BASE_PATH      = $BASE_PATH . 'Log' . $PATH_SEPARATOR;
$ARTIST_IMAGE_BASE_PATH = $BASE_PATH . 'Content' . $PATH_SEPARATOR . 'ArtistImages' . $PATH_SEPARATOR;

$ARTIST_IMAGE_BASE_URL  = $BASE_URL . 'Content/ArtistImages/';

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


?>
