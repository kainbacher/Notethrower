<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_project_genre table
class ProjectGenre {
    var $project_id;
    var $genre_id;

    // non-table fields
    var $genre_name; // currently not read/not used

    // constructors
    // ------------
    function ProjectGenre() {
    }

    function fetchAllOfProjectsOfUser($user_id) {
        $objs = array();

        $result = _mysql_query(
            'select pg.* ' .
            'from pp_project_genre pg, pp_project p ' .
            'where p.user_id = ' . n($user_id) . ' ' .
            'and p.status != "finished" ' .
            'and p.id = pg.project_id'
        );

        while ($row = mysql_fetch_array($result)) {
            $pg = new ProjectGenre();
            ProjectGenre::_read_row($pg, $row);

            $objs[] = $pg;
        }

        mysql_free_result($result);

        return $objs;
    }

    function addAll($genreIds, $projectId) {
        foreach ($genreIds as $id) {
            if ($id) {
                $pg = new ProjectGenre();
                $pg->project_id = $projectId;
                $pg->genre_id   = $id;
                $pg->insert();
            }
        }
    }

    function _read_row(&$pg, $row) {
        $pg->project_id = $row['project_id'];
        $pg->genre_id   = $row['genre_id'];

        // non-table fields
        $pg->genre_name = $row['genre_name'];

        return $pg;
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_project_genre ' .
            '(' .
            'project_id int(10) not null, ' .
            'genre_id   int(10) not null, ' .
            'primary key (project_id, genre_id), ' .
            'index (project_id), ' .
            'index (genre_id) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function getGenreIdsForProjectId($project_id) {
        $ids = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_project_genre ' .
            'where project_id = ' . n($project_id)
        );

        while ($row = mysql_fetch_array($result)) {
            $ids[] = $row['genre_id'];
        }

        mysql_free_result($result);

        return $ids;
    }

    function getGenreNamesForProjectId($project_id) {
        $names = array();

        $result = _mysql_query(
            'select g.name as genre_name ' .
            'from pp_project_genre pg, pp_genre g ' .
            'where pg.project_id = ' . n($project_id) . ' ' .
            'and pg.genre_id = g.id'
        );

        while ($row = mysql_fetch_array($result)) {
            $names[] = $row['genre_name'];
        }

        mysql_free_result($result);

        return $names;
    }

    function deleteForProjectId($projectId) {
        return _mysql_query(
            'delete from pp_project_genre ' .
            'where project_id = ' . n($projectId)
        );
    }

    // object methods
    // --------------
    function insert() {
        $ok = _mysql_query(
            'insert into pp_project_genre ' .
            '(project_id, genre_id) ' .
            'values (' .
            n($this->project_id)    . ', ' .
            n($this->genre_id)      .
            ')'
        );

        if (!$ok) {
            return false;
        }

        return $ok;
    }
}

?>