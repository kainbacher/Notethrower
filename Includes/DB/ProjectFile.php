<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_project_file table
class ProjectFile {
    var $id;
    var $project_id;
    var $originator_user_id; // this is null when the file was uploaded by the project owner, otherwise it references the collaborating user who uploaded the file
    var $filename;
    var $orig_filename;
    var $type; // raw, mix, release
    var $status; // inactive or active
    var $comment;
    var $entry_date;
    var $autocreated;

    // fields from referenced tables

    // constructors
    // ------------
    function ProjectFile() {
    }

    function fetch_all_for_project_id($tid, $show_inactive_items = false) {
        $objs = array();

        $result = _mysql_query(
            'select pf.* ' .
            'from pp_project_file pf ' .
            'where pf.project_id = ' . n($tid) . ' ' .
            ($show_inactive_items ? 'and pf.status in ("active", "inactive") ' : 'and pf.status = "active" ') .
            'order by pf.entry_date desc'
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

    function fetch_all_for_user_id_and_type($uid, $type) {
        $objs = array();

        $result = _mysql_query(
            'select pf.* ' .
            'from pp_project_file pf, pp_project p ' .
            'where pf.project_id = p.id ' .
            'and p.user_id = ' . n($uid) . ' ' .
            'and p.status = "active" ' .
            'and pf.status = "active" ' .
            'and pf.type = ' . qq($type) . ' ' .
            'order by pf.entry_date desc'
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
        $f->project_id            = $row['project_id'];
        $f->originator_user_id    = $row['originator_user_id'];
        $f->filename              = $row['filename'];
        $f->orig_filename         = $row['orig_filename'];
        $f->type                  = $row['type'];
        $f->status                = $row['status'];
        $f->comment               = $row['comment'];
        $f->entry_date            = reformat_sql_date($row['entry_date']);
        $f->autocreated           = $row['autocreated'];

        // fields from referenced tables

        return $f;
    }

    // class functions
    // ---------------
    function create_table() {
        $ok = _mysql_query(
            'create table if not exists pp_project_file ' .
            '(' .
            'id                    int(10)      not null auto_increment, ' .
            'project_id            int(10)      not null, ' .
            'originator_user_id    int(10), ' .
            'filename              varchar(255) not null, ' .
            'orig_filename         varchar(255) not null, ' .
            'type                  varchar(10)  not null, ' .
            'status                varchar(20)  not null, ' .
            'comment               text, ' .
            'entry_date            datetime     not null default "1970-01-01 00:00:00", ' .
            'autocreated           tinyint(1)   not null default 0, ' .
            'primary key (id), ' .
            'key project_id (project_id), ' .
            'key entry_date (entry_date) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function count_all_for_project_id($tid, $count_inactive_items) {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_project_file ' .
            'where project_id = ' . n($tid) . ' ' .
            ($count_inactive_items ? 'and status in ("active", "inactive")' : 'and status = "active"')
        );

        $row = mysql_fetch_array($result);
        $count = $row['cnt'];
        mysql_free_result($result);

        return $count;
    }

    function master_mp3_file_found_for_project_id($tid, $count_inactive_items = false) {
        $result = _mysql_query(
            'select count(*) as cnt ' .
            'from pp_project_file ' .
            'where project_id = ' . n($tid) . ' ' .
            'and type = "mix" ' .
            ($count_inactive_items ? 'and status in ("active", "inactive")' : 'and status = "active"')
        );

        $row = mysql_fetch_array($result);
        mysql_free_result($result);

        return $row['cnt'] > 0; // there can only be 1 or 0
    }

    function delete_all_with_project_id($tid) {
        if (!$tid) return;

        $result = _mysql_query(
            'select id ' .
            'from pp_project_file ' .
            'where project_id = ' . n($tid)
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
            $file = $GLOBALS['CONTENT_BASE_PATH'] . $f;
            $logger->info('deleting project file: ' . $file);
            $ok = @unlink($file); // suppress errors because this negatively influences ajax/json communication
            if (!$ok) {
                $logger->error('failed to delete file: ' . $file);
            }

        } else {
            $logger->error('unable to get filename for project file id: ' . $id);
        }

        // delete record
        $logger->info('deleting project file record with id: ' . $id);
        return _mysql_query(
            'delete from pp_project_file ' .
            'where id = ' . n($id)
        );
    }

    function getFilepathsForProjectFileIds($pfids) {
        if (count($pfids) == 0) return array();

        $result = _mysql_query(
            'select id, filename, orig_filename ' .
            'from pp_project_file ' .
            'where id in (' . implode(',', $pfids) . ')'
        );

        $data = array();
        while ($row = mysql_fetch_array($result)) {
            $data[] = array(
                'id'           => $row['id'],
                'origFilename' => $row['orig_filename'],
                'path'         => $GLOBALS['CONTENT_BASE_PATH'] . $row['filename']
            );
        }

        mysql_free_result($result);

        return $data;
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
            '(project_id, originator_user_id, filename, orig_filename, type, status, comment, entry_date, autocreated) ' .
            'values (' .
            n($this->project_id)             . ', ' .
            n($this->originator_user_id)     . ', ' .
            qq($this->filename)              . ', ' .
            qq($this->orig_filename)         . ', ' .
            qq($this->type)                  . ', ' .
            qq($this->status)                . ', ' .
            qq($this->comment)               . ', ' .
            b($this->autocreated)            . ', ' .
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
            'set project_id = '     . n($this->project_id)         . ', ' .
            'originator_user_id = ' . n($this->originator_user_id) . ', ' .
            'filename = '           . qq($this->filename)          . ', ' .
            'orig_filename = '      . qq($this->orig_filename)     . ', ' .
            'type = '               . qq($this->type)              . ', ' .
            'status = '             . qq($this->status)            . ', ' .
            'comment = '            . qq($this->comment)           . ', ' .
            'autocreated = '        . b($this->autocreated)        . ' ' .
            // entry_date intentionally not set here
            'where id = '           . n($this->id)
        );

        return $ok;
    }
}

?>