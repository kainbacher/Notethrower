<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_attribute table
class Attribute {
    var $id;
    var $name;
    var $entry_date;
    var $shown_for; // valid values are "contains" (also means "provides" or "offers" in the user/attribute context), "needs" and "both"

    // constructors
    // ------------
    function Attribute() {
    }

    function fetchAll() {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_attribute ' .
            'order by name asc '
        );

        while ($row = mysql_fetch_array($result)) {
            $f = new Attribute();
            $f = Attribute::_read_row($f, $row);

            $objs[$f->id] = $f;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchShownFor($shownFor) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_attribute ' .
            'where shown_for = ' . qq($shownFor) . ' or shown_for = "both" ' .
            'order by name asc'
        );

        while ($row = mysql_fetch_array($result)) {
            $f = new Attribute();
            $f = Attribute::_read_row($f, $row);

            $objs[] = $f;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetchForId($id) {
        $result = _mysql_query(
            'select * ' .
            'from pp_attribute ' .
            'where id = ' . n($id)
        );

        $a = new Attribute();

        if ($row = mysql_fetch_array($result)) {
            $a = Attribute::_read_row($a, $row);
        }

        mysql_free_result($result);

        return $a;
    }

    function fetchForName($name) {
        $result = _mysql_query(
            'select * ' .
            'from pp_attribute ' .
            'where name = ' . qq($name)
        );

        $a = new Attribute();

        if ($row = mysql_fetch_array($result)) {
            $a = Attribute::_read_row($a, $row);
        }

        mysql_free_result($result);

        return $a;
    }

    function _read_row($a, $row) {
        $a->id           = $row['id'];
        $a->name         = $row['name'];
        $a->shown_for    = $row['shown_for'];
        $a->entry_date   = reformat_sql_date($row['entry_date']);

        return $a;
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_attribute ' .
            '(' .
            'id                        int(10)      not null auto_increment, ' .
            'name                      varchar(255) not null, ' .
            'shown_for                 varchar(20) not null, ' .
            'entry_date                datetime     not null default "1970-01-01 00:00:00", ' .
            'primary key (id), ' .
            'key entry_date (entry_date) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function getIdNameMapShownFor($shownFor) {
        $map = array();

        $result = _mysql_query(
            'select id, name ' .
            'from pp_attribute ' .
            'where shown_for = ' . qq($shownFor) . ' or shown_for = "both" ' .
            'order by name asc'
        );

        while ($row = mysql_fetch_array($result)) {
            $map[$row['id']] = $row['name'];
        }

        mysql_free_result($result);

        return $map;
    }

    function populateTable() {
        $existingAttrs = Attribute::fetchAll();
        if (count($existingAttrs) == 0) {
            $a = new Attribute();
            $a->name = 'Acoustic Guitar';
            $a->shown_for = 'both';
            $a->save();
            $a = new Attribute();
            $a->name = 'Bass';
            $a->shown_for = 'both';
            $a->save();
            $a = new Attribute();
            $a->name = 'Drums';
            $a->shown_for = 'both';
            $a->save();
            $a = new Attribute();
            $a->name = 'Electric Guitar';
            $a->shown_for = 'both';
            $a->save();
            $a = new Attribute();
            $a->name = 'Horns';
            $a->shown_for = 'both';
            $a->save();
            $a = new Attribute();
            $a->name = 'Keyboard';
            $a->shown_for = 'both';
            $a->save();
            $a = new Attribute();
            $a->name = 'Midi';
            $a->shown_for = 'both';
            $a->save();
            $a = new Attribute();
            $a->name = 'Piano';
            $a->shown_for = 'both';
            $a->save();
            $a = new Attribute();
            $a->name = 'Synth';
            $a->shown_for = 'both';
            $a->save();
            $a = new Attribute();
            $a->name = 'Vinyl';
            $a->shown_for = 'both';
            $a->save();
            $a = new Attribute();
            $a->name = 'Vocals';
            $a->shown_for = 'both';
            $a->save();
            $a = new Attribute();
            $a->name = 'surprise me';
            $a->shown_for = 'needs';
            $a->save();
        }
    }

    function countAll() {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_attribute'
        );

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
    }

    function countAllShownFor($shownFor) {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_attribute where shown_for = ' . qq($shownFor) . ' or shown_for = "both" '
        );

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
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
            'insert into pp_attribute ' .
            '(name, shown_for, entry_date) ' .
            'values (' .
            qq($this->name)      . ', ' .
            qq($this->shown_for) . ' , ' .
            'now()'          .
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
            'update pp_attribute ' .
            'set name = ' . qq($this->name) . ', ' .
            'shown_for = ' . qq($this->shown_for) . ' ' .
            'where id = ' . n($this->id)
        );

        return $ok;
    }
}

?>