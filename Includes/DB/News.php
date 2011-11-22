<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_news table
class News {
    var $id;
    var $html;
    var $headline;
    var $entry_date;

    // constructors
    // ------------
    function News() {
    }

    function fetch_all() {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_news order by entry_date desc '
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $f = new News();
            $f = News::_read_row($f, $row);

            $objs[$ind] = $f;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_for_id($id) {
        $result = _mysql_query(
            'select * ' .
            'from pp_news ' .
            'where id = ' . n($id)
        );

        if ($row = mysql_fetch_array($result)) {
            $a = new News();
            $a = News::_read_row($a, $row);
            mysql_free_result($result);
            return $a;

        } else {
            mysql_free_result($result);
            return null;
        }
    }

    function fetch_with_limit($limit) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_news order by entry_date desc ' .
            'limit ' . n($limit)
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $f = new News();
            $f = News::_read_row($f, $row);

            $objs[$ind] = $f;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_newest_from_to($from, $to) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_news order by entry_date desc ' .
            'limit ' . $from . ', ' . ($to - $from + 1)
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $f = new News();
            $f = News::_read_row($f, $row);

            $objs[$ind] = $f;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }



    function _read_row($a, $row) {
        $a->id          = $row['id'];
        $a->html        = $row['html'];
        $a->headline    = $row['headline'];
        $a->entry_date  = reformat_sql_date($row['entry_date']);

        return $a;
    }

    // class functions
    // ---------------
    function create_table() {
        $ok = _mysql_query(
            'create table if not exists pp_news ' .
            '(' .
            'id          int(10)      not null auto_increment, ' .
            'html        text         not null, ' .
            'headline    varchar(255) not null, ' .
            'entry_date  datetime     not null default "1970-01-01 00:00:00", ' .
            'primary key (id), ' .
            'key entry_date (entry_date) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function count_all() {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_news '
        );

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
    }

    function delete_with_id($id) {
        if (!$id) return;

        return _mysql_query(
            'delete from pp_news ' .
            'where id = ' . n($id)
        );
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
            'insert into pp_news ' .
            '(html, headline, entry_date) ' .
            'values (' .
            qq($this->html)     . ', ' .
            qq($this->headline) . ', ' .
            qq(formatMysqlDatetime()) .
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
            'update pp_news ' .
            'set headline = ' . qq($this->headline). ', ' .
            'html = '         . qq($this->html)    . ' ' .
            // entry_date intentionally not set here
            'where id = '     . n($this->id)
        );

        return $ok;
    }
}

?>