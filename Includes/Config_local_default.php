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

$EMAIL_DELIVERY_MODE = 'inactive'; // 'active', 'inactive' or 'override' (send all mails to override address)
$EMAIL_DELIVERY_OVERRIDE_ADDR = 'hanno@rastaduck.org';
$MAIL_FROM = '"oneloudr.com" <noreply@oneloudr.com>';

$SANDBOX_MODE = true;

// Paypal config
$PAYPAL_BASE_URL = 'https://www.sandbox.paypal.com/cgi-bin/webscr?';
$PAYPAL_FIX_PARAMS = "&cmd=_xclick&no_shipping=1&no_note=1&rm=POST&bn=PP-BuyNowBF";

// facebook api config
$FACEBOOK_APP_ID     = 'xxxxxxxx';
$FACEBOOK_APP_SECRET = 'xxxxxxxxxxxxxxxxxxxxxx';

?>