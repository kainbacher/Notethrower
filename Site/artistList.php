<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/UserAttribute.php');
include_once('../Includes/DB/UserGenre.php');
include_once('../Includes/DB/Genre.php');
include_once('../Includes/DB/Attribute.php');

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
    $page       = get_numeric_param('page'); // optional
    //$maxRows     = get_numeric_param('maxRows'); // optional
    $maxRows     = 5;
    $start       = $page * $maxRows;
    $name        = (get_param('name') == 'Artist Name' ? false : get_param('name')); // optional
    $genreId     = get_numeric_param('genreId'); // optional
    $attributeId = get_numeric_param('attributeId'); // optional
    
    
    $rowCount = User::getResultsCountForSearch($name, $attributeId, $genreId);

    $users = User::fetchForSearch($start, $maxRows, $name, $attributeId, $genreId);
    $result = array();
    foreach ($users as $a) {
        $result['serp'] .= buildArtistRow($a);
    }
    if($rowCount > 0){
        $paginationUrl = '?action=search&name='.$name.'&genreId='.$genreId.'&attributeId='.$attributeId;
        $result['pagination'] = buildPagination($rowCount, $maxRows, $page, $paginationUrl);
    }
    echo json_encode($result);

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

$genreOptions = '';
$genres = Genre::getSelectorOptionsArray(true);
foreach($genres as $genreKey => $genreOption){
    $genreOptions .= '<option value="'.$genreKey.'">'.$genreOption.'</option>';
}

$attributeOptions = '<option value=""></option>';
$attributes = Attribute::getIdNameMapShownFor('needs');
foreach($attributes as $attributeKey => $attributeOption){
    $attributeOptions .= '<option value="'.$attributeKey.'">'.$attributeOption.'</option>';
}


processAndPrintTpl('ArtistList/index.html', array(
    '${Common/pageHeader}'                     => buildPageHeader('Artist list', false, false, true),
    '${Common/bodyHeader}'                     => buildBodyHeader($user),
    '${ArtistList/artistListItem_top_list}'    => $topArtistsList,
    '${ArtistList/artistListItem_latest_list}' => $latestArtistsList,
    '${genreSelect}'                           => $genreOptions,
    '${attributeSelect}'                       => $attributeOptions,
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
        $artistAttributes = '<span class="titleText">Skills:</span> ' . $artistAttributes;
        $artistAttributes = (strlen($artistAttributes) > 200 ? substr($artistAttributes, 0, 200) . '...' : $artistAttributes);
    }
    if (count($artistGenresArray) > 0) {
        $artistGenres = implode(', ', $artistGenresArray);
        $artistGenres = '<span class="titleText">Genres: </span>' . $artistGenres;
        $artistGenres = (strlen($artistGenres) > 200 ? substr($artistGenres, 0, 200) . '...' : $artistGenres);
    }

    return processTpl('ArtistList/artistListItem.html', array(
        '${artistImgUrl}'     => getUserImageUri($a->image_filename, 'tiny'),
        '${userId}'           => $a->id,
        '${artistName}'       => escape($a->name),
        '${artistAttributes}' => $artistAttributes,
        '${artistGenres}'     => $artistGenres
    ));
}

function buildPagination($totalRows, $perPage, $currentPage = 0, $paginationUrl){
    
    $pageCount = ceil($totalRows/$perPage);
    
    $pagination = '';
    for($i=0; $i < $pageCount; $i++){
        if($i == $currentPage){
            $pagination .= '<span style="font-weight:bold"> '.($i+1).' </span>';
        } else {
            $pagination .= '<a href="'.$paginationUrl.'&page='.$i.'" rel="pagination"> '.($i+1).' </a>';
        }
        
    }
    $previousPage   = ($currentPage+1) > 1 ? '<a href="'.$paginationUrl.'&page='.($currentPage-1).'" rel="pagination">Previous</a> | ' : null;
    $nextPage       = ($currentPage+1) < $pageCount ? '<a href="'.$paginationUrl.'&page='.($currentPage+1).'" rel="pagination">Next</a> | ' : null;
    $pagination = $previousPage . $pagination . $nextPage;
    return $pagination;

}

?>        	