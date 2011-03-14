<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');

$user = User::new_from_cookie(false); // fetch cookie, but don't refresh last activity timestamp
if ($user) {
    $user->doLogout();
}

if (get_param('dest')) {
    redirectTo(get_param('dest'));
} else {
    redirectTo('index.php');
}

?>