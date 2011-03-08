<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/AudioTrack.php');

$genre = null;
handleGenreSelection($genre);

$leftTrack  = null;
$rightTrack = null;
$trackSelectionMessage = getLeftAndRightTrack($leftTrack, $rightTrack, $genre);

setGenreCookie($genre);

$pageHeader = processTpl('Common/pageHeader.html', array(
    '${pageTitleSuffix}' => ' - Start'
));

$leftPlayer = processTpl('Startpage/player.html', array(
    '${trackId}' => $leftTrack->id,
    '${mp3Url}'  => $leftTrack->getPreviewMp3Url(),
));

$rightPlayer = processTpl('Startpage/player.html', array(
    '${trackId}' => $rightTrack->id,
    '${mp3Url}'  => $rightTrack->getPreviewMp3Url(),
));

$pageFooter = processTpl('Common/pageFooter.html', array());

$message = '';
if ($trackSelectionMessage) {
    $message = processTpl('Common/message.html', array(
        '${msg}' => $trackSelectionMessage
    ));
}

processAndPrintTpl('Startpage/index.html', array(
    '${Common/pageHeader}'      => $pageHeader,
    '${selectedGenre}'          => $genre,
    '${message}'                => $message,
    '${Startpage/player_left}'  => $leftPlayer,
    '${Startpage/player_right}' => $rightPlayer,
    '${Common/pageFooter}'      => $pageFooter
));

// END

// functions
// -----------------------------------------------------------------------------
function handleGenreSelection(&$genre) {
    $genre = getGenreCookieValue();
    
    if ($selectedGenre = get_param('genre')) {
        if (!isValidGenre($selectedGenre)) {
            show_fatal_error_and_exit('invalid genre value: ' . $selectedGenre);
        }
        
        $genre = $selectedGenre;
        
    } else {
        if (!$genre) { 
            $genre = chooseRandomGenre();
        }
    }
}

function getLeftAndRightTrack(&$leftTrack, &$rightTrack, &$genre) {
    global $logger;
    
    $leftTrack  = AudioTrack::fetchRandomPublicTrack($genre);
    $rightTrack = AudioTrack::fetchRandomPublicTrack($genre, $leftTrack->id); // ensure that we have different tracks
    $retryCount = 0;
    $trackSelectionMessage = null;
    while ((!$leftTrack || !$rightTrack) && $retryCount <= 100) { // search until we have two different tracks in the same genre
        $trackSelectionMessage = 'Sorry but we couldn\'t find two songs in the selected genre. A random genre was chosen.';
        $retryCount++;
        $genre = chooseRandomGenre();
        $leftTrack  = AudioTrack::fetchRandomPublicTrack($genre);
        $rightTrack = AudioTrack::fetchRandomPublicTrack($genre, $leftTrack->id); // ensure that we have different tracks        
    }
    
    if (!$leftTrack || !$rightTrack) {
        show_fatal_error_and_exit('unable to find two different tracks in the same genre, giving up.');
    }
    
    return $trackSelectionMessage;
}
?>