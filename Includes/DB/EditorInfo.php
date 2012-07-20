<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// DAO for pp_editor_info table
class EditorInfo {
    var $textId;
    var $html;

    // constructors
    // ------------
    public function EditorInfo() {
    }

    public static function fetchForId($id) {
        $result = _mysql_query(
            'select * ' .
            'from pp_editor_info ' .
            'where text_id = ' . qq($id)
        );

        $obj = null;

        if ($row = mysql_fetch_array($result)) {
            $obj = new self();
            self::readRow($obj, $row);
        }

        mysql_free_result($result);

        return $obj;
    }

    private static function readRow(&$obj, $row) {
        $obj->textId = $row['text_id'];
        $obj->html   = $row['html'];
    }

    // static class functions
    // ---------------
    public static function createTable() {
        _mysql_query(
            'create table if not exists pp_editor_info ' .
            '(' .
            'text_id     varchar(30)  not null, ' .
            'html        mediumtext   null, ' .
            'primary key (text_id)' .
            ') DEFAULT CHARSET=utf8'
        );
    }
    
    public static function createDefaultObj() {
        $obj = new self();
        
        return $obj;
    }

    public static function deleteWithId($id) {
        if (!$id) return;

        _mysql_query(
            'delete from pp_editor_info ' .
            'where text_id = ' . qq($id)
        );
    }

    // object methods
    // --------------
    public function insert() {
        _mysql_query(
            'insert into pp_editor_info ' .
            '(text_id, html) ' .
            'values (' .
            qq($this->textId)      . ', ' .
            qq($this->html)        . 
            ')'
        );
    }

    public function update() {
        _mysql_query(
            'update pp_editor_info set ' .
            'html = '          . qq($this->html)   . ' ' .
            'where text_id = ' . qq($this->textId)
        );
    }
}

?>