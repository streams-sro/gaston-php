<?php

namespace StreamsSro\Gaston\Http;

/**
 * Abstraction over the transport used to talk to the API.
 *
 * The default implementation is {@see CurlHttpClient}. Tests (and anyone who
 * wants to plug in their own transport) may supply an alternative.
 */
interface HttpClientInterface
{
    /**
     * Perform an HTTP request and return the raw response.
     *
     * Any query string is already baked into $url by the caller.
     *
     * @param string          $method         HTTP verb (GET, POST, PATCH, DELETE).
     * @param string          $url            Fully-formed URL including query string.
     * @param array           $headers        Associative array of header name => value.
     * @param UploadFile|null $upload         Optional multipart file part.
     * @param float           $timeout        Total/read timeout in seconds (0 = no limit).
     * @param float           $connectTimeout Connection timeout in seconds (0 = no limit).
     * @return Response
     */
    public function send($method, $url, array $headers, $upload = null, $timeout = 0.0, $connectTimeout = 0.0);
}