<?php

namespace StreamsSro\Gaston\Exception;

/**
 * Raised when the API returns an error response.
 *
 * Carries the human readable error message, the HTTP status code (if any),
 * any extra details supplied alongside the error and the full decoded payload.
 */
class GastonApiException extends GastonException
{
    /** @var int|null */
    private $statusCode;

    /** @var mixed */
    private $details;

    /** @var mixed */
    private $payload;

    /**
     * @param string   $message    Human readable error message returned by the API.
     * @param int|null $statusCode HTTP status code of the response (if any).
     * @param mixed    $details    Optional extra payload returned alongside the error.
     * @param mixed    $payload    The full decoded response body.
     */
    public function __construct($message, $statusCode = null, $details = null, $payload = null)
    {
        $this->statusCode = $statusCode;
        $this->details = $details;
        $this->payload = $payload;
        $prefix = $statusCode !== null ? '[' . $statusCode . '] ' : '';
        parent::__construct($prefix . $message, $statusCode === null ? 0 : (int) $statusCode);
    }

    /**
     * @return int|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }
}