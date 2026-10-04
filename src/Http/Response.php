<?php

namespace StreamsSro\Gaston\Http;

/**
 * A minimal HTTP response: a status code, the raw response body and headers.
 */
class Response
{
    /** @var int */
    public $statusCode;

    /** @var string */
    public $body;

    /** @var array<string, string> Response headers, keyed by lower-cased name. */
    public $headers;

    /**
     * @param int                   $statusCode
     * @param string                $body
     * @param array<string, string> $headers Header name => value (names are case-insensitive).
     */
    public function __construct($statusCode, $body, array $headers = array())
    {
        $this->statusCode = (int) $statusCode;
        $this->body = (string) $body;
        $this->headers = array();
        foreach ($headers as $name => $value) {
            $this->headers[strtolower($name)] = (string) $value;
        }
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

    /**
     * Return a header value (case-insensitive name), or null if it is absent.
     *
     * @param string $name
     * @return string|null
     */
    public function getHeader($name)
    {
        $name = strtolower($name);
        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }
}
