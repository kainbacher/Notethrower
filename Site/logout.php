<?php

include_once('../Includes/Init.php');
include_once('../Includes/DB/User.php');

$user = User::new_from_cookie(false); // fetch cookie, but don't refresh last activity timestamp
if ($user) {
    $user->doLogout();
}

header('Location: index.php');

?>