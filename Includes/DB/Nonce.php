<?php

include_once('../Includes/DbConnect.php');
include_once('../Includes/Snippets.php');

// dao for pp_nonce table
class Nonce {
    var $_NONCE_SECRET = 'Brrrrrragadabadingbim!';

    // class functions
    // ---------------
    function generateNonce($userId, $action, $timestamp) {
        return md5($userId . '-' . $action . '-' . $timestamp . '-' . $_NONCE_SECRET);
    }

    function isNonceValidAndUnused($nonceStr, $userId, $action, $timestamp) {
        global $logger;

        // check if nonce is valid
        if (md5($userId . '-' . $action . '-' . $timestamp . '-' . $_NONCE_SECRET) === $nonceStr) {
            // check in nonce table if already consumed
            $result = _mysql_query(
                'select count(*) as cnt ' .
                'from pp_nonce ' .
                'where nonce_str = ' . qq($nonceStr)
            );

            if ($row = mysql_fetch_array($result)) {
                $logger->info($row['cnt']);
                if ($row['cnt'] > 0) {
                    $logger->warn('nonce has already been consumed!');
                    mysql_free_result($result);
                    return false;
                }
            }

            mysql_free_result($result);
            return true;

        } else {
            $logger->warn('nonce is invalid!');
            return false;
        }
    }

    function invalidateNonce($nonceStr) {
        // from time to time cleanup nonce table (remove really old nonces)
        if (time() % 60 < 2) { // in seconds 0 & 1 of every minute
            _mysql_query(
                'delete from pp_nonce ' .
                'where creation_date < ' . qq(date('Y:m:d H:i:s', time() - 60 * 60 * 24 * 180)) // delete after 180 days
            );
        }

        // insert into used nonces table
        return _mysql_query(
            'insert into pp_nonce ' .
            '(nonce_str, creation_date) ' .
            'values (' .
            qq($nonceStr)     . ', ' .
            'now()'           .
            ')'
        );
    }

    function create_table() {
        return _mysql_query(
            'create table if not exists pp_nonce ' .
            '(' .
            'nonce_str           varchar(255) not null, ' .
            'creation_date       datetime     not null, ' .
            'primary key (nonce_str)' .
            ')'
        );
    }
}

?>