<?php

include_once('../Includes/Config.php');
include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

class Subscription {
     
    var $username;
    var $email_address;
    var $rand_str;
    var $referrer_id;
    
    function fetch_for_rand_str($rand_str) {
        
        $result = _mysql_query(
            'select * ' .
            'from pp_subscription ' .
            'where rand_str = ' . qq($rand_str)
        );

        if ($row = mysql_fetch_array($result)) {
            $a = new Subscription();
            $a = Subscription::_read_row($a, $row);
            mysql_free_result($result);

            return $a;

        } else {
            mysql_free_result($result);
            return null;
        }
    }
    
    function fetch_for_username($username) {
        
        $result = _mysql_query(
            'select * ' .
            'from pp_subscription ' .
            'where username = ' . qq($username)
        );
        

        if ($row = mysql_fetch_array($result)) {
            $a = new Subscription();
            $a = Subscription::_read_row($a, $row);
            mysql_free_result($result);

            return $a;

        } else {
            mysql_free_result($result);
            return null;
        }
    }
    
     function fetch_for_email($email) {
        
        $result = _mysql_query(
            'select * ' .
            'from pp_subscription ' .
            'where email_address = ' . qq($email)
        );
        

        if ($row = mysql_fetch_array($result)) {
            $a = new Subscription();
            $a = Subscription::_read_row($a, $row);
            mysql_free_result($result);

            return $a;

        } else {
            mysql_free_result($result);
            return null;
        }
    }
    
    function fetch_notethrower_artists(){
        $ok = _mysql_query(
            'SELECT * FROM pp_subscription ' .
            'WHERE old_artist_id > 0' 
            );
        while($row = mysql_fetch_array($ok)){
            $result[] = array(
                'id' => $row['id'],
                'username' => $row['username'],
                'email_address' => $row['email_address'],
                'rand_str' => $row['rand_str']   
            );
        }
        
        return $result;
    } 
    
    function insert() {
        $ok = _mysql_query(
            'insert into pp_subscription ' .
            '(username, email_address, rand_str, referrer_id, entry_date) ' .
            'values (' .
            qq($this->username)        . ', ' .
            qq($this->email_address)   . ', ' .
            qq($this->rand_str)       . ', ' .
            n($this->referrer_id)      . ', ' .
            'now()'                    .
            ')'
        );

        if (!$ok) {
            return false;
        }

        $this->id = mysql_insert_id();

        return $ok;
    }


    
    function _read_row($a, $row) {
        $a->id              = $row['id'];
        $a->username        = $row['username'];
        $a->email_address   = $row['email_address'];
        $a->rand_str        = $row['rand_str'];
        $a->referrer_id     = $row['referrer_id'];
        $a->entry_date      = reformat_sql_date($row['entry_date']);

        return $a;
    }
    
    
     function create_table() {
        $ok = _mysql_query(
            'create table if not exists pp_subscription ' .
            '(' .
            'id              int(10)      not null auto_increment, ' .
            'username        varchar(50)  not null, ' .
            'email_address   varchar(255) not null, ' .
            'rand_str        varchar(10)  not null, ' .
            'referrer_id     int(10), ' .
            'entry_date      datetime     not null default "1970-01-01 00:00:00", ' .
            'old_artist_id   int(10)      not null default "0", ' .
            'primary key (id) ' .
           ') default charset=utf8'
        );
    return $ok;
    }
}
