<?php

namespace StreamsSro\Gaston\Http;

use StreamsSro\Gaston\Exception\GastonException;

/**
 * Default {@see HttpClientInterface} implementation built on ext-curl.
 *
 * It has no third-party dependencies, which is what lets the package run on
 * PHP 7.0+ with nothing but ext-curl and ext-json.
 */
class CurlHttpClient implements HttpClientInterface
{
    public function send($method, $url, array $headers, $upload = null, $timeout = 0.0, $connectTimeout = 0.0)
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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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

        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new GastonException('Request to ' . $url . ' failed: ' . $error);
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return new Response($status, $body);
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