<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_stats table
class Stats {
    var $artist_id;
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
            'artist_id  int(10)     not null, ' .
            'ip         varchar(15) not null, ' .
            'entry_date datetime    not null default "1970-01-01 00:00:00", ' .
            'index (artist_id), ' .
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
            '(artist_id, ip, entry_date) ' .
            'values ('                     .
            qq($this->artist_id)           . ', ' .
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