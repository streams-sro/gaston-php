<?php

namespace StreamsSro\Gaston\Http;

/**
 * A minimal HTTP response: a status code and the raw response body.
 */
class Response
{
    /** @var int */
    public $statusCode;

    /** @var string */
    public $body;

    /**
     * @param int    $statusCode
     * @param string $body
     */
    public function __construct($statusCode, $body)
    {
        $this->statusCode = (int) $statusCode;
        $this->body = (string) $body;
    }

    /**
     * Whether the status code indicates success (2xx/3xx).
     *
     * @return bool
     */
    public function isOk()
    {
        return $this->statusCode >= 200 && $this->statusCode < 400;
    }
}