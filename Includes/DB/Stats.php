<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_stats table
class Stats {
    var $user_id;
    var $ip;
    var $entry_date;

    // constructors
    // ------------
    function Stats() {
    }

    // class functions
    // ---------------
    function create_table() {
        $ok = _mysql_query(
            'create table if not exists pp_stats ' .
            '(' .
            'user_id  int(10)     not null, ' .
            'ip         varchar(15) not null, ' .
            'entry_date datetime    not null default "1970-01-01 00:00:00", ' .
            'index (user_id), ' .
            'index (entry_date) ' .
            ')'
        );

        return $ok;
    }

    // object methods
    // --------------
    function insert() {
        $ok = _mysql_query(
            'insert into pp_stats ' .
            '(user_id, ip, entry_date) ' .
            'values ('                     .
            qq($this->user_id)           . ', ' .
            qq($this->ip)                  . ', ' .
            'now()'                        .
            ')'
        );

        if (!$ok) {
            return false;
        }

        $this->id = mysql_insert_id();

        return $ok;
    }
}

?>