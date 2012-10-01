<?php

$STAGING_ENV = 'dev_hj_laptop';

$WEBAPP_BASE = 'oneloudr/';
$DOMAIN = 'localhost';
$BASE_URL = 'http://' . $DOMAIN . '/' . $WEBAPP_BASE;
$BASE_PATH = 'C:\\xampp\\htdocs\\oneloudr\\';

$PATH_SEPARATOR = '\\'; // change to a forward slash if installed on a unix machine
$DEBUG_LOG_FILE = 'D:\\projects\\_oneloudr\\htdocs\\debug.log';
$DATABASE_HOST = 'localhost';
$DATABASE_USERNAME = 'xxxxx';
$DATABASE_PASSWORD = 'xxxxx';
$DATABASE_NAME = 'podperfect';

$EMAIL_DELIVERY_MODE                        = 'inactive'; // 'active', 'inactive' or 'override' (send all mails to override address)
$EMAIL_DELIVERY_OVERRIDE_ADDR               = 'someone@mail.org';
$EMAIL_DELIVERY_OVERRIDE_ALLOWED_RECIPIENTS = array();

//$MAIL_FROM             = '"ntdev.com" <postmaster@ntdev.com>'; // only used by Snippets.php:send_email()
$MAIL_FROM_NAME        = 'oneloudr.com';
$MAIL_FROM_ADDRESS     = 'noreply@oneloudr.com';
$MAIL_REPLY_TO_ADDRESS = 'noreply@oneloudr.com';
$MAIL_BCC_ADDRESS      = '';

$MAIL_SERVER_HOST      = 'some.smtp.server';
$MAIL_SERVER_PORT      = 25;
$MAIL_SERVER_AUTH_TYPE = '';
$MAIL_SERVER_AUTH_USER = '';
$MAIL_SERVER_AUTH_PWD  = '';

$SANDBOX_MODE = true;

// Paypal config
$PAYPAL_BASE_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr?';
$PAYPAL_FIX_PARAMS = "&cmd=_xclick&no_shipping=1&no_note=1&rm=POST&bn=PP-BuyNowBF";

// facebook api config
$FACEBOOK_APP_ID     = 'xxxxxxxx';
$FACEBOOK_APP_SECRET = 'xxxxxxxxxxxxxxxxxxxxxx';

// twitter api config
$TWITTER_CONSUMER_KEY    = 'xxxxxxxxxxxxxxxx';
$TWITTER_CONSUMER_SECRET = 'xxxxxxxxxxxxxxxxxxxxxxx';

// MailChimp API config
$MC_API_KEY = 'lakjsdhflkajshfalkasjdfhaaskldfh-us4';
$MC_LIST_ID = 'asdfadsf';

?>
