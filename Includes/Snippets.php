<?php

error_reporting (E_ALL ^ E_NOTICE);

include_once('../Includes/Config.php');
include_once('../Includes/DB/ProjectFile.php');

// constants
$ASC2UNI = Array();
for($i = 128; $i < 256; $i++){
    $ASC2UNI[chr($i)] = "&#x" . dechex($i) . ";";
}

// functions
function getFileExtension($fileName) {
    $pos = strrpos($fileName, '.');
    $extension = null;
    if ($pos !== false) {
        $extension = strtolower(substr($fileName, $pos + 1));
    }
    return $extension;
}

function deleteOldTempFiles($extension) {
    global $logger;

    $expiryDays = 3;

    // safenet - if config value is missing or doesn't have a meaningful value
    if (
        !isset($GLOBALS['TEMP_FILES_BASE_PATH']) ||
        $GLOBALS['TEMP_FILES_BASE_PATH'] === ''  ||
        $GLOBALS['TEMP_FILES_BASE_PATH'] == './' ||
        $GLOBALS['TEMP_FILES_BASE_PATH'] == '.\\'
    ) {
        show_fatal_error_and_exit('suspicious temp file base path: ' . $GLOBALS['TEMP_FILES_BASE_PATH']);
    }

    $files = glob($GLOBALS['TEMP_FILES_BASE_PATH'] . '*.' . $extension);

    $logger->info('found ' . count($files) . ' old temp files with extension: ' . $extension);

    $deleteCount = 0;

    foreach ($files as $file) {
        // calculate file age in seconds
        $fileAge = time() - filemtime($file); // age = now - last modification time

        $logger->debug('age of file ' . $file . ' is: ' . ($fileAge / 60 / 60 / 24) . ' days');

        // is the file older than the given time span?
        if ($fileAge > ($expiryDays * 60 * 60 * 24)) {
            $logger->info('deleting file: ' . $file);
            unlink($file);
            $deleteCount++;
        }
    }

    $logger->info('deleted ' . $deleteCount . ' old temp files');
}

// takes the given project file IDs, takes the corresponding files and creates a zip file in the Tmp folder.
// returns the full path to the zip file or false if something went wrong.
function putProjectFilesIntoZip($projectFileIds) {
    global $logger;

    $logger->info('zipping up project files with IDs: ' . implode(', ', $projectFileIds));

    $response = false;

    $zip = new ZipArchive();
    $zipFilename = $GLOBALS['TEMP_FILES_BASE_PATH'] . str_replace('.', '_', microtime(true)) . '.zip';

    if ($zip->open($zipFilename, ZIPARCHIVE::CREATE) !== true) {
        $logger->error('cannot create zip file: ' . $zipFilename);

    } else {
        $data = ProjectFile::getFilepathsForProjectFileIds($projectFileIds);

        foreach ($data as $entry) {
            $path = $entry['path'];
            //$pathInZip = preg_replace('/[^a-zA-Z0-9.]/', '', $entry['origFilename']);
            $pathInZip = $entry['origFilename'];
            $logger->info('adding file ' . $path . ' as ' . $pathInZip);
            $zip->addFile($path, $pathInZip);
        }

        $logger->info('zipped files count: ' . $zip->numFiles);
        $logger->info('zip status: ' . $zip->status);

        if ($zip->numFiles != count($data)) {
            $logger->error('zip file count (' . $zip->numFiles . ') does not match project file count (' . count($data) . ')!');

        } else if ($zip->status !== 0) {
            $logger->error('zip file creation failed!');

        } else {
            $response = $zipFilename;
        }

        $ok = $zip->close();
        if (!$ok) {
            $logger->error('zip->close() was unsuccessful!');
            $response = false;
        }

        chmod($zipFilename, 0666);
    }

    return $response;
}

function sendJsonResponseAndExit(&$jsonResponse) {
    global $logger;

    $logger->debug('json response: ' . print_r($jsonResponse, true));
    $jsonResponse = json_encode($jsonResponse);
    header('Content-type: text/plain');
    header('Content-length: ' . strlen($jsonResponse));
    echo $jsonResponse;
    exit;
}

