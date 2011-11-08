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

if (get_param('action') == 'search') { // ajax call
    $start       = get_numeric_param('start'); // optional
    $maxRows     = get_numeric_param('maxRows'); // optional
    $name        = get_param('name'); // optional
    $genreId     = get_numeric_param('genreId'); // optional
    $attributeId = get_numeric_param('attributeId'); // optional

    echo User::getResultsCountForSearch($name, $attributeId, $genreId) . '<hr>';

    $users = User::fetchForSearch($start, $maxRows, $name, $attributeId, $genreId);

    foreach ($users as $a) {
        echo buildArtistRow($a);
    }

    exit;
}

$latestArtistsList = '';
$latestArtists = User::fetch_all_from_to(0, 25, false, false, false, 'order by u.entry_date desc'); // FIXME - paging?
foreach ($latestArtists as $a) {
    $latestArtistsList .= buildArtistRow($a);
}

$topArtistsList = '';
$topArtists = User::fetch_most_listened_artists_from_to(0, 25); // FIXME - paging?

foreach ($topArtists as $a) {
    $topArtistsList .= buildArtistRow($a);
}

processAndPrintTpl('ArtistList/index.html', array(
    '${Common/pageHeader}'                     => buildPageHeader('Artist list', false, false),
    '${Common/bodyHeader}'                     => buildBodyHeader($user),
    '${ArtistList/artistListItem_top_list}'    => $topArtistsList,
    '${ArtistList/artistListItem_latest_list}' => $latestArtistsList,
    '${Common/newsletterSubscription}'         => processTpl('Common/newsletterSubscription.html', array()),
    '${Common/bodyFooter}'                     => buildBodyFooter(),
    '${Common/pageFooter}'                     => buildPageFooter()
));

// END

// functions
function buildArtistRow(&$a) {
    $artistAttributesArray = UserAttribute::getAttributeNamesForUserIdAndState($a->id, 'offers');
    $artistGenresArray = UserGenre::getGenreNamesForUserId($a->id);
    $artistAttributes = '';
    $artistGenres = '';
    if (count($artistAttributesArray) >  0) {
        $artistAttributes = implode(', ', $artistAttributesArray);
        $artistAttributes = 'Skills: ' . $artistAttributes;
        $artistAttributes = (strlen($artistAttributes) > 50 ? substr($artistAttributes, 0, 50) . '...' : $artistAttributes);
    }
    if (count($artistGenresArray) > 0) {
        $artistGenres = implode(', ', $artistGenresArray);
        $artistGenres = 'Genres: ' . $artistGenres;
        $artistGenres = (strlen($artistGenres) > 50 ? substr($artistGenres, 0, 50) . '...' : $artistGenres);
    }

    return processTpl('ArtistList/artistListItem.html', array(
        '${artistImgUrl}'     => getUserImageUri($a->image_filename, 'tiny'),
        '${userId}'           => $a->id,
        '${artistName}'       => escape($a->name),
        '${artistAttributes}' => $artistAttributes,
        '${artistGenres}'     => $artistGenres
    ));
}

?>        	