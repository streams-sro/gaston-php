<?php

namespace StreamsSro\Gaston\Http;

use StreamsSro\Gaston\Exception\GastonException;

/**
 * Default {@see HttpClientInterface} implementation built on ext-curl.
 *
 * It has no third-party dependencies, which is what lets the package run on
 * PHP 7.0+ with nothing but ext-curl and ext-json.
 */
class CurlHttpClient implements StreamingHttpClientInterface
{
    public function send($method, $url, array $headers, $upload = null, $timeout = 0.0, $connectTimeout = 0.0)
    {
        $ch = $this->init($method, $url, $headers, $upload, $timeout, $connectTimeout);
        $responseHeaders = array();
        $status = 0;
        $this->captureHeaders($ch, $responseHeaders, $status);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $body = curl_exec($ch);
        if ($body === false) {
            $this->fail($ch, $url);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return new Response($status, $body, $responseHeaders);
    }

    public function sendToStream($method, $url, array $headers, $sink, $timeout = 0.0, $connectTimeout = 0.0)
    {
        $ch = $this->init($method, $url, $headers, null, $timeout, $connectTimeout);
        $responseHeaders = array();
        $status = 0;
        $this->captureHeaders($ch, $responseHeaders, $status);

        // Only a successful body goes to the sink; anything else (an error
        // payload) is buffered so the caller can inspect it.
        $buffer = '';
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$status, &$buffer, $sink) {
            if ($status >= 200 && $status < 300) {
                $written = fwrite($sink, $chunk);
                // Returning a short count makes cURL abort with a write error.
                return $written === false ? 0 : $written;
            }
            $buffer .= $chunk;
            return strlen($chunk);
        });

        if (curl_exec($ch) === false) {
            $this->fail($ch, $url);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return new Response($status, $buffer, $responseHeaders);
    }

    /**
     * @return resource|\CurlHandle
     *
     * @throws GastonException
     */
    private function init($method, $url, array $headers, $upload, $timeout, $connectTimeout)
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new GastonException('Failed to initialise cURL.');
        }

        $headerLines = array();
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // Timeouts are expressed in seconds (possibly fractional); 0 means
        // "wait indefinitely", matching the higher-level client contract.
        if ($timeout > 0) {
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, (int) round($timeout * 1000));
        } else {
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        }
        if ($connectTimeout > 0) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, (int) round($connectTimeout * 1000));
        } else {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        }

        if ($upload !== null) {
            $this->applyMultipart($ch, $upload, $headerLines);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);

        return $ch;
    }

    /**
     * Collect response headers (lower-cased names) and the status code.
     *
     * When redirects are followed cURL reports every response's headers, so
     * both are reset at each status line and end up describing the final one.
     *
     * @param resource|\CurlHandle  $ch
     * @param array<string, string> $headers Filled by reference.
     * @param int                   $status  Filled by reference.
     */
    private function captureHeaders($ch, array &$headers, &$status)
    {
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $line) use (&$headers, &$status) {
            $trimmed = trim($line);
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $trimmed, $m)) {
                $headers = array();
                $status = (int) $m[1];
            } elseif (strpos($trimmed, ':') !== false) {
                list($name, $value) = explode(':', $trimmed, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
            return strlen($line);
        });
    }

    /**
     * @param resource|\CurlHandle $ch
     * @param string               $url
     *
     * @throws GastonException
     */
    private function fail($ch, $url)
    {
        $error = curl_error($ch);
        curl_close($ch);
        throw new GastonException('Request to ' . $url . ' failed: ' . $error);
    }

    /**
     * Configure cURL for a multipart/form-data upload.
     *
     * A path-backed file is handed to cURL as a CURLFile so it is streamed from
     * disk; an in-memory file is encoded into the body by hand so we never need
     * to touch the filesystem.
     *
     * @param resource   $ch
     * @param UploadFile $upload
     * @param string[]   $headerLines Passed by reference so we can add a boundary header.
     */
    private function applyMultipart($ch, UploadFile $upload, array &$headerLines)
    {
        curl_setopt($ch, CURLOPT_POST, true);

        if ($upload->path !== null) {
            $part = new \CURLFile($upload->path, 'application/octet-stream', $upload->filename);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array($upload->field => $part));
            return;
        }

        $boundary = '----GastonPhp' . bin2hex(random_bytes(16));
        $eol = "\r\n";
        $body = '--' . $boundary . $eol;
        $body .= 'Content-Disposition: form-data; name="' . $upload->field . '"; filename="' . $upload->filename . '"' . $eol;
        $body .= 'Content-Type: application/octet-stream' . $eol . $eol;
        $body .= $upload->contents . $eol;
        $body .= '--' . $boundary . '--' . $eol;

        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $headerLines[] = 'Content-Type: multipart/form-data; boundary=' . $boundary;
    }
}
