<?php

include_once('../Includes/Init.php');

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Genre.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectGenre.php');

// fetch all projects
$result = _mysql_query(
    'select p.* ' .
    'from pp_project p ' .
    'where p.genres is not null and p.genres != ""'
);

while ($row = mysql_fetch_array($result)) {
    rewriteGenreInfo($row['id'], $row['genres']);
}

mysql_free_result($result);

echo 'all done.';

// END

// functions
function rewriteGenreInfo($projectId, $genreNamesStr) {
    if (!$genreNamesStr) return;

    $genreNames = explode(',', $genreNamesStr);

    foreach ($genreNames as $genreName) {
        $genreName = trim($genreName);

        // search for genre with that name
        $genre = Genre::fetchForName($genreName);

        if (!$genre || !$genre->id) {
            show_fatal_error_and_exit('Found no genre record for name: ' . $genreName);
        }

        // insert its id into the pp_project_genre table
        $pg = new ProjectGenre();
        $pg->project_id = $projectId;
        $pg->genre_id = $genre->id;
        $pg->insert();

        echo 'inserted project/genre association: ' . $projectId . '/' . $genre->id . ' ' . $genre->name . '<br>' . "\n";
    }
}

?>