<?php

namespace LaterPay\Client\Http;

/**
 * LaterPay Subscriptions class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class Transport
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var array
     */
    protected $headers = array();

    /**
     * @var string|null
     */
    protected $response;

    /**
     * @var int
     */
    protected $timeout = 30;

    /**
     * @param string $url
     *
     * @return self
     */
    public function setURL($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return self
     */
    public function setHeaders(array $headers = array())
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @return string
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function call()
    {
        $this
            ->processOptions()
            ->executeCall();

        return $this->response;
    }

    /**
     * @return self
     *
     * @throws \InvalidArgumentException
     */
    protected function processOptions()
    {
        if (empty($this->url)) {
            throw new \InvalidArgumentException('No URL provided');
        }

        $this->timeout = isset($this->options['timeout']) ? (int)$this->options['timeout'] : $this->timeout;

        return $this;
    }

    /**
     *
     * @return self
     *
     * @throws \RuntimeException
     */
    protected function executeCall()
    {
        $this->convertDataToURL();

        if (function_exists('vip_safe_wp_remote_get')) {
            $rawResponse = vip_safe_wp_remote_get(
                $this->url,
                array(
                    'headers' => $this->headers,
                    'timeout' => $this->timeout,
                )
            );
        } else {
            $rawResponse = wp_remote_get(
                $this->url,
                array(
                    'headers' => $this->headers,
                    'timeout' => $this->timeout,
                )
            );
        }

        $this->response = wp_remote_retrieve_body($rawResponse);
        $response_code  = wp_remote_retrieve_response_code($rawResponse);

        if (empty($response_code)) {
            throw new \RuntimeException(
                wp_remote_retrieve_response_message($rawResponse)
            );
        }

        return $this;
    }

    /**
     * Format a URL given GET data.
     *
     * @return self
     */
    protected function convertDataToURL()
    {
        if (empty($this->data)) {
            return $this;
        }

        $urlParts = explode('?', $this->url);

        if (empty($urlParts[1])) {
            $query = $urlParts[1] = '';
        } else {
            $query = $urlParts[1];
        }

        $query .= '&' . http_build_query($this->data, null, '&');
        $query = trim($query, '&');

        if (empty($urlParts[1])) {
            $this->url .= '?' . $query;
        } else {
            $this->url = str_replace($urlParts[1], $query, $this->url);
        }

        $this->data = array();

        return $this;
    }
}