function redirectTo($uri) {
    global $logger;

    $logger->info('redirecting to: ' . $uri);
    header('Location: ' . $uri);
    exit;
}

function getGenreCookieValue() {
    if (isset($_COOKIE[$GLOBALS['COOKIE_NAME_GENRE']])) {
        return $_COOKIE[$GLOBALS['COOKIE_NAME_GENRE']];
    }

    return null;
}

function setGenreCookie($genre) {
    setcookie($GLOBALS['COOKIE_NAME_GENRE'], $genre, time() + 60 * 60 * 24 * 365 * 100, $GLOBALS['WEBAPP_BASE']);
}

function buildPageHeader($title, $includeJPlayerStuff = false, $includeAjaxPagination = false, $includeChosenStuff = false, $useMobileVersion = false) {
    $jplayerStylesheet = '';
    $jplayerScript     = '';
    if ($includeJPlayerStuff) {
        if ($includeJPlayerStuff === 'circlesmall') {
            $jplayerStylesheet = processTpl('Common/jPlayerStylesheetCS.html', array(), $useMobileVersion);
            $jplayerScript     = processTpl('Common/jPlayerScriptCS.html', array(), $useMobileVersion);
        }
        else {
            $jplayerStylesheet = processTpl('Common/jPlayerStylesheet.html', array(), $useMobileVersion);
            $jplayerScript     = processTpl('Common/jPlayerScript.html', array(), $useMobileVersion);
        }
    }

    $ajaxPaginationStylesheet = '';
    $ajaxPaginationScript     = '';
    if ($includeAjaxPagination) {
        $ajaxPaginationStylesheet = processTpl('Common/ajaxPaginationStylesheet.html', array(), $useMobileVersion);
        $ajaxPaginationScript     = processTpl('Common/ajaxPaginationScript.html', array(), $useMobileVersion);
    }

    $chosenStylesheet = '';
    $chosenScript     = '';
    if ($includeChosenStuff) {
        $chosenStylesheet = processTpl('Common/chosenStylesheet.html', array(), $useMobileVersion);
        $chosenScript     = processTpl('Common/chosenScript.html', array(), $useMobileVersion);
    }

    return processTpl('Common/pageHeader.html', array(
        '${pageTitle}'                                => escape($title),
        '${Common/jPlayerStylesheet_optional}'        => $jplayerStylesheet,
        '${Common/jPlayerScript_optional}'            => $jplayerScript,
        '${Common/ajaxPaginationStylesheet_optional}' => $ajaxPaginationStylesheet,
        '${Common/ajaxPaginationScript_optional}'     => $ajaxPaginationScript,
        '${Common/chosenStylesheet_optional}'         => $chosenStylesheet,
        '${Common/chosenScript_optional}'             => $chosenScript
    ), $useMobileVersion);
}

