<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Config.php');

$GLOBALS['DATABASE_NAME'] = 'podperfect_data'; // override test config with live db name

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');

$listFile = $GLOBALS['BASE_URL'] . 'Content/earlyAccessEmailList.txt';

$message = '';
if (get_param('email') && email_syntax_ok(get_param('email'))) {
    if (get_param('username')) {
        // check if username/email is already taken
        // first, search in the db
        $checkUser = fetch_user_for_username(get_param('username'));
        if ($checkUser && $checkUser->id) {
            $message = '<div class="notice">Sorry, but that username is already taken.<br /></div>';
        }
    }

    if (!$message) {
        $checkUser = fetch_user_for_email_address(get_param('email'));
        if ($checkUser && $checkUser->id) {
            $message = '<div class="notice">Sorry, but that email is already taken.<br /></div>';
        }
    }

    // then search in the early access email list
    if (!$message) {
        if (file_exists($listFile)) {
            $c = file_get_contents($listFile);
            $entries = explode("\n", $c);
            foreach ($entries as $entry) {
                $parts = explode(':', $entry);
                if (get_param('username') && get_param('username') == $parts[0]) {
                    $message = '<div class="notice">Sorry, but that username is already taken.<br /></div>';
                    break;

                } else if (get_param('email') == $parts[1]) {
                    $message = '<div class="notice">Sorry, but that email is already taken.<br /></div>';
                    break;
                }
            }
        }
    }

    // if check was passed, check email and save everything if email was not already in the list
    if (!$message) {
        $entries = array();
        if (file_exists($listFile)) {
            $c = file_get_contents($listFile);
            $entries = explode("\n", $c);
        }

        $entries[] = trim(get_param('username')) . ':' . trim(get_param('email'));
        sort($entries);
        file_put_contents($listFile, implode("\n", $entries));
        //chmod($listFile, 0666);

        $message = '<div class="success">Thanks, Friend.<br />' .
                   'Once we connect all the cables, we\'ll send you an email.</div>';
    }

} else if (get_param('username')) { // only a username was entered
    $message = '<div class="notice">You only entered a username.<br />' .
               'Please specify your email address, too.<br /></div>';
}

processAndPrintTpl('EarlyAccess/index.html', array(
    '${Common/pageHeader}' => buildPageHeader('Early Access'),
    '${phpSelf}'           => basename($_SERVER['PHP_SELF'], '.php'),
    '${message}'           => $message,
    '${username}'          => escape(get_param('username')),
    '${email}'             => escape(get_param('email')),
    '${Common/pageFooter}' => buildPageFooter()
));

// END

// functions

function fetch_user_for_username($username) {
    $result = _mysql_query(
        'select id ' .
        'from pp_artist ' .
        'where username = ' . qq($username)
    );

    if ($row = mysql_fetch_array($result)) {
        $a = new User();
        $a->id = $row['id'];
        mysql_free_result($result);
        return $a;

    } else {
        mysql_free_result($result);
        return null;
    }
}

function fetch_user_for_email_address($email) {
    $result = _mysql_query(
        'select id ' .
        'from pp_artist ' .
        'where email_address = ' . qq($email)
    );

    if ($row = mysql_fetch_array($result)) {
        $a = new User();
        $a->id = $row['id'];
        mysql_free_result($result);
        return $a;

    } else {
        mysql_free_result($result);
        return null;
    }
}
