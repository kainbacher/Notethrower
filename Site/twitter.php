<?php

include_once('../Includes/Init.php');
include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectFile.php');
require_once('../Includes/twitteroauth/twitteroauth.php');

$action = get_param('action');

if ($action == 'connect') {
    if (!$GLOBALS['TWITTER_CONSUMER_KEY'] || !$GLOBALS['TWITTER_CONSUMER_SECRET']) {
        show_fatal_error_and_exit('$TWITTER_CONSUMER_KEY or $TWITTER_CONSUMER_SECRET missing in local config!');
    }
    
    if (!get_param('twitterAction')) {
        show_fatal_error_and_exit('returnUrl param is missing!');
    }
    
    if (!get_param('returnUrl')) {
        show_fatal_error_and_exit('returnUrl param is missing!');
    }
    
    session_start();

    // Build TwitterOAuth object with client credentials.
    $connection = new TwitterOAuth($GLOBALS['TWITTER_CONSUMER_KEY'], $GLOBALS['TWITTER_CONSUMER_SECRET']);
     
    // Get temporary credentials.
    $callbackUrl = $GLOBALS['BASE_URL'] . 'Site/' . basename($_SERVER['PHP_SELF']) . 
                   '?action=callback';
    $logger->info('callback url: ' . $callbackUrl);
    $request_token = $connection->getRequestToken($callbackUrl);
    $logger->info('request_token: ' . print_r($request_token, true));

    // Save temporary credentials to session.
    $_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
    $_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
    
    // store custom data in session, too
    $_SESSION['twitterAction'] = get_param('twitterAction'); // a key which controls what shall happen after authentication (eg. the string tweetAboutRelease)
    $_SESSION['data']          = get_param('data'); // use this for transportation of the payload for the twitter action (currently this holds eg the pfid of the release file)
    $_SESSION['returnUrl']     = get_param('returnUrl');
 
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

    // If the oauth_token is old redirect to the connect page.
    if (isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']) {
        $_SESSION['oauth_status'] = 'oldtoken';
        redirectTo($_SERVER['PHP_SELF'] . '?action=clearsessions');
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
    
    // If HTTP response is 200 continue otherwise send to connect action to retry
    if (200 == $connection->http_code) {
        // The user has been verified and the access tokens can be saved for future use
        $_SESSION['status'] = 'verified';
        redirectTo($_SERVER['PHP_SELF'] . '?action=postTweet');
      
    } else {
        /* Save HTTP status for error dialog on connnect page.*/
        redirectTo($_SERVER['PHP_SELF'] . '?action=clearsessions');
    }
    
    exit;

} else if ($action == 'clearsessions') {
    $logger->info('clearing sessions');
    
    session_start();
    session_destroy();
 
    // Redirect to connect action
    redirectTo($_SERVER['PHP_SELF'] . '?action=connect');
    
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
        redirectTo($_SERVER['PHP_SELF'] . '?action=clearsessions');
    }
    
    // Get user access tokens out of the session.
    $access_token = $_SESSION['access_token'];
    
    // Create a TwitterOauth object with consumer/user tokens.
    $connection = new TwitterOAuth($GLOBALS['TWITTER_CONSUMER_KEY'], $GLOBALS['TWITTER_CONSUMER_SECRET'], 
            $access_token['oauth_token'], $access_token['oauth_token_secret']);
    
    // post the tweet
    $logger->info('twitterAction param: ' . $_SESSION['twitterAction']);
    $logger->info('data param: ' . $_SESSION['data']);
    $logger->info('returnUrl: ' . $_SESSION['returnUrl']);
    
    if ($_SESSION['twitterAction'] == 'tweetAboutRelease') {
        $projectFile = ProjectFile::fetch_for_id($_SESSION['data']);
        if (!$projectFile || !$projectFile->id) {
            show_fatal_error_and_exit('found no project file for id: ' . $_SESSION['data']);
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
        show_fatal_error_and_exit('unrecognized twitterAction: ' . $_SESSION['twitterAction']);
    }
    
    /* Some example calls */
    //$connection->get('users/show', array('screen_name' => 'abraham'));
    //$connection->post('statuses/update', array('status' => date(DATE_RFC822)));
    //$connection->post('statuses/destroy', array('id' => 5437877770));
    //$connection->post('friendships/create', array('id' => 9436992)));
    //$connection->post('friendships/destroy', array('id' => 9436992)));
    
    if ($_SESSION['returnUrl']) {
        redirectTo($_SESSION['returnUrl']);
    } else {
        show_fatal_error_and_exit('found no returnUrl value in $_SESSION!');
    }
    
    exit;

} else {
    show_fatal_error_and_exit('unrecognized action param: ' . $action);
}

?>