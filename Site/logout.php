<?php

include_once('../Includes/Init.php');
include_once('../Includes/DB/Artist.php');

$artist = Artist::new_from_cookie(false); // fetch cookie, but don't refresh last activity timestamp
if ($artist) {
    $artist->doLogout();
}

header('Location: index.php');

?>