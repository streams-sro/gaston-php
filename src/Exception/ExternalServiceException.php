<?php

namespace StreamsSro\Gaston\Exception;

/**
 * Raised when the API fails because an external dependency (e.g. fetching a
 * URL or downloading a YouTube video) failed (HTTP 502).
 */
class ExternalServiceException extends GastonApiException
{
}