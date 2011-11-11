<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_user_genre table
class UserGenre {
    var $user_id;
    var $genre_id;

    // non-table fields
    var $genre_name; // currently not read/not used

    // constructors
    // ------------
    function UserGenre() {
    }

    function fetchForUserIdGenreId($user_id, $genre_id) {
        $result = _mysql_query(
            'select * ' .
            'from pp_user_genre ' .
            'where user_id = ' . n($user_id) . ' ' .
            'and genre_id = ' . n($genre_id)
        );

        $ug = null;

        if ($row = mysql_fetch_array($result)) {
            $ug = new UserGenre();
            UserGenre::_read_row($ug, $row);
        }

        mysql_free_result($result);

        return $ug;
    }

    function fetchAllForGenreId($genre_id) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_user_genre ' .
            'where genre_id = ' . n($genre_id)
        );

        while ($row = mysql_fetch_array($result)) {
            $ug = new UserGenre();
            UserGenre::_read_row($ug, $row);

            $objs[] = $ug;
        }

        mysql_free_result($result);

        return $objs;
    }

    function addAll($genreIds, $userId) {
        foreach ($genreIds as $id) {
            if ($id) {
                $ug = new UserGenre();
                $ug->user_id  = $userId;
                $ug->genre_id = $id;
                $ug->insert();
            }
        }
    }

    function _read_row(&$a, $row) {
        $a->user_id  = $row['user_id'];
        $a->genre_id = $row['genre_id'];

        // non-table fields
        $a->genre_name = $row['genre_name'];

        return $a;
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_user_genre ' .
            '(' .
            'user_id  int(10) not null, ' .
            'genre_id int(10) not null, ' .
            'primary key (user_id, genre_id), ' .
            'index (user_id), ' .
            'index (genre_id) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function getGenreIdsForUserId($user_id) {
        $ids = array();

        $result = _mysql_query(
            'select genre_id ' .
            'from pp_user_genre ' .
            'where user_id = ' . n($user_id)
        );

        while ($row = mysql_fetch_array($result)) {
            $ids[] = $row['genre_id'];
        }

        mysql_free_result($result);

        return $ids;
    }

    function getGenreNamesForUserId($user_id) {
        $names = array();

        $result = _mysql_query(
            'select g.name as genre_name ' .
            'from pp_user_genre ug, pp_genre g ' .
            'where ug.user_id = ' . n($user_id) . ' ' .
            'and ug.genre_id = g.id'
        );

        while ($row = mysql_fetch_array($result)) {
            $names[] = $row['genre_name'];
        }

        mysql_free_result($result);

        return $names;
    }

    function deleteForUserId($userId) {
        return _mysql_query(
            'delete from pp_user_genre ' .
            'where user_id = ' . n($userId)
        );
    }

    // object methods
    // --------------
    function insert() {
        $ok = _mysql_query(
            'insert into pp_user_genre ' .
            '(user_id, genre_id) ' .
            'values (' .
            n($this->user_id)  . ', ' .
            n($this->genre_id) .
            ')'
        );

        if (!$ok) {
            return false;
        }

        return $ok;
    }
}

?>