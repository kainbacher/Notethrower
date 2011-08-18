<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_project_file table
class ProjectFile {
    var $id;
    var $track_id;
    var $filename;
    var $orig_filename;
    var $is_master; // defines if the track file is a master/mix file (there can be more than one)
    var $status;
    var $entry_date;

    // constructors
    // ------------
    function ProjectFile() {
    }

    function fetch_all_for_track_id($tid, $show_inactive_items = false) {
        $objs = array();

        $result = _mysql_query(
            'select * ' .
            'from pp_project_file ' .
            'where track_id = ' . n($tid) . ' ' .
            ($show_inactive_items ? 'and status in ("active", "inactive") ' : 'and status = "active" ') .
            'order by entry_date desc'
        );

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $f = new ProjectFile();
            $f = ProjectFile::_read_row($f, $row);

            $objs[$ind] = $f;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }

    function fetch_for_id($id) {
        $result = _mysql_query(
            'select * ' .
            'from pp_project_file ' .
            'where id = ' . n($id)
        );

        $f = new ProjectFile();

        if ($row = mysql_fetch_array($result)) {
            $f = ProjectFile::_read_row($f, $row);
        }

        mysql_free_result($result);

        return $f;
    }

    function _read_row($f, $row) {
        $f->id                    = $row['id'];
        $f->track_id              = $row['track_id'];
        $f->filename              = $row['filename'];
        $f->orig_filename         = $row['orig_filename'];
        $f->is_master             = $row['is_master'];
        $f->status                = $row['status'];
        $f->entry_date            = reformat_sql_date($row['entry_date']);

        return $f;
    }

    // class functions
    // ---------------
    function create_table() {
        $ok = _mysql_query(
            'create table if not exists pp_project_file ' .
            '(' .
            'id                    int(10)      not null auto_increment, ' .
            'track_id              int(10)      not null, ' .
            'filename              varchar(255) not null, ' .
            'orig_filename         varchar(255) not null, ' .
            'is_master             tinyint(1)   not null, ' .
            'status                varchar(20)  not null, ' .
            'entry_date            datetime     not null default "1970-01-01 00:00:00", ' .
            'primary key (id), ' .
            'key track_id (track_id), ' .
            'key entry_date (entry_date) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function count_all_for_track_id($tid, $count_inactive_items) {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_project_file ' .
            'where track_id = ' . n($tid) . ' ' .
            ($count_inactive_items ? 'and status in ("active", "inactive")' : 'and status = "active"')
        );

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
    }

    function master_mp3_file_found_for_track_id($tid, $count_inactive_items = false) {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_project_file ' .
            'where track_id = ' . n($tid) . ' ' .
            'and orig_filename like "%mp3" ' .
            'and is_master = 1 ' .
            ($count_inactive_items ? 'and status in ("active", "inactive")' : 'and status = "active"')
        );

        $row = mysql_fetch_array($result);
        mysql_free_result($result);

        return $row['cnt'] > 0; // there can only be 1 or 0
    }

    function delete_all_with_track_id($tid) {
        if (!$tid) return;

        $result = _mysql_query(
            'select id from pp_project_file ' .
            'where track_id = ' . n($tid)
        );

        while ($row = mysql_fetch_array($result)) {
            ProjectFile::delete_with_id($row['id']);
        }

        mysql_free_result($result);
    }

    function delete_with_id($id) {
        global $logger;

        if (!$id) return;

        // delete file from filesystem
        $result = _mysql_query(
            'select filename ' .
            'from pp_project_file ' .
            'where id = ' . n($id)
        );

        $f = null;
        if ($row = mysql_fetch_array($result)) {
            $f = $row['filename'];
        }

        mysql_free_result($result);

        if ($f) {
            $logger->info('deleting track file: ' . $GLOBALS['CONTENT_BASE_PATH'] . $f);
            unlink ($GLOBALS['CONTENT_BASE_PATH'] . $f);
        } else {
            $logger->error('unable to get filename for track file id: ' . $id);
        }

        // delete record
        $logger->info('deleting track file record with id: ' . $id);
        return _mysql_query(
            'delete from pp_project_file ' .
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
            'insert into pp_project_file ' .
            '(track_id, filename, orig_filename, is_master, ' .
            'status, entry_date) ' .
            'values (' .
            n($this->track_id)               . ', ' .
            qq($this->filename)              . ', ' .
            qq($this->orig_filename)         . ', ' .
            b($this->is_master)              . ', ' .
            qq($this->status)                . ', ' .
            'now()'                          .
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
            'update pp_project_file ' .
            'set track_id = '  . n($this->track_id)       . ', ' .
            'filename = '      . qq($this->filename)      . ', ' .
            'orig_filename = ' . qq($this->orig_filename) . ', ' .
            'is_master = '     . qq($this->is_master)     . ', ' .
            'status = '        . qq($this->status)        . ' ' .
            // entry_date intentionally not set here
            'where id = '      . n($this->id)
        );

        return $ok;
    }
}

?>