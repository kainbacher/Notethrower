<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_vote table
class Vote {
    var $userid;
    var $pfid;
    var $entry_date;

    // constructors
    // ------------
    function Vote() {
    }

    function _read_row($v, $row) {
        $v->userid       = $row['userid'];
        $v->pfid         = $row['pfid'];
        $v->entry_date   = reformat_sql_date($row['entry_date']);

        return $v;
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_vote ' .
            '(' .
            'userid                    int(10)      not null, ' .
            'pfid                      int(10)      not null, ' .
            'entry_date                datetime     not null default "1970-01-01 00:00:00", ' .
            'primary key (userid, pfid)' .
            ')'
        );

        return $ok;
    }

    function countAllForUserIdAndPfid($userid, $pfid) {
        $cnt = 0;

        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_vote ' .
            'where userid = ' . n($userid) . ' ' .
            'and pfid = ' . n($pfid)
        );

        if ($row = mysql_fetch_array($result)) {
            $cnt = $row['cnt'];
        }

        mysql_free_result($result);

        return $cnt;
    }

    // object methods
    // --------------
    function insert() {
        $ok = _mysql_query(
            'insert into pp_vote ' .
            '(userid, pfid) ' .
            'values (' .
            n($this->userid) . ', ' .
            n($this->pfid)   . ' , ' .
            'now()'          .
            ')'
        );

        return $ok;
    }
}

?>
