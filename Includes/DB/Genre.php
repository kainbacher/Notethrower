<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_genre table
class Genre {
    var $id;
    var $name;

    // constructors
    // ------------
    function Genre() {
    }

    function fetchAll() {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_genre ' .
            'order by name asc'
        );

        while ($row = mysql_fetch_array($result)) {
            $g = new Genre();
            Genre::_read_row($g, $row);

            $objs[$g->id] = $g;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchForId($id) {
        $result = _mysql_query(
            'select * ' .
            'from pp_genre ' .
            'where id = ' . n($id)
        );

        $g = null;

        if ($row = mysql_fetch_array($result)) {
            $g = new Genre();
            Genre::_read_row($g, $row);
        }

        mysql_free_result($result);

        return $g;
    }

    function fetchForName($name) {
        $result = _mysql_query(
            'select * ' .
            'from pp_genre ' .
            'where name = ' . qq($name)
        );

        $g = null;

        if ($row = mysql_fetch_array($result)) {
            $g = new Genre();
            Genre::_read_row($g, $row);
        }

        mysql_free_result($result);

        return $g;
    }

    function _read_row(&$g, $row) {
        $g->id   = $row['id'];
        $g->name = $row['name'];
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_genre ' .
            '(' .
            'id                        int(10)      not null auto_increment, ' .
            'name                      varchar(255) not null, ' .
            'primary key (id) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function populateTable() {
        $existingGenres = Genre::fetchAll();
        if (count($existingGenres) == 0) {
            $initialGenres = array(
                'Pop',
                'Rock',
                'Punk',
                'Country',
                'Electronic',
                'Blues',
                'Hip-Hop',
                'Jazz',
                'Alternative',
                'Singer/Songwriter',
                'Instrumental',
                'Beats',
                'Experimental',
                'Samples or libraries'
            );

            foreach ($initialGenres as $ig) {
                if ($ig) {
                    $g = new Genre();
                    $g->name = $ig;
                    $g->insert();
                }
            }
        }
    }

    function countAll() {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_genre'
        );

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
    }

    function chooseRandomGenreName() {
        $result = _mysql_query(
            'select name ' .
            'from pp_genre ' .
            'order by rand() ' .
            'limit 1'
        );

        $row = mysql_fetch_array($result);
        $genre = $row['name'];
        mysql_free_result($result);

        return $genre;
    }

    function isValidGenre($genre) {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_genre ' .
            'where name = ' . qq($genre)
        );

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return ($count == 1);
    }

    function getSelectorOptionsArray($includeEmptyOption = false) {
        $genres = array();

        if ($includeEmptyOption) {
            $genres[''] = '';
        }

        $all = Genre::fetchAll();
        foreach ($all as $g) {
            $genres[$g->id] = $g->name;
        }
        return $genres;
    }

    // object methods
    // --------------
    function save() {
        if (isset($this->id)) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    function insert() {
        $ok = _mysql_query(
            'insert into pp_genre ' .
            '(name) ' .
            'values (' .
            qq($this->name) .
            ')'
        );

        if (!$ok) {
            return false;
        }

        $this->id = mysql_insert_id();

        return $ok;
    }

    function update() {
        $ok = _mysql_query(
            'update pp_genre ' .
            'set name = ' . qq($this->name) . ' ' .
            'where id = ' . n($this->id)
        );

        return $ok;
    }
}

?>