function buildBodyHeader($loggedInUser, $useMobileVersion = false) {
    $logoLinkUrl = $GLOBALS['BASE_URL'] . 'Site/dashboard.php';
    $loginBlock = '';
    $loggedInUserInfoBlockFirstRow = '';
    $loggedInUserInfoBlockSecondRow = '';

    if (!$loggedInUser) {
        $logoLinkUrl = $GLOBALS['BASE_URL'] . 'Site/index.php';

        $fbLoginUrl = $GLOBALS['STAGING_ENV'] == 'dev' ? 'fbDummy.php' : 'fb.php';
        $fbLoginUrl .= '?destUrl=' . urlencode($_SERVER['PHP_SELF']);

        $loginBlock = processTpl('Common/signUpAndLoginMenuItems.html', array(
            '${facebookLoginUrl}' => $fbLoginUrl
        ), $useMobileVersion);

    } else {
        $loggedInUserInfoBlockFirstRow = processTpl('Common/loggedInUserFirstRowMenuItems.html', array(
            '${userId}' => $loggedInUser->id
        ), $useMobileVersion);

        $loggedInUserInfoBlockSecondRow = processTpl('Common/loggedInUserSecondRowMenuItems.html', array(
            '${userId}'                         => $loggedInUser->id,
            '${dashboardActiveMenuItemClass}'   => strpos($_SERVER['PHP_SELF'], 'dashboard.php')     !== false ? ' mainMenuItemAct' : '',
            '${artistActiveMenuItemClass}'      => strpos($_SERVER['PHP_SELF'], 'artist.php')        !== false ? ' mainMenuItemAct' : '',
            '${artistListActiveMenuItemClass}'  => strpos($_SERVER['PHP_SELF'], 'artistList.php')    !== false ? ' mainMenuItemAct' : '',
            '${projectListActiveMenuItemClass}' => strpos(basename($_SERVER['PHP_SELF']), 'project') === 0     ? ' mainMenuItemAct' : '', // covers project.php and projectList.php
            '${projectActiveMenuItemClass}'     => strpos($_SERVER['PHP_SELF'], 'project.php')       !== false ? ' mainMenuItemAct' : '',
            '${accountActiveMenuItemClass}'     => strpos($_SERVER['PHP_SELF'], 'account.php')       !== false ? ' mainMenuItemAct' : ''
        ), $useMobileVersion);
    }

    return processTpl('Common/bodyHeader.html', array(
        '${logoLinkUrl}'                                    => $logoLinkUrl,
        '${Common/signUpAndLoginMenuItems_optional}'        => $loginBlock,
        '${Common/loggedInUserFirstRowMenuItems_optional}'  => $loggedInUserInfoBlockFirstRow,
        '${Common/loggedInUserSecondRowMenuItems_optional}' => $loggedInUserInfoBlockSecondRow,
    ), $useMobileVersion);
}

function buildBodyFooter($useMobileVersion = false) {
    return processTpl('Common/bodyFooter.html', array(), $useMobileVersion);
}

function buildPageFooter($useMobileVersion = false) {
    return processTpl('Common/pageFooter.html', array(), $useMobileVersion);
}

function writePageDoctype() {
    echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">' . "\n";
}

function writePageTitle() {
    echo 'oneloudr.com';
}

function writePageMetaTags() {
    echo '<meta name="keywords" content="oneloudr Notethrower podperfect Share your frequency Music Independent Artists Online Collaboration Revenue Share Money Creative Commons Mp3 Sound Remix Remixer">' . "\n";
    echo '<meta name="description" content="oneloudr - Share your frequency - Use this online collaboration platform to create and share music with others and earn money">' . "\n";
    echo '<meta name="content-language" content="en">' . "\n";
    echo '<meta name="language" content="en">' . "\n";
    echo '<meta name="robots" content="all">' . "\n";
    echo '<meta name="revisit-after" content="4 days">' . "\n";
}

function show_header_logo() {
    echo '<div id="logo" onClick="javascript:document.location.href=\'' . $GLOBALS['BASE_URL'] . 'Site/index.php' . '\';"></div>';
}

function writeGoogleAnalyticsStuff() {
    if ($GLOBALS['STAGING_ENV'] == 'live') {
        echo '<script type="text/javascript">' . "\n" .
             'var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");' . "\n" .
             'document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));' . "\n" .
             '</script>' . "\n" .
             '<script type="text/javascript">' . "\n" .
             'try {' . "\n" .
             'var pageTracker = _gat._getTracker("UA-716626-1");' . "\n" .
             'pageTracker._trackPageview();' . "\n" .
             '} catch(err) {}</script>';
    }
}

function getUserImageHtml($userImageFilename, $userName, $size) {
    global $logger;

    $userImgUrl = getUserImageUri($userImageFilename, $size);

    return '<img title="' . escape($userName) . '" src="' . $userImgUrl . '"' . ($size == 'tiny' ? ' height="30"' : '') . '>';
}

function getUserImageUri($userImageFilename, $size) {
    global $logger;

    $userImgUri = null;
    if ($userImageFilename) {
        if ($size == 'thumb' || $size == 'tiny') $userImageFilename = str_replace('.jpg', '_thumb.jpg', $userImageFilename);

        $userImg    = $GLOBALS['USER_IMAGE_BASE_PATH'] . $userImageFilename;
        $userImgUri = $GLOBALS['USER_IMAGE_BASE_URL']  . $userImageFilename;

        $logger->debug('user img: ' . $userImg);

        if (file_exists($userImg)) {
      	    $userImgUri .= '?nocache=' . filemtime($userImg); // prevent caching
        } else {
            $userImgUri = $GLOBALS['BASE_URL'] . 'Images/no_artist_image.png';
        }

    } else {
        $userImgUri = $GLOBALS['BASE_URL'] . 'Images/no_artist_image.png';
    }

    return $userImgUri;
}

