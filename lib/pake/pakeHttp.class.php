<?php

/**
 * @package    pake
 * @author     Alexey Zakhlestin <indeyets@gmail.com>
 * @copyright  2010 Alexey Zakhlestin <indeyets@gmail.com>
 * @license    see the LICENSE file included in the distribution
 */

class pakeHttp
{
    /**
     * execute HTTP Request
     *
     * @param string $method 
     * @param string $url 
     * @param mixed $query_data string or array
     * @param mixed $body string or array
     * @param array $headers 
     * @param array $options 
     * @return string
     */
    public static function request($method, $url, $query_data = null, $body = null, array $headers = array(), array $options = array())
    {
        $method = strtoupper($method);

        $_options = array(
            'method' => $method,
            'user_agent' => 'pake '.pakeApp::VERSION,
            'ignore_errors' => true,
        );

        if (null !== $body) {
            if (is_array($body)) {
                $body = http_build_query($body);
            }

            $_options['content'] = $body;
        }

        if (count($headers) > 0) {
            $_options['header'] = implode("\r\n", $headers)."\r\n";
        }

        $options = array_merge($_options, $options);

        if (null !== $query_data) {
            if (is_array($query_data)) {
                $query_data = http_build_query($query_data);
            }

            $url .= '?'.$query_data;
        }

        $context = stream_context_create(array('http' => $options));
        $stream = fopen($url, 'r', false, $context);

        if (false === $stream) {
            throw new pakeException('HTTP request failed');
        }

        pake_echo_action('HTTP '.$method, $url);

        $meta = stream_get_meta_data($stream);
        $response = stream_get_contents($stream);

        fclose($stream);

        $status = $meta['wrapper_data'][0];
        $code = substr($status, 9, 3);

        if ($status > 400)
            throw new pakeException('http request returned: '.$status);

        return $response;
    }

    /**
     * execute HTTP Request and match response against PCRE regexp
     *
     * @param string $regexp PCRE regexp
     * @param string $method 
     * @param string $url 
     * @param mixed $query_data string or array
     * @param mixed $body string or array
     * @param array $headers 
     * @param array $options 
     * @return void
     */
    public static function matchRequest($regexp, $method, $url, $query_data = null, $body = null, array $headers = array(), array $options = array())
    {
        $response = self::request($method, $url, $query_data, $body, $headers, $options);

        $result = preg_match($regexp, $response);

        if (false === $result) {
            throw new pakeException("There's some error with this regular expression: ".$regexp);
        }

        if (0 === $result) {
            throw new pakeException("HTTP Response didn't match against regular expression: ".$regexp);
        }

        pake_echo_comment('HTTP response matched against '.$regexp);
    }


    // Convenience wrappers follow
    public static function get($url, $query_data = null, array $headers = array(), array $options = array())
    {
        return self::request('GET', $url, $query_data, null, $headers, $options);
    }

    public static function matchGet($regexp, $url, $query_data = null, array $headers = array(), array $options = array())
    {
        return self::matchRequest($regexp, 'GET', $url, $query_data, null, $headers, $options);
    }

    public static function post($url, $query_data = null, $body = null, array $headers = array(), array $options = array())
    {
        return self::request('POST', $url, $query_data, $body, $headers, $options);
    }

    public static function matchPost($regexp, $url, $query_data = null, $body = null, array $headers = array(), array $options = array())
    {
        return self::matchRequest($regexp, 'POST', $url, $query_data, $body, $headers, $options);
    }
}
