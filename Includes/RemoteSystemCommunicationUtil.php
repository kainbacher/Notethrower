<?php

function sendGetRequest($url, $timeoutSeconds = 30, $followRedirects = true) {
    global $logger;

    $logger->debug('url: ' . $url);
    $u = @parse_url($url);
    $logger->debug('url parse result: ' . print_r($u, true));
    if ($u) {
        return doSendGetRequest(
            $u['scheme'] . '://',
            $u['host'],
            isset($u['port']) ? $u['port'] : null,
            $u['path'] . ($u['query'] ? '?' . $u['query'] : ''),
            $timeoutSeconds,
            $followRedirects
        );

    } else {
        return array(
            'result'          => 'FAILURE',
            'error'           => 'Invalid URL: ' . $url,
            'responseHeaders' => null,
            'responseBody'    => null
        );
    }
}

function doSendGetRequest($protocol, $domain, $port, $endpointPathPlusQueryString, $timeoutSeconds = 30, $followRedirects = true) {
    global $logger;

    $crlf = "\r\n";

    if ($protocol == 'http://')  $protocol = '';
    if ($protocol == 'https://') $protocol = 'ssl://';

    if ($protocol == 'ssl://' && !$port) $port = 443;

    if (!$port) $port = 80;

    $header  = 'GET ' . $endpointPathPlusQueryString . ' HTTP/1.0' . $crlf;
    $header .= 'User-agent: imagazine_de_client' . $crlf;
    $header .= 'Host: ' . $domain . $crlf;
    $header .= 'Connection: close' . $crlf . $crlf;

    $logger->info('GETting data from ' . $protocol . $domain . ':' . $port . $endpointPathPlusQueryString);
    $errno  = null;
    $errstr = null;
    $fp = fsockopen($protocol . $domain, $port, $errno, $errstr, $timeoutSeconds); // protocol for https must be 'ssl://', port must be 443; for http it must be empty and port may be 80

    if (!$fp) {
        // HTTP ERROR
        $logger->error('HTTP error while GETing data: ' . $errno . ' ' . $errstr);
        return array(
            'result'          => 'FAILURE',
            'error'           => $errno . ' ' . $errstr,
            'responseHeaders' => null,
            'responseBody'    => null
        );

    } else {
        fputs ($fp, $header);

        $response = '';
        while (!feof($fp)) {
            $response .= fgets($fp, 128);
        }

        fclose ($fp);

        // split header and body
        $header = null;
        $body = null;
        $pos = strpos($response, $crlf . $crlf);
        if ($pos !== false) {
            $header = substr($response, 0, $pos);
            $body = substr($response, $pos + 2 * strlen($crlf));

        } else {
            $body = $response;
        }

        // parse headers
        $headers = array();
        $lines = explode($crlf, $header);
        foreach($lines as $line) {
            if(($pos = strpos($line, ':')) !== false) {
                $headers[strtolower(trim(substr($line, 0, $pos)))] = trim(substr($line, $pos+1));
            }
        }

        if (isset($headers['location']) && $headers['location'] && $followRedirects) {
            $logger->info('Redirection header received. Re-sending request with url: ' . $headers['location']);
            return sendGetRequest($headers['location'], $timeoutSeconds, false);
        }

        return array(
            'result'          => 'SUCCESS',
            'error'           => null,
            'responseHeaders' => $headers,
            'responseBody'    => $body
        );
    }
}

function sendPostRequest($url, $paramsList, $timeoutSeconds = 30, $followRedirects = true) {
    global $logger;

    $logger->debug('url: ' . $url);
    $u = @parse_url($url);
    $logger->debug('url parse result: ' . print_r($u, true));
    if ($u) {
        return doSendPostRequest(
            $u['scheme'] . '://',
            $u['host'],
            isset($u['port']) ? $u['port'] : null,
            $u['path'] . ($u['query'] ? '?' . $u['query'] : ''),
            $paramsList,
            $timeoutSeconds,
            $followRedirects
        );

    } else {
        return array(
            'result'          => 'FAILURE',
            'error'           => 'Invalid URL: ' . $url,
            'responseHeaders' => null,
            'responseBody'    => null
        );
    }
}

function doSendPostRequest($protocol, $domain, $port, $endpointPath, $paramsList, $timeoutSeconds = 30, $followRedirects = true) {
    global $logger;

    $crlf = "\r\n";

    if ($protocol == 'http://')  $protocol = '';
    if ($protocol == 'https://') $protocol = 'ssl://';

    if ($protocol == 'ssl://' && !$port) $port = 443;

    if (!$port) $port = 80;

    $params = '';
    foreach ($paramsList as $key => $value) {
        $value = urlencode(stripslashes($value));
        $params .= "&$key=$value"; // this assumes that the keys don't contain problematic characters
    }
    $logger->debug('POST params: ' . $params);

    $header  = 'POST ' . $endpointPath . ' HTTP/1.0' . $crlf;
    $header .= 'User-agent: imagazine_de_client' . $crlf;
    $header .= 'Host: ' . $domain . $crlf;
    $header .= 'Content-Type: application/x-www-form-urlencoded' . $crlf;
    $header .= 'Content-Length: ' . strlen($params) . $crlf . $crlf;

    $logger->info('POSTing request data to ' . $domain . ':' . $port . $endpointPath);
    $errno  = null;
    $errstr = null;
    $fp = fsockopen($protocol . $domain, $port, $errno, $errstr, $timeoutSeconds); // protocol for https must be 'ssl://', port must be 443; for http it must be empty and port may be 80

    if (!$fp) {
        // HTTP ERROR
        $logger->error('HTTP error while POSTing request data: ' . $errno . ' ' . $errstr);
        return array(
            'result'          => 'FAILURE',
            'error'           => $errno . ' ' . $errstr,
            'responseHeaders' => null,
            'responseBody'    => null
        );

    } else {
        fputs ($fp, $header . $params);

        $response = '';
        while (!feof($fp)) {
            $response .= fgets($fp, 128);
        }

        fclose ($fp);

        // split header and body
        $header = null;
        $body = null;
        $pos = strpos($response, $crlf . $crlf);
        if ($pos !== false) {
            $header = substr($response, 0, $pos);
            $body = substr($response, $pos + 2 * strlen($crlf));

        } else {
            $body = $response;
        }

        // parse headers
        $headers = array();
        $lines = explode($crlf, $header);
        foreach($lines as $line) {
            if (($pos = strpos($line, ':')) !== false) {
                $headers[strtolower(trim(substr($line, 0, $pos)))] = trim(substr($line, $pos+1));
            } else {
                if (count($headers) == 0) {
                    $headers['_httpResponseCodeLine'] = $line;
                }
            }
        }

        if (isset($headers['location']) && $headers['location'] && $followRedirects) {
            $logger->info('Redirection header received. Re-sending request with url: ' . $headers['location']);
            return sendPostRequest($headers['location'], $paramsList, $timeoutSeconds, false);
        }

        return array(
            'result'          => 'SUCCESS',
            'error'           => null,
            'responseHeaders' => $headers,
            'responseBody'    => $body
        );
    }
}

?>
