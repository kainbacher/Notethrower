<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Logger.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/ProjectFile.php');
include_once('../Includes/DB/User.php');

//$logger->set_debug_level();

$pfid = get_numeric_param('pfid');
$vote = get_param('vote'); // h/n for hot/not
$nextUrl = get_param('next');

if ($pfid) {
    $user = User::new_from_cookie();
    if (!userAlreadyVotedForProjectFile($user ? $user->id : null, $pfid)) {
        $pf = ProjectFile::fetch_for_id($pfid);
        if ($pf) {
            if ($user) {
                if ($user->is_pro) {
                    if ($vote == 'h') $pf->hot_count_pro = $pf->hot_count_pro + 1;
                    else $pf->not_count_pro = $pf->not_count_pro + 1;
                } else {
                    if ($vote == 'h') $pf->hot_count = $pf->hot_count + 1;
                    else $pf->not_count = $pf->not_count + 1;
                }
            } else {
                if ($vote == 'h') $pf->hot_count_anon = $pf->hot_count_anon + 1;
                else $pf->not_count_anon = $pf->not_count_anon + 1;
            }
            $pf->update();
            
            if ($user) {
                // track vote
                $vote = new Vote();
                $vote->userid = $user->id;
                $vote->pfid   = $pfid;
                $vote->insert();
                
            } else { // user is not logged in or unknown
                trackVoteInCookie($pfid);
            }
        }
        
    } else {
        $logger->info('user already voted for this project file');
    }
}

// redirect to set cookie (if applicable)
if ($next) {
    redirectTo($GLOBALS['BASE_URL'] . $next);
} else {
    redirectTo($GLOBALS['BASE_URL'] . 'charts');
}

// END

// functions

function trackVoteInCookie($pfid) { // FIXME - this can be easily exploited
    global $logger;
    
    $pfidlist = array($pfid);
    if (isset($_COOKIE['ONELOUDR_VOTES'])) { // TODO - put cookie name into config
        $pfidliststr = $_COOKIE['ONELOUDR_VOTES'];
        $pfidlist = explode(',', $pfidliststr);
        $pfidlist[] = $pfid;
    }

    $data = implode(',', array_unique($pfidlist));
    $logger->debug('setting cookie: ONELOUDR_VOTES=' . $data);
    setcookie('ONELOUDR_VOTES', $data, time() + 60 * 60 * 24 * 365 * 20, $GLOBALS['WEBAPP_BASE']); // expires in 20 years
}

?>        	
