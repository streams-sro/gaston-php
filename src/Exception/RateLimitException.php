<?php

namespace StreamsSro\Gaston\Exception;

/**
 * Raised when the monthly file/API limit is exhausted (HTTP 429).
 */
class RateLimitException extends GastonApiException
{
}