<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/UserAttribute.php');
include_once('../Includes/DB/UserGenre.php');

$visitorUserId = -1;

$userIsLoggedIn = false;
$user = User::new_from_cookie();
if ($user) {
    $visitorUserId = $user->id;
    $logger->info('visitor user id: ' . $visitorUserId);

    $userIsLoggedIn = true;
    $logger->info('user is logged in');

} else {
    $logger->info('user is NOT logged in');
}

$latestArtistsList = '';
$latestArtists = User::fetch_all_from_to(0, 999999, false, false, false, 'order by u.entry_date desc'); // FIXME - paging?
foreach ($latestArtists as $a) {
    
    $artistAttributesArray = UserAttribute::getAttributeNamesForUserIdAndState($a->id, 'offers');
    $artistGenresArray = UserGenre::getGenreNamesForUserId($a->id);
    $artistAttributes = '';
    $artistGenres = '';
    if(count($artistAttributesArray)>0){
        $artistAttributes = implode(", ", $artistAttributesArray);
        $artistAttributes = 'Skills: '.$artistAttributes;
        $artistAttributes = (strlen($artistAttributes) > 50 ? substr($artistAttributes, 0, 50).'...' : $artistAttributes);
        
    }
    if(count($artistGenresArray)>0){
        $artistGenres = implode(", ", $artistGenresArray);
        $artistGenres = 'Genres: '.$artistGenres;
        $artistGenres = (strlen($artistGenres) > 50 ? substr($artistGenres, 0, 50).'...' : $artistGenres);
    }

    $latestArtistsList .= processTpl('ArtistList/artistListItem.html', array(
        '${artistImgUrl}' => getUserImageUri($a->image_filename, 'tiny'),
        '${userId}'       => $a->id,
        '${artistName}'   => escape($a->name),
        '${artistAttributes}'   => $artistAttributes,
        '${artistGenres}'       => $artistGenres
    ));
}

$topArtistsList = '';
$topArtists = User::fetch_most_listened_artists_from_to(0, 999999); // FIXME - paging?

foreach ($topArtists as $a) {
        
    $artistAttributesArray = UserAttribute::getAttributeNamesForUserIdAndState($a->id, 'offers');
    $artistGenresArray = UserGenre::getGenreNamesForUserId($a->id);
    $artistAttributes = '';
    $artistGenres = '';
    if(count($artistAttributesArray)>0){
        $artistAttributes = implode(", ", $artistAttributesArray);
        $artistAttributes = 'Skills: '.$artistAttributes;
        $artistAttributes = (strlen($artistAttributes) > 50 ? substr($artistAttributes, 0, 50).'...' : $artistAttributes);
        
    }
    if(count($artistGenresArray)>0){
        $artistGenres = implode(", ", $artistGenresArray);
        $artistGenres = 'Genres: '.$artistGenres;
        $artistGenres = (strlen($artistGenres) > 50 ? substr($artistGenres, 0, 50).'...' : $artistGenres);
    }

    $topArtistsList .= processTpl('ArtistList/artistListItem.html', array(
        '${artistImgUrl}'       => getUserImageUri($a->image_filename, 'tiny'),
        '${userId}'             => $a->id,
        '${artistName}'         => escape($a->name),
        '${artistAttributes}'   => $artistAttributes,
        '${artistGenres}'       => $artistGenres
    ));
}

processAndPrintTpl('ArtistList/index.html', array(
    '${Common/pageHeader}'                     => buildPageHeader('Artist list', true, false),
    '${Common/bodyHeader}'                     => buildBodyHeader($user),
    '${ArtistList/artistListItem_top_list}'    => $topArtistsList,
    '${ArtistList/artistListItem_latest_list}' => $latestArtistsList,
    '${Common/newsletterSubscription}'         => processTpl('Common/newsletterSubscription.html', array()),
    '${Common/bodyFooter}'                     => buildBodyFooter(),
    '${Common/pageFooter}'                     => buildPageFooter()
));

?>        	