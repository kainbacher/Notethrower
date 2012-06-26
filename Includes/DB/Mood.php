<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_mood table
class Mood {
    var $id;
    var $name;

    // constructors
    // ------------
    function Mood() {
    }

    function fetchAll() {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_mood ' .
            'order by name asc'
        );

        while ($row = mysql_fetch_array($result)) {
            $g = new Mood();
            Mood::_read_row($g, $row);

            $objs[$g->id] = $g;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchForId($id) {
        $result = _mysql_query(
            'select * ' .
            'from pp_mood ' .
            'where id = ' . n($id)
        );

        $g = null;

        if ($row = mysql_fetch_array($result)) {
            $g = new Mood();
            Mood::_read_row($g, $row);
        }

        mysql_free_result($result);

        return $g;
    }

    function fetchForName($name) {
        $result = _mysql_query(
            'select * ' .
            'from pp_mood ' .
            'where name = ' . qq($name)
        );

        $g = null;

        if ($row = mysql_fetch_array($result)) {
            $g = new Mood();
            Mood::_read_row($g, $row);
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
            'create table if not exists pp_mood ' .
            '(' .
            'id                        int(10)      not null auto_increment, ' .
            'name                      varchar(255) not null, ' .
            'primary key (id) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function populateTable() {
        $existingMoods = Mood::fetchAll();
        if (count($existingMoods) == 0) {
            $initialMoods = array(
                'Down/Dark/Melancholic',
                'Up/Positive',
                'Aggressive/Edgy',
                'Ambient/Spacious',
                'Laid Back/Groovy',
                'Sentimental/Ballad',
                'Quirky/Wacky/Silly',
                'Inspirational',
                'Dramatic'
            );

            foreach ($initialMoods as $ig) {
                if ($ig) {
                    $g = new Mood();
                    $g->name = $ig;
                    $g->insert();
                }
            }
        }
    }

    function countAll() {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_mood'
        );

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
    }

    function getSelectorOptionsArray($includeEmptyOption = false) {
        $moods = array();

        if ($includeEmptyOption) {
            $moods[''] = '';
        }

        $all = Mood::fetchAll();
        foreach ($all as $g) {
            $moods[$g->id] = $g->name;
        }
        return $moods;
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
            'insert into pp_mood ' .
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
            'update pp_mood ' .
            'set name = ' . qq($this->name) . ' ' .
            'where id = ' . n($this->id)
        );

        return $ok;
    }
}

?>