function isParamSet($name) {
    if (isset($_GET[$name]) || isset($_POST[$name])) {
        return true;
    }

    return false;
}

function get_numeric_param($name) {
    $val = get_param($name);

    if ($val === NULL) return NULL;
    if ($val === '')   return '';

    if (preg_match('/[^0-9.,-]/', $val)) { // non-numeric
        return 0;
    } else if (preg_match('/^(-?\d+)[,.](\d+)$/', $val, $treffer)) { // -1234,5678 or -1234.5678
        $val = $treffer[1] . '.' . $treffer[2];
    } else if (preg_match('/^(-?[0-9.]+),(\d+)$/', $val, $treffer)) { // -1.234,56
        $val = preg_replace('/\./', '', $treffer[1]) . '.' . $treffer[2];
    } else if (preg_match('/^(-?[0-9.]+)$/', $val, $treffer)) { // -1.234.567
        $val = preg_replace('/\./', '', $treffer[1]);
    }

    return $val;
}

function get_param($name) {
    $val = '';
    if (isset($_GET[$name])) {
        $val = $_GET[$name];
    }
    if (isset($_POST[$name])) {
        $val = $_POST[$name];
    }

    if (get_magic_quotes_gpc()) {
        $val = stripslashes($val);
    }

    return trim($val);
}

function get_array_param($name) {
    $val = array();

    if (isset($_GET[$name])) {
        $val = $_GET[$name];
    }
    if (isset($_POST[$name])) {
        $val = $_POST[$name];
    }

    return $val;
}


function qq($s) {
    global $logger;

    // set string to NULL if undefined or if defined, escape problematic chars and put into doublequotes
    if (!isset($s)) {
        return 'NULL';
    }

    // this is needed to make apostrophes in strings possible and to prevent sql injections like " and "1"="1
    $s = str_replace('"', '""', $s);

    return '"' . $s . '"';
}

function qqLike($s) {
    // set string to NULL if undefined or if defined, escape problematic chars and put into doublequotes
    if (!isset($s)) {
        return 'NULL';
    }

    // this is needed to make apostrophes in strings possible and to prevent sql injections like " and "1"="1
    $s = str_replace('"', '""', $s);

    return '"%' . $s . '%"';
}

function qqList($list) {
    // set string to NULL if undefined or if defined, escape problematic chars and put into doublequotes
    if (!isset($list)) {
        return 'NULL';
    }

    $s = '';
    foreach($list as $entry) {
        $s = $s . qq($entry) . ',';
    }
    return trim($s, ',');
}

function nList($s) {
    global $logger;

    // set string to NULL if undefined or if defined, escape problematic chars and put into doublequotes
    if (!isset($s)) {
        return '()';
    }

    // prevent sql injections
    return preg_replace('[^0-9,]', '', $s);
}

function n($s) {
    // set number to NULL if undefined or if defined, escape problematic chars
    if (!isset($s) || $s === '') {
        return 'NULL';
    }

    // this is needed to prevent sql injections like " and "1"="1
    $s = preg_replace('/[^0-9.-]/', '', $s);

    return $s;
}

function b($s) { // for boolean fields which should be declared as 'tinyint(1)'. the 'bit' data type makes problems.
    if (isset($s) && $s == '1') {
        return '1';
    }

    return '0';
}

function reformat_sql_date($date_str, $day_only = false) {
    if (!$date_str) return '';

    if ($day_only) {
        return date('n/j/Y', strtotime($date_str)); // us format
        
    } else {
        //return date('j.n.Y H:i:s', strtotime($date_str));
        //return date('j.n.Y H:i', strtotime($date_str)); // german format
        return date('n/j/Y g:ia', strtotime($date_str)); // us format
    }
}

function escape($utf8_string) {
    return htmlspecialchars($utf8_string, ENT_QUOTES, 'UTF-8');
}

