<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_project_genre table
class ProjectGenre {
    var $project_id;
    var $genre_id;
    var $relevance; // currently the values 1 (for main genre) and 0 (for sub genre). this is used to sort result lists by main genre matches first.

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

    function fetchAllOfProject($project_id) {
        $objs = array();

        $result = _mysql_query(
            'select pg.* ' .
            'from pp_project_genre pg ' .
            'where pg.project_id = ' . n($project_id)
        );

        while ($row = mysql_fetch_array($result)) {
            $pg = new ProjectGenre();
            ProjectGenre::_read_row($pg, $row);

            $objs[] = $pg;
        }

        mysql_free_result($result);

        return $objs;
    }

    function addAll($genreIds, $projectId, $relevance = 0) {
        foreach ($genreIds as $id) {
            if ($id) {
                $pg = new ProjectGenre();
                $pg->project_id = $projectId;
                $pg->genre_id   = $id;
                $pg->relevance  = $relevance;
                $pg->insert();
            }
        }
    }

    function _read_row(&$pg, $row) {
        $pg->project_id = $row['project_id'];
        $pg->genre_id   = $row['genre_id'];
        $pg->relevance  = $row['relevance'];

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
            'relevance  int(1)  not null, ' .
            'primary key (project_id, genre_id), ' .
            'index (project_id), ' .
            'index (genre_id) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function getMainGenreIdForProjectId($project_id) {
        $id = null;

        $result = _mysql_query(
            'select * ' .
            'from pp_project_genre ' .
            'where project_id = ' . n($project_id) . ' ' .
            'and relevance = 1 ' .
            'limit 1'
        );

        if ($row = mysql_fetch_array($result)) {
            $id = $row['genre_id'];
        }

        mysql_free_result($result);

        return $id;
    }

    function getSubGenreIdsForProjectId($project_id) {
        $ids = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_project_genre ' .
            'where project_id = ' . n($project_id) . ' ' .
            'and relevance = 0'
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
            'and pg.genre_id = g.id ' .
            'order by relevance desc, genre_name asc'
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
    function save() {
        if (isset($this->project_id) && isset($this->genre_id)) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    function insert() {
        $ok = _mysql_query(
            'insert into pp_project_genre ' .
            '(project_id, genre_id, relevance) ' .
            'values (' .
            n($this->project_id)    . ', ' .
            n($this->genre_id)      . ', ' .
            n($this->relevance)     .
            ')'
        );

        if (!$ok) {
            return false;
        }

        return $ok;
    }

    function update() {
        $ok = _mysql_query(
            'update pp_project_genre ' .
            'set relevance = '    . n($this->relevance)  . ', ' .
            'where project_id = ' . n($this->project_id) . ' ' .
            'and genre_id = '     . n($this->genre_id)
        );

        return $ok;
    }
}

?>