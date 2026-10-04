<?php

namespace StreamsSro\Gaston\Tests;

use StreamsSro\Gaston\Http\Response;
use StreamsSro\Gaston\Http\StreamingHttpClientInterface;

/**
 * {@see FakeHttpClient} that also supports streaming, mimicking
 * CurlHttpClient::sendToStream(): 2xx bodies go to the sink, others are
 * returned in the Response.
 */
class FakeStreamingHttpClient extends FakeHttpClient implements StreamingHttpClientInterface
{
    /** @var int Number of sendToStream() calls. */
    public $streamCalls = 0;

    public function sendToStream($method, $url, array $headers, $sink, $timeout = 0.0, $connectTimeout = 0.0)
    {
        $this->streamCalls++;
        $response = $this->send($method, $url, $headers, null, $timeout, $connectTimeout);
        if ($response->statusCode >= 200 && $response->statusCode < 300) {
            fwrite($sink, $response->body);
            return new Response($response->statusCode, '', $response->headers);
        }
        return $response;
    }
}