function escape_single_quotes($str) {
    return preg_replace("/'/", "\'", $str);
}

function escape_and_rewrite_single_quotes($utf8_string) {
    return escape(str_replace("'", "\\'", $utf8_string));
}

function show_fatal_error_and_exit($errortext) {
    global $logger;
    $logger->fatal($errortext);

    //echo '<br><font color="#FF0000">FATAL ERROR: ' . escape($errortext) . '</font><br>' . "\n";
    exit;
}

function human_readable_filesize($size){
    $i = 0;
    $iec = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    while (($size / 1024) > 1) {
      $size = $size / 1024;
      $i++;
    }
    $size = sprintf("%.1f", $size);
    return preg_replace('/\./', ',', $size) . ' ' . $iec[$i];
}

function file_date($file) {
    if (file_exists($file)) {
        return date('j.n.Y H:i', filemtime($file));
    } else {
        return '-';
    }
}

function do_upload($upload_dir, $upload_param_name, $dest_filename) {
    global $logger;

    if (substr($upload_dir, -1) != $GLOBALS['PATH_SEPARATOR']) $upload_dir = $upload_dir . $GLOBALS['PATH_SEPARATOR'];

    $logger->info('uploading file to dir: ' . $upload_dir);

    umask(0777); // most probably ignored on windows systems

    if (!is_dir($upload_dir)) create_directory($upload_dir);

    if (!move_uploaded_file($_FILES[$upload_param_name]['tmp_name'], $upload_dir . $dest_filename)) {
        show_fatal_error_and_exit(
            'Upload Error: ' . $_FILES[$upload_param_name]['error'] . "<br>\n" . print_r($_FILES) . "<br>\n" . ini_get('upload_max_filesize') . "<br>\n" . ini_get('post_max_filesize')
        );
    }

    chmod($upload_dir . $dest_filename, 0666);
}

function do_solmetra_upload($tmp_file, $upload_dir, $dest_filename, $overwrite_allowed) {
    global $logger;

    if (substr($upload_dir, -1) != $GLOBALS['PATH_SEPARATOR']) $upload_dir = $upload_dir . $GLOBALS['PATH_SEPARATOR'];

    $logger->info('uploading file to dir: ' . $upload_dir);

    umask(0777); // most probably ignored on windows systems

    if (!is_dir($upload_dir)) create_directory($upload_dir);

    move_file($tmp_file, $upload_dir . $dest_filename, $overwrite_allowed);

    chmod($upload_dir . $dest_filename, 0666);
}

function copy_file($src, $dest, $allowOverwriting = true) {
    global $logger;

    $logger->info('copying file from "' . $src . '" to "' . $dest . '"');

    umask(0777); // most probably ignored on windows systems

    if (!is_file($src)) {
        show_fatal_error_and_exit(
            'failed to copy non-existing file: ' . $src
        );
    }

    if (!$allowOverwriting && is_file($dest)) {
        show_fatal_error_and_exit(
            'cannot copy file to already existing destination file: ' . $dest
        );
    }

    $ok = copy($src, $dest);

    if (!$ok) {
        show_fatal_error_and_exit(
            'cannot copy ' . $src . ' to ' . $dest . '!'
        );
    }

    chmod($dest, 0666);
}

function move_file($src, $dest, $allowOverwriting = true) {
    global $logger;

    $logger->info('moving file from "' . $src . '" to "' . $dest . '"');

    umask(0777); // most probably ignored on windows systems

    if (!is_file($src)) {
        show_fatal_error_and_exit(
            'failed to move non-existing file: ' . $src
        );
    }

    if (!$allowOverwriting && is_file($dest)) {
        show_fatal_error_and_exit(
            'cannot move file to already existing destination file: ' . $dest
        );

    } else if ($allowOverwriting && is_file($dest)) {
        unlink($dest);
    }

    $ok = rename($src, $dest);

    if (!$ok) {
        show_fatal_error_and_exit(
            'cannot move ' . $src . ' to ' . $dest . '!'
        );
    }

    chmod($dest, 0666);
}

