<?php

namespace app\common\utils\payment\kernel\supports\traits;

trait HttpRequest
{
    /**
     * Send a GET request.
     *
     * @param string $endpoint
     * @param array $query
     * @param array $header
     *
     * @return array|string
     */
    protected function get($endpoint, $query = [], $header = [])
    {
        if (!empty($query)) {
            $endpoint .= '?' . http_build_query($query);
        }

        return $this->request('GET', $endpoint, [], $header);
    }

    /**
     * Send a POST request.
     *
     * @param string $endpoint
     * @param string|array $data
     * @param array $header
     * @param array $options
     *
     * @return array|string
     */
    protected function post($endpoint, $data, $header = [], $options = [])
    {
        return $this->request('POST', $endpoint, $data, $header, $options);
    }

    /**
     * Send request.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @param array $header
     * @param array $options
     *
     * @return array|string
     */
    protected function request($method, $endpoint, $data = [], $header = [], $options = [])
    {
        $baseUri = property_exists($this, 'baseUri') ? $this->baseUri : '';
        $timeout = property_exists($this, 'timeout') ? $this->timeout : 5.0;
        $url = $baseUri . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if (isset($options['cert']) && !empty($options['cert']) && isset($options['sslkey']) && !empty($options['sslkey'])) {
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $options['cert']);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $options['sslkey']);
        }

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                break;
        }

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $contentType = isset($info['content_type']) ? $info['content_type'] : '';
        if (!empty($contentType)) {
            if (false !== stripos($contentType, 'json') || stripos($contentType, 'javascript')) {
                return json_decode($result, true);
            } elseif (false !== stripos($contentType, 'xml')) {
                return json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE), true);
            }
        }

        return $result;
    }
}
