<?php

namespace StreamsSro\Gaston\Http;

/**
 * A transport that can stream a response body straight into a stream.
 *
 * Used for large downloads (e.g. audio exports) so the body never has to be
 * held in memory. Transports that only implement {@see HttpClientInterface}
 * still work; the client then buffers the body and writes it out itself.
 */
interface StreamingHttpClientInterface extends HttpClientInterface
{
    /**
     * Perform an HTTP request, writing a successful (2xx) body to $sink.
     *
     * For a 2xx response the returned Response has an empty body; any other
     * response's body is buffered into the Response instead (so error
     * payloads can be inspected) and nothing is written to $sink.
     *
     * @param string   $method         HTTP verb.
     * @param string   $url            Fully-formed URL including query string.
     * @param array    $headers        Associative array of header name => value.
     * @param resource $sink           Writable stream that receives the body.
     * @param float    $timeout        Total/read timeout in seconds (0 = no limit).
     * @param float    $connectTimeout Connection timeout in seconds (0 = no limit).
     * @return Response
     */
    public function sendToStream($method, $url, array $headers, $sink, $timeout = 0.0, $connectTimeout = 0.0);
}