function create_directory($dir) {
    global $logger;

    if (is_dir($dir)) return;

    $logger->info('creating directory: ' . $dir);

    $ok = true;

    // create dir with full access rights
    umask(0000);
    $ok = mkdir($dir, 0777);

    if (!$ok) {
        show_fatal_error_and_exit(
            'Cannot create directory: ' . $dir
        );
    }
}

function read_filesize($file) {
    if (file_exists($file)) {
        return filesize($file);
    } else {
        return 0;
    }
}

function readfile_chunked($filename,$retbytes=true) { // the standard readfile() can only deliver 2 MB of data
    $chunksize = 1*(1024*1024); // how many bytes per chunk
    $buffer = '';
    $cnt = 0;
    // $handle = fopen($filename, 'rb');
    $handle = fopen($filename, 'rb');
    if ($handle === false) {
        return false;
    }
    while (!feof($handle)) {
        $buffer = fread($handle, $chunksize);
        echo $buffer;
        if ($retbytes) {
            $cnt += strlen($buffer);
        }
    }
    $status = fclose($handle);
    if ($retbytes && $status) {
        return $cnt; // return num. bytes delivered like readfile() does.
    }
    return $status;
}

function xmlentities($str){
    global $ASC2UNI;

    $str = str_replace("&", "&amp;", $str);
    $str = str_replace("<", "&lt;", $str);
    $str = str_replace(">", "&gt;", $str);
    $str = str_replace("'", "&apos;", $str);
    $str = str_replace("\"", "&quot;", $str);
    $str = str_replace("\r", "", $str);

    $str = strtr($str, $ASC2UNI);

    return $str;
}

function send_email($recipient_email, $subject, $text, $filename = '', $data = '', $mime_type = '', $reply_to = null) {
    global $logger;


    $logger->info('sending email with subject "' . $subject . '" to ' . $recipient_email . ' ...');


    if (!email_syntax_ok($recipient_email)) {
        $logger->error('cannot send email to invalid address: ' . $recipient_email);
        return false;
    }

    // for attachments
    $bound_text = md5(time());
    $bound      = '--' . $bound_text . "\r\n";
    $bound_last = '--' . $bound_text . "--\r\n";

    $with_attachment = $data && $filename && $mime_type;

    $safe_text  = normalize_newlines($text);
    $final_text = '';

    $header = 'From: ' . $GLOBALS['MAIL_FROM'] . "\r\n" .
              'X-Mailer: PHP/' . phpversion();

    if($reply_to){
        $header .= "\r\nReply-To: ".$reply_to;
    }


    if ($with_attachment) {
        $header     .= "\r\nMIME-Version: 1.0\r\n" .
                       "Content-Type: multipart/mixed; boundary=\"$bound_text\"";

        $final_text .= "Seems your mail client cannot handle MIME mails!\r\n" .
  	                   $bound;

        $final_text .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n" .
          	           "Content-Transfer-Encoding: 7bit\r\n\r\n" .
          	           $safe_text . "\r\n" .
          	           $bound;

        $filename = str_replace('"', '', $filename);

        $final_text .= "Content-Type: $mime_type; name=\"$filename\"\r\n" .
  	                   "Content-Transfer-Encoding: base64\r\n" .
  	                   "Content-disposition: attachment; file=\"$filename\"\r\n" .
  	                   "\r\n" .
  	                   chunk_split(base64_encode($data)) . // evtl. hier mit "\n" arbeiten falls mails nicht ankommen
  	                   $bound_last;

    } else {
        $final_text = $safe_text;
    }

    if ($GLOBALS['EMAIL_DELIVERY_MODE'] == 'active' || $GLOBALS['EMAIL_DELIVERY_MODE'] == 'override') {
        $logger->debug('recipient: ' . $GLOBALS['EMAIL_DELIVERY_MODE'] == 'override' ? $GLOBALS['EMAIL_DELIVERY_OVERRIDE_ADDR'] : $recipient_email);
        $logger->debug('subject: ' . $subject);
        $logger->debug('text: ' . $final_text);
        $logger->debug('header: ' . $header);

        // limit line length to 70 - does not cut words that are larger than the given width (ex: urls > 70 chars don't get cut)
        $final_text = wordwrap($final_text, 70);

        $ok = @mail(
            ($GLOBALS['EMAIL_DELIVERY_MODE'] == 'override' ? $GLOBALS['EMAIL_DELIVERY_OVERRIDE_ADDR'] : $recipient_email),
            $subject,
            $final_text,
            $header
        );

        if (!$ok) {
            $logger->error(
                'email delivery failed!' . "\n" .
                'recipient: ' . $recipient_email . "\n" .
                'subject: '   . $subject         . "\n" .
                'text: '      . $final_text
            );

        } else {
            $logger->info('mail successfully sent');
        }

        return $ok;

    } else {
        $logger->info('---- email delivery simulation ----');
        $logger->info('recipient : ' . $recipient_email);
        $logger->info('subject   : ' . $subject);
        $logger->info('text      : ' . $final_text);
        if ($with_attachment) {
            $logger->info('attachment: ' . $filename . ' (' . $mime_type . ')');
        }
        $logger->info('-----------------------------------');

        return true;
    }
}

