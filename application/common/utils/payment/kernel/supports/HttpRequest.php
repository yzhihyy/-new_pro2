<?php

namespace app\common\utils\payment\kernel\supports;

class HttpRequest
{
    /**
     * Request.
     *
     * @param string $url
     * @param array $header
     * @param array $data
     * @param string $method
     *
     * @return mixed
     */
    public static function request($url, $header, $data, $method)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // custom request method
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        // not include the header in the output
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // HTTP header fields
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        // return the transfer as a string of the return value of curl_exec() instead of outputting it directly
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // The full data to post in a HTTP "POST" operation
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        // stop cURL from verifying the peer's certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // not check common name in the SSL peer certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }

    /**
     * GET request.
     *
     * @param string $url
     * @param array $header
     * @param array $query
     *
     * @return mixed
     */
    public static function httpGet($url, array $header = [], array $query = [])
    {
        return self::request($url, $header, $query, 'GET');
    }

    /**
     * POST request.
     *
     * @param string $url
     * @param array $header
     * @param array $data
     *
     * @return mixed
     */
    public static function httpPost($url, array $header = [], array $data = [])
    {
        return self::request($url, $header, $data, 'POST');
    }
}
