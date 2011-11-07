<?php

include_once('../Includes/Config.php');
include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_transcoding_job table
class TranscodingJob {
    var $id;
    var $projectFileId;
    var $status; // PENDING; PROCESSING; SUCCEES; FAILED;
    var $entryDate;
    var $updatedDate;
    var $info;

    //no db table fields
    var $projectId;
    var $filename;

    // constructors
    // ------------
    function TranscodingJob() {
    }

    function fetchPending() {
        $objs = array();

        $result = null;
        $result = _mysql_query('select j.id, j.project_file_id, j.status, j.info, j.entry_date, ' .
                               'j.updated_date, f.filename ' .
                               'from pp_transcoding_job j left join pp_project_file f on f.id=j.project_file_id ' .
                               'where j.status="PENDING"');

        $ind = 0;

        while ($row = mysql_fetch_array($result)) {
            $a = new TranscodingJob();
            $a = TranscodingJob::_read_row($a, $row);

            $objs[$ind] = $a;
            $ind++;
        }

        mysql_free_result($result);

        return $objs;
    }
    
    function fetchRandomPendingJob() {
        $result = null;
        $result = _mysql_query('select j.id, j.project_file_id, j.status, j.info, j.entry_date, ' .
                               'j.updated_date, f.filename, f.project_id ' .
                               'from pp_transcoding_job j left join pp_project_file f on f.id=j.project_file_id ' .
                               'where j.status="PENDING" LIMIT 0,1');
        
        $row = mysql_fetch_array($result);
        $a = null;
        if ($row) {
            $a = new TranscodingJob();
            $a = TranscodingJob::_read_row($a, $row);
        }
        mysql_free_result($result);

        return $a;
    }
  
    function _read_row($a, $row) {
        $a->id               = $row['id'];
        $a->status           = $row['status'];
        $a->projectFileId    = $row['project_file_id'];
        $a->info             = $row['info'];
        $a->entryDate        = reformat_sql_date($row['entry_date']);
        $a->updateDate       = reformat_sql_date($row['updated_date']);
        $a->projectId        = $row['project_id'];
        $a->filename         = $row['filename'];
        return $a;
    }

    // class functions
    // ---------------
    function createTable() {
        $ok = _mysql_query(
            'create table if not exists pp_transcoding_job ' .
            '(' .
            'id                        int(10)      not null auto_increment, ' .
            'project_file_id           int(5)       not null, ' .
            'status                    varchar(20)  not null, ' .
            'entry_date                datetime     not null default "1970-01-01 00:00:00", ' .
            'updated_date              datetime             ,' .
            'info                      varchar(255)         ,' .
            'primary key (id), ' .
            'key status (status), ' .
            'key entry_date (entry_date) ' .
            ') default charset=utf8'
        );

        return $ok;
    }

    function save() {
        if (isset($this->id)) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    function insert() {
        $ok = _mysql_query(
            'insert into pp_transcoding_job ' .
            '(project_file_id, status, entry_date, updated_date, info)' .
            'values (' .
            n($this->projectFileId)           . ', ' .
            qq($this->status)                 . ', ' .
            'now()'                           . ', ' .
            'now()'                           . ', ' .
            qq($this->info)                   .
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
            'update pp_transcoding_job ' .
            'set project_file_id='         . n($this->projectFileId)            . ', ' .
            'status = '                     . qq($this->status)                     . ', ' .
            'updated_date = '              . 'now()'                              . ', ' .
            'info = '                      . qq($this->info)                      . ' ' .
            'where id = '                  . n($this->id)
        );
        return $ok;
    }
    
    function updateStatusBasedOnOldStatus($oldStatus) {
        $ok = _mysql_query(
            'update pp_transcoding_job ' .
            'set status = '    . qq($this->status) . ', ' .
            'updated_date = '  . 'now()'           . ' ' .
            'where id=' . n($this->id) . ' and status=' . qq($oldStatus)
        );
        $affectedRows = mysql_affected_rows();
        $resp = array('ok'=>$ok, 'affectedRows'=>$affectedRows);
        return $resp;
    }
}

?>