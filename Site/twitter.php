<?php

include_once('../Includes/Init.php');
include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectFile.php');
require_once('../Includes/twitteroauth/twitteroauth.php');

$action = get_param('action');

$twitterAction = get_param('twitterAction');
$data          = get_param('data');
$returnUrl     = get_param('returnUrl');
$cs            = get_param('cs');

// http://ntdev.com/Site/twitter.php?action=connect&twitterAction=tweetAboutRelease&data=22&cs=056d2ad3af7e8e0d9774aa466f6ce69f&returnUrl=http://ntdev.com/
// http://oneloudr.com/OLTest/Site/twitter.php?action=connect&twitterAction=tweetAboutRelease&data=165&cs=bd26a82906301d78d6494c6f104ae163&returnUrl=

if (md5('ErpaDerpa!' . $twitterAction . '_' . $data . '_' . $returnUrl) != $cs) {
    show_fatal_error_and_exit('checksum failure!');
}

if ($action == 'connect') {
    if (!$GLOBALS['TWITTER_CONSUMER_KEY'] || !$GLOBALS['TWITTER_CONSUMER_SECRET']) {
        show_fatal_error_and_exit('$TWITTER_CONSUMER_KEY or $TWITTER_CONSUMER_SECRET missing in local config!');
    }
    
    if (!$twitterAction) {
        show_fatal_error_and_exit('returnUrl param is missing!');
    }
    
    session_start();

    // Build TwitterOAuth object with client credentials.
    $connection = new TwitterOAuth($GLOBALS['TWITTER_CONSUMER_KEY'], $GLOBALS['TWITTER_CONSUMER_SECRET']);
     
    // Get temporary credentials.
    $callbackUrl = $GLOBALS['BASE_URL'] . 'Site/' . basename($_SERVER['PHP_SELF']) . 
                   '?action=callback' .
                   '&twitterAction=' . $twitterAction .
                   '&data='          . urlencode($data) .
                   '&returnUrl='     . urlencode($returnUrl) .
                   '&cs='            . $cs;
    $logger->info('callback url: ' . $callbackUrl);
    $request_token = $connection->getRequestToken($callbackUrl);
    $logger->info('request_token: ' . print_r($request_token, true));

    // Save temporary credentials to session.
    $_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
    $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
    
    // store custom data in session, too // FIXME - for some reason this gets lost when testing on dreamhost. it works locally. interim solution is to transfer all data in get parameters
    $_SESSION['twitterAction'] = $twitterAction; // a key which controls what shall happen after authentication (eg. the string tweetAboutRelease)
    $_SESSION['data']          = $data; // use this for transportation of the payload for the twitter action (currently this holds eg the pfid of the release file)
    $_SESSION['returnUrl']     = $returnUrl;
    $_SESSION['cs']            = $cs;
    
    $logger->info('session: ' . print_r($_SESSION, true));
 
    switch ($connection->http_code) {
      case 200:
        // Build authorize URL and redirect user to Twitter. 
        $url = $connection->getAuthorizeURL($token);
        redirectTo($url); 
        
      default:
        // Show notification if something went wrong. 
        echo 'Could not connect to Twitter. Refresh the page or try again later.';
    }
    
    exit;

} else if ($action == 'callback') {
    session_start();
    
    $logger->info('session: ' . print_r($_SESSION, true));

    // If the oauth_token is old redirect to the connect page.
    if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
        $_SESSION['oauth_status'] = 'oldtoken';
        redirectTo($_SERVER['PHP_SELF'] . '?action=clearsessions' .
                                          '&twitterAction=' . $twitterAction .
                                          '&data='          . urlencode($data) .
                                          '&returnUrl='     . urlencode($returnUrl) .
                                          '&cs='            . $cs);
    }
    
    // Create TwitteroAuth object with app key/secret and token key/secret from default phase
    $connection = new TwitterOAuth($GLOBALS['TWITTER_CONSUMER_KEY'], $GLOBALS['TWITTER_CONSUMER_SECRET'], 
            $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
    
    // Request access tokens from twitter
    $access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);
    $logger->info('access_token: ' . print_r($access_token, true));
    
    // Save the access tokens. Normally these would be saved in a database for future use.
    $_SESSION['access_token'] = $access_token;
    
    // Remove no longer needed request tokens
    unset($_SESSION['oauth_token']);
    unset($_SESSION['oauth_token_secret']);
    
    // If HTTP response is 200 continue, otherwise send to connect action to retry
    if (200 == $connection->http_code) {
        // The user has been verified and the access tokens can be saved for future use
        $_SESSION['status'] = 'verified';
        redirectTo($_SERVER['PHP_SELF'] . '?action=postTweet' .
                                          '&twitterAction=' . $twitterAction .
                                          '&data='          . urlencode($data) .
                                          '&returnUrl='     . urlencode($returnUrl) .
                                          '&cs='            . $cs);
      
    } else {
        /* Save HTTP status for error dialog on connnect page.*/
        redirectTo($_SERVER['PHP_SELF'] . '?action=clearsessions' .
                                          '&twitterAction=' . $twitterAction .
                                          '&data='          . urlencode($data) .
                                          '&returnUrl='     . urlencode($returnUrl) .
                                          '&cs='            . $cs);
    }
    
    exit;

} else if ($action == 'clearsessions') {
    $logger->info('clearing sessions');
    
    session_start();
    session_destroy();
 
    // Redirect to connect action
    redirectTo($_SERVER['PHP_SELF'] . '?action=connect' .
                                      '&twitterAction=' . $twitterAction .
                                      '&data='          . urlencode($data) .
                                      '&returnUrl='     . urlencode($returnUrl) .
                                      '&cs='            . $cs);
    
    exit;

} else if ($action == 'postTweet') {
    $logger->info('posting tweet');
    
    session_start();
    
    // If access tokens are not available redirect to connect page.
    if (
        empty($_SESSION['access_token']) || 
        empty($_SESSION['access_token']['oauth_token']) || 
        empty($_SESSION['access_token']['oauth_token_secret'])
    ) {
        redirectTo($_SERVER['PHP_SELF'] . '?action=clearsessions' .
                                          '&twitterAction=' . $twitterAction .
                                          '&data='          . urlencode($data) .
                                          '&returnUrl='     . urlencode($returnUrl) .
                                          '&cs='            . $cs);
    }
    
    // Get user access tokens out of the session.
    $access_token = $_SESSION['access_token'];
    
    // Create a TwitterOauth object with consumer/user tokens.
    $connection = new TwitterOAuth($GLOBALS['TWITTER_CONSUMER_KEY'], $GLOBALS['TWITTER_CONSUMER_SECRET'], 
            $access_token['oauth_token'], $access_token['oauth_token_secret']);
    
    // post the tweet
    $logger->info('twitterAction: ' . $twitterAction);
    $logger->info('data: ' . $data);
    $logger->info('returnUrl: ' . $returnUrl);
    $logger->info('cs:' . $cs);
    
    if ($twitterAction == 'tweetAboutRelease') {
        $projectFile = ProjectFile::fetch_for_id($data);
        if (!$projectFile || !$projectFile->id) {
            show_fatal_error_and_exit('found no project file for id: ' . $data);
        }
        
        if ($projectFile->type != 'release' || $projectFile->status != 'active') {
            show_fatal_error_and_exit('project file is no release or is not active!');
        }
        
        $project = Project::fetch_for_id($projectFile->project_id);
        
        $releaseUrl = getReleaseUrl($projectFile->id, $projectFile->release_title);
        $logger->info('release url: ' . $releaseUrl);
                
        $tweetText = 'I just released "' . $projectFile->release_title . '" from my project "' .
                     $project->title . '" at ' . $releaseUrl;
        if (strlen($tweetText) > 140) {
            $tweetText = 'Check this out: ' . $releaseUrl; // FIXME - ok this way?
        }
        if (strlen($tweetText) > 140) {
            $tweetText = $releaseUrl; // FIXME - ok this way?
        }
                             
        $response = $connection->post('statuses/update', array('status' => $tweetText));
        $logger->info('status/update response: ' . print_r($response, true));
        
    } else {
        show_fatal_error_and_exit('unrecognized twitterAction: ' . $twitterAction);
    }
    
    /* Some example calls */
    //$connection->get('users/show', array('screen_name' => 'abraham'));
    //$connection->post('statuses/update', array('status' => date(DATE_RFC822)));
    //$connection->post('statuses/destroy', array('id' => 5437877770));
    //$connection->post('friendships/create', array('id' => 9436992)));
    //$connection->post('friendships/destroy', array('id' => 9436992)));
    
    if ($returnUrl) {
        redirectTo($returnUrl);
    } else {
        redirectTo($GLOBALS['BASE_URL']);
    }
    
    exit;

} else {
    show_fatal_error_and_exit('unrecognized action param: ' . $action);
}

?>