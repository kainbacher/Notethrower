<?php

include_once('../Includes/Init.php');

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Genre.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectGenre.php');
include_once('../Includes/DB/UserGenre.php');

// fetch all projects
$result = _mysql_query(
    'select p.* ' .
    'from pp_project p ' .
    'where p.genres is not null and p.genres != ""'
);

while ($row = mysql_fetch_array($result)) {
    $project = new Project();
    $project = Project::_read_row($project, $row);
    rewriteGenreInfo($project, $row['genres']);
}

mysql_free_result($result);

echo 'all done.';

// END

// functions
function rewriteGenreInfo(&$project, $genreNamesStr) {
    if (!$genreNamesStr) return;

    $genreNames = explode(',', $genreNamesStr);

    $relevance = 1; // the first will be the main genre, all remaining will be the sub genres

    foreach ($genreNames as $genreName) {
        $genreName = trim($genreName);

        // search for genre with that name
        $genre = Genre::fetchForName($genreName);

        if (!$genre || !$genre->id) {
            show_fatal_error_and_exit('Found no genre record for name: ' . $genreName);
        }

        // insert its id into the pp_project_genre table
        $pg = new ProjectGenre();
        $pg->project_id = $project->id;
        $pg->genre_id   = $genre->id;
        $pg->relevance  = $relevance;
        // FIXME - reactivate // $pg->insert();
        // FIXME - reactivate // echo 'inserted project/genre association: ' . $project->id . '/' . $genre->id . ' ' . $genre->name . '<br>' . "\n";

        // copy the genre info to the user profile
        $ug = UserGenre::fetchForUserIdGenreId($project->user_id, $genre->id);
        if (!$ug) {
            $ug = new UserGenre();
            $ug->user_id  = $project->user_id;
            $ug->genre_id = $genre->id;
            $ug->insert();

            echo 'inserted user/genre association: ' . $project->user_id . '/' . $genre->id . ' ' . $genre->name . '<br>' . "\n";
        }

        $relevance = 0;
    }
}

?>