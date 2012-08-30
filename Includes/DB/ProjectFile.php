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
    var $hot_count; // for hot (+1) votings of logged-in users
    var $not_count; // for not (-1) votings of logged-in users
    var $hot_count_anon; // for hot (+1) votings of anonymous users
    var $not_count_anon; // for not (-1) votings of anonymous users
    var $hot_count_pro; // for hot (+1) votings of pro users
    var $not_count_pro; // for not (-1) votings of pro users
    var $comment;
    var $release_title;
    var $release_date;
    var $entry_date;
    var $autocreated_from;

    // fields from referenced tables
    var $originator_user_name;

    // constructors
    // ------------
    function ProjectFile() {
    }

    function fetch_all_for_project_id($tid, $show_inactive_items = false) {
        $objs = array();

        $result = _mysql_query(
            'select pf.*, u.name as originator_user_name ' .
            'from pp_project_file pf ' .
            'left join pp_user u on pf.originator_user_id = u.id ' .
            'where pf.project_id = ' . n($tid) . ' ' .
            ($show_inactive_items ? 'and pf.status in ("active", "inactive") ' : 'and pf.status = "active" ') .
            'order by pf.entry_date desc, pf.autocreated_from asc' // ATTENTION: never change the ordering here without checking the effects on the the display of project file lists with regards to autocreated files, etc.!
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
    
    function fetch_all_for_project_id_and_type($tid, $type, $show_autocreated_siblings = false, $show_inactive_items = false) {
        $objs = array();

        $result = _mysql_query(
            'select pf.*, u.name as originator_user_name ' .
            'from pp_project_file pf ' .
            'left join pp_user u on pf.originator_user_id = u.id ' .
            'where pf.project_id = ' . n($tid) . ' ' .
            ($show_inactive_items ? 'and pf.status in ("active", "inactive") ' : 'and pf.status = "active" ') .
            ($show_autocreated_siblings ? '' : 'and pf.autocreated_from is null ') .
            'and pf.type = ' . qq($type) . ' ' .
            'order by pf.entry_date desc, pf.autocreated_from asc' // ATTENTION: never change the ordering here without checking the effects on the the display of project file lists with regards to autocreated files, etc.!
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

    function fetch_all_releases_ordered_by_rating() {
        $objs = array();

        $result = _mysql_query(
            //'select * from (' .
                'select pf.*, pf.hot_count + pf.not_count as total_votes, pf.hot_count_anon + pf.not_count_anon as total_votes_anon, pf.hot_count_pro + pf.not_count_pro as total_votes_pro, ' .
                'pf.hot_count + pf.hot_count_anon * 0.5 + pf.hot_count_pro * 2 - pf.not_count - pf.not_count_anon * 0.5 - pf.not_count_pro * 2 as rating ' .
                'from pp_project_file pf, pp_project p ' .
                'where pf.project_id = p.id ' .
                'and p.status = "active" ' .
                'and pf.status = "active" ' .
                'and pf.type = "release" ' .
                'order by rating desc, hot_count desc, not_count asc' 
            //') as tmp_table ' .
            //'where hot_count / total_votes >= 0.5' // only list released tracks with at least 50% hot votes            // FIXME - do something like this for proudloudr charts
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
            'select pf.*, u.name as originator_user_name ' .
            'from pp_project_file pf ' .
            'left join pp_user u on pf.originator_user_id = u.id ' .
            'where pf.id = ' . n($id)            
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
        $f->hot_count             = $row['hot_count'];
        $f->not_count             = $row['not_count'];
        $f->hot_count_anon        = $row['hot_count_anon'];
        $f->not_count_anon        = $row['not_count_anon'];
        $f->hot_count_pro         = $row['hot_count_pro'];
        $f->not_count_pro         = $row['not_count_pro'];
        $f->comment               = $row['comment'];
        $f->release_title         = $row['release_title'];
        $f->release_date          = $row['release_date'];
        $f->entry_date            = $row['entry_date'];
        $f->autocreated_from      = $row['autocreated_from'];

        // fields from referenced tables
        $f->originator_user_name = $row['originator_user_name'];

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
            'hot_count             int(10)      not null default 0, ' .
            'not_count             int(10)      not null default 0, ' .
            'hot_count_anon        int(10)      not null default 0, ' .
            'not_count_anon        int(10)      not null default 0, ' .
            'hot_count_pro         int(10)      not null default 0, ' .
            'not_count_pro         int(10)      not null default 0, ' .
            'comment               text, ' .
            'release_title         varchar(255), ' .
            'release_date          datetime, ' .
            'entry_date            datetime     not null default "1970-01-01 00:00:00", ' .
            'autocreated_from      int(10), ' .
            'primary key (id), ' .
            'key project_id (project_id), ' .
            'key entry_date (entry_date), ' .
            'key autocreated_from (autocreated_from) ' .
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

        // first, delete the autocreated sibling (if one is there)
        ProjectFile::delete_with_autocreated_from($id);

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

    function delete_with_autocreated_from($id) {
        global $logger;

        if (!$id) return;

        // delete file from filesystem
        $result = _mysql_query(
            'select filename ' .
            'from pp_project_file ' .
            'where autocreated_from = ' . n($id)
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
        $logger->info('deleting project file record with autocreated_from: ' . $id);
        return _mysql_query(
            'delete from pp_project_file ' .
            'where autocreated_from = ' . n($id)
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
            '(project_id, originator_user_id, filename, orig_filename, type, status, hot_count, not_count, hot_count_anon, not_count_anon, hot_count_pro, not_count_pro, comment, release_title, ' .
            'release_date, entry_date, autocreated_from) ' .
            'values (' .
            n($this->project_id)             . ', ' .
            n($this->originator_user_id)     . ', ' .
            qq($this->filename)              . ', ' .
            qq($this->orig_filename)         . ', ' .
            qq($this->type)                  . ', ' .
            qq($this->status)                . ', ' .
            n($this->hot_count)              . ', ' .
            n($this->not_count)              . ', ' .
            n($this->hot_count_anon)         . ', ' .
            n($this->not_count_anon)         . ', ' .
            n($this->hot_count_pro)          . ', ' .
            n($this->not_count_pro)          . ', ' .
            qq($this->comment)               . ', ' .
            qq($this->release_title)         . ', ' .
            qq($this->release_date)          . ', ' .
            ($this->entry_date ? qq($this->entry_date) : qq(formatMysqlDatetime())) . ', ' .
            n($this->autocreated_from)       .
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
            'hot_count = '          . n($this->hot_count)          . ', ' .
            'not_count = '          . n($this->not_count)          . ', ' .
            'hot_count_anon = '     . n($this->hot_count_anon)     . ', ' .
            'not_count_anon = '     . n($this->not_count_anon)     . ', ' .
            'hot_count_pro = '      . n($this->hot_count_pro)      . ', ' .
            'not_count_pro = '      . n($this->not_count_pro)      . ', ' .
            'comment = '            . qq($this->comment)           . ', ' .
            'release_title = '      . qq($this->release_title)     . ', ' .
            'release_date = '       . qq($this->release_date)      . ', ' .
            'autocreated_from = '   . n($this->autocreated_from)   . ' ' .
            // entry_date intentionally not set here
            'where id = '           . n($this->id)
        );

        return $ok;
    }
}

?>