function email_syntax_ok($addr) {
	//surpress "eregi is deprecated" Error
	error_reporting(E_ALL & ~E_DEPRECATED);
    return eregi('([_\.0-9a-z-]+@)([0-9a-z][0-9a-z-]+\.)+([a-z]{2,3})', $addr);
}

function normalize_newlines($text) {
    return str_replace(array("\r\n", "\n", "\r"), "\r\n", $text); // extend everything to \r\n
}

function create_resized_jpg($file, $destFile, $maxWidth, $maxHeight, $type) {
    global $logger;

    list($width, $height) = getimagesize($file); // FIXME - check for too large images somewhere to avoid memory problems.

    /* rewrite images instead of just copying - output should always be jpg
    if ($width <= $maxWidth && $height <= $maxHeight) {
        $logger->info('image size already ok, copying file');
        copy_file($file, $destFile);
        return;
    }
    */

    $newWidth  = 0;
    $newHeight = 0;

    if ($width <= $maxWidth && $height <= $maxHeight) { //image size already ok - rewrite with same dimensions
        $newWidth  = $width;
        $newHeight = $height;
    } else if ($width > $maxWidth && $height <= $maxHeight) { // image too wide but not too high
        $logger->info('image is too wide but not too high');

        $newWidth = $maxWidth;
        $ratio = $width / $newWidth;
        $newHeight = $height / $ratio;

    } else if ($height > $maxHeight && $width <= $maxWidth) { // image too high but not too wide
        $logger->info('image is too high but not too wide');

        $newHeight = $maxHeight;
        $ratio = $height / $newHeight;
        $newWidth = $width / $ratio;

    } else { // both too high and too wide
        $logger->info('image is both too wide and too high');

        // first adjust to max height
        $newHeight = $maxHeight;
        $ratio = $height / $newHeight;
        $newWidth = round($width / $ratio);
        $widthTmp  = $newWidth;
        $heightTmp = $newHeight;

        $logger->info('tmp width/height after height adjustment: ' . $widthTmp . 'x' . $heightTmp);

        // if still too wide, adjust to max width
        if ($newWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $ratio = $widthTmp / $newWidth;
            $newHeight = round($heightTmp / $ratio);
        }
    }

    $logger->info('destination width/height: ' . $newWidth . 'x' . $newHeight);

    // Bild neu aufbereiten
    $imageNew = imagecreatetruecolor($newWidth, $newHeight);
    if($type == 'image/jpeg'){
        $image = imagecreatefromjpeg($file);
    } elseif($type == 'image/gif') {
        $image = imagecreatefromgif($file);
    } elseif($type == 'image/png'){
        $image = imagecreatefrompng($file);
    } else {
        //fallback assuming img is jpeg
        $image = imagecreatefromjpeg($file);
    }



    imagecopyresampled($imageNew, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagedestroy($image); // free memory

    // Ausgabe des Bildes
    imagejpeg($imageNew, $destFile, 100);
    imagedestroy($imageNew); // free memory
}

function rand_char($length=6) {
    $key = '';
    $pattern = "12345678901234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    for($i=0;$i<$length;$i++){
        $key .= $pattern{rand(0,72)};
    }
    return $key;
}

?>