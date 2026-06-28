<?php

namespace StreamsSro\Gaston\Tests;

use StreamsSro\Gaston\Http\HttpClientInterface;
use StreamsSro\Gaston\Http\Response;
use StreamsSro\Gaston\Http\UploadFile;

/**
 * In-memory transport for tests. Returns a queued response and records the
 * request it was given so assertions can inspect it.
 */
class FakeHttpClient implements HttpClientInterface
{
    /** @var array Recorded requests. */
    public $calls = array();

    /** @var Response[] Queue of responses to return, in order. */
    private $responses = array();

    /**
     * Queue a JSON response.
     *
     * @param array $json
     * @param int   $status
     * @return self
     */
    public function queueJson(array $json, $status = 200)
    {
        $this->responses[] = new Response($status, json_encode($json));
        return $this;
    }

    /**
     * Queue a raw (non-JSON) response.
     *
     * @param string $body
     * @param int    $status
     * @return self
     */
    public function queueRaw($body, $status = 200)
    {
        $this->responses[] = new Response($status, $body);
        return $this;
    }

    public function send($method, $url, array $headers, $upload = null, $timeout = 0.0, $connectTimeout = 0.0)
    {
        $this->calls[] = array(
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'upload' => $upload,
            'timeout' => $timeout,
            'connectTimeout' => $connectTimeout,
        );
        if (empty($this->responses)) {
            return new Response(200, '{}');
        }
        return array_shift($this->responses);
    }

    /**
     * @return array The most recently recorded request.
     */
    public function lastCall()
    {
        return end($this->calls);
    }
}