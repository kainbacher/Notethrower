<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_paypal_tx table
class PayPalTx {
    var $id;
    var $payer;
    var $payer_ip;
    var $receiver;
    var $paypal_tx_id;
    var $item_number;
    var $entry_date;
    var $amount;
    var $currency;
    var $first_name;
    var $last_name;
    var $residence_country;


    // constructors
    // ------------
    function PayPalTx() {
    }

    function fetch_for_id($id) {
        $result = _mysql_query(
            'select * ' .
            'from pp_paypal_tx ' .
            'where id = ' . n($id)
        );

        $p = new PayPalTx();

        if ($row = mysql_fetch_array($result)) {
            $p = PayPalTx::_read_row($p, $row);
        }

        mysql_free_result($result);

        return $p;
    }

    function fetch_for_paypal_tx_id($txid) {
        $result = _mysql_query(
            'select * ' .
            'from pp_paypal_tx ' .
            'where paypal_tx_id = ' . qq($txid)
        );

        $p = new PayPalTx();

        if ($row = mysql_fetch_array($result)) {
            $p = PayPalTx::_read_row($p, $row);
        }

        mysql_free_result($result);

        return $p;
    }

    function _read_row($p, $row) {
        $p->id                = $row['id'];
        $p->payer             = $row['payer'];
        $p->payer_ip          = $row['payer_ip'];
        $p->receiver          = $row['receiver'];
        $p->paypal_tx_id      = $row['paypal_tx_id'];
        $p->item_number       = $row['item_number'];
        $p->entry_date        = reformat_sql_date($row['entry_date']);
        $p->amount            = $row['amount'];
        $p->currency          = $row['currency'];
        $p->first_name        = $row['first_name'];
        $p->last_name         = $row['last_name'];
        $p->residence_country = $row['residence_country'];

        return $p;
    }

    // class functions
    // ---------------
    function create_table() {
        $ok = _mysql_query(
            'create table if not exists pp_paypal_tx ' .
            '(' .
            'id                int(10)      not null auto_increment, ' .
            'payer             varchar(255) not null default "", ' .
            'payer_ip          varchar(15)  not null default "", ' .
            'receiver          varchar(255) not null default "", ' .
            'paypal_tx_id      varchar(255) not null default "", ' .
            'item_number       varchar(255) not null default "", ' .
            'entry_date        datetime     not null default "1970-01-01 00:00:00", ' .
            'amount            float        not null default 0, ' .
            'currency          varchar(10)  not null default "", ' .
            'first_name        varchar(50)  not null default "", ' .
            'last_name         varchar(50)  not null default "", ' .
            'residence_country varchar(10)  not null default "", ' .
            'primary key  (id), ' .
            'key paypal_tx_id (paypal_tx_id) ' .
            ')'
        );

        return $ok;
    }

    // object methods
    // --------------
    function insert() {
        $ok = _mysql_query(
            'insert into pp_paypal_tx ' .
            '(payer, payer_ip, receiver, paypal_tx_id, item_number, ' .
            'entry_date, amount, currency, first_name, last_name, residence_country) ' .
            'values (' .
            qq($this->payer)             . ', ' .
            qq($this->payer_ip)          . ', ' .
            qq($this->receiver)          . ', ' .
            qq($this->paypal_tx_id)      . ', ' .
            qq($this->item_number)       . ', ' .
            'now()'                      . ', ' .
            n($this->amount)             . ', ' .
            qq($this->currency)          . ', ' .
            qq($this->first_name)        . ', ' .
            qq($this->last_name)         . ', ' .
            qq($this->residence_country) .
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