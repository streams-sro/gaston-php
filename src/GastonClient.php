<?php

namespace StreamsSro\Gaston;

use StreamsSro\Gaston\Exception\AuthenticationException;
use StreamsSro\Gaston\Exception\BadRequestException;
use StreamsSro\Gaston\Exception\ExternalServiceException;
use StreamsSro\Gaston\Exception\GastonApiException;
use StreamsSro\Gaston\Exception\GastonException;
use StreamsSro\Gaston\Exception\NotFoundException;
use StreamsSro\Gaston\Exception\RateLimitException;
use StreamsSro\Gaston\Http\CurlHttpClient;
use StreamsSro\Gaston\Http\HttpClientInterface;
use StreamsSro\Gaston\Http\Response;
use StreamsSro\Gaston\Http\UploadFile;
use StreamsSro\Gaston\Model\AlignTranslationResult;
use StreamsSro\Gaston\Model\Directory;
use StreamsSro\Gaston\Model\Media;
use StreamsSro\Gaston\Model\MediaList;
use StreamsSro\Gaston\Model\SearchResults;
use StreamsSro\Gaston\Model\TranscribeResult;
use StreamsSro\Gaston\Model\TranslateResult;
use StreamsSro\Gaston\Model\User;

/**
 * A client for the Gaston transcription/translation/search API.
 *
 * Example:
 *
 *     $client = new GastonClient('gapi-...');
 *     $me = $client->me();
 *     echo $me->email, ' ', $me->usage->filesLeft;
 */
class GastonClient
{
    const BASE_URL = 'https://api.gaston.live';

    /** Quick metadata calls. */
    const DEFAULT_TIMEOUT = 30.0;

    /**
     * Endpoints that upload a file or fetch a remote URL can legitimately take
     * minutes, so they get a much more generous read timeout by default.
     */
    const DEFAULT_UPLOAD_TIMEOUT = 600.0;

    const DEFAULT_CONNECT_TIMEOUT = 10.0;

    /** Undocumented escape hatch for development/testing only. */
    const BASE_URL_OVERRIDE_ENV = 'GASTON_API_URL_OVERRIDE';

    /** @var string */
    private $token;

    /** @var string */
    private $baseUrl;

    /** @var float */
    private $timeout;

    /** @var float */
    private $uploadTimeout;

    /** @var float */
    private $connectTimeout;

    /** @var HttpClientInterface */
    private $http;

    /** @var array<int, string> */
    private static $statusExceptions = array(
        400 => BadRequestException::class,
        403 => AuthenticationException::class,
        404 => NotFoundException::class,
        429 => RateLimitException::class,
        502 => ExternalServiceException::class,
    );

    /**
     * @param string|null              $token          API token (the "gapi-..." token issued by the
     *                                                  platform). Falls back to the GASTON_API_TOKEN
     *                                                  environment variable.
     * @param float                    $timeout        Timeout for ordinary requests, in seconds
     *                                                  (0 = wait indefinitely). Defaults to 30s.
     * @param float                    $uploadTimeout  Timeout for the file-upload endpoint
     *                                                  (transcribe), in seconds. Defaults to 600s.
     * @param float                    $connectTimeout Connection timeout, in seconds. Defaults to 10s.
     * @param HttpClientInterface|null $httpClient     Optional transport override.
     *
     * @throws GastonException if no token is available.
     */
    public function __construct(
        $token = null,
        float $timeout = self::DEFAULT_TIMEOUT,
        float $uploadTimeout = self::DEFAULT_UPLOAD_TIMEOUT,
        float $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
        $httpClient = null
    ) {
        if ($token === null || $token === '') {
            $token = getenv('GASTON_API_TOKEN');
        }
        if ($token === false || $token === null || $token === '') {
            throw new GastonException(
                'An API token is required (pass it to the constructor or set GASTON_API_TOKEN).'
            );
        }

        $override = getenv(self::BASE_URL_OVERRIDE_ENV);
        $baseUrl = ($override !== false && $override !== '') ? $override : self::BASE_URL;

        $this->token = $token;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->uploadTimeout = $uploadTimeout;
        $this->connectTimeout = $connectTimeout;
        if ($httpClient !== null && !$httpClient instanceof HttpClientInterface) {
            throw new GastonException('$httpClient must implement ' . HttpClientInterface::class . '.');
        }
        $this->http = $httpClient !== null ? $httpClient : new CurlHttpClient();
    }

    // -- user ------------------------------------------------------------

    /**
     * Return the authenticated user and remaining usage.
     *
     * @return User
     */
    public function me(): User
    {
        return User::fromArray($this->request('GET', '/user/me'));
    }

    // -- media -----------------------------------------------------------

    /**
     * List media in the library, paginated.
     *
     * @param int      $page  1-based page number.
     * @param int|null $dirId Restrict to a directory (null for the root listing).
     * @return MediaList
     */
    public function listMedia(int $page = 1, $dirId = null): MediaList
    {
        return MediaList::fromArray(
            $this->request('GET', '/media/list', array('page' => $page, 'dir_id' => $dirId))
        );
    }

    /**
     * Fetch a single media item including its sentences.
     *
     * @param string      $mediaId The public media id (uid).
     * @param string|null $lang    Return sentences in this language (defaults to the original).
     * @return Media
     */
    public function getMedia(string $mediaId, $lang = null): Media
    {
        return Media::fromArray(
            $this->request('GET', '/media', array('media_id' => $mediaId, 'lang' => $lang))
        );
    }

    /**
     * Move a media item into a directory (dirId = null for root).
     *
     * @param string   $mediaId
     * @param int|null $dirId
     * @return Media
     */
    public function moveMedia(string $mediaId, $dirId = null): Media
    {
        return Media::fromArray(
            $this->request('PATCH', '/media', array('media_id' => $mediaId, 'dir_id' => $dirId))
        );
    }

    /**
     * Upload a media file and queue it for transcription.
     *
     * @param string|resource $file    Path to a file, or an open (binary) stream resource.
     * @param string|null     $lang    Source language hint (see {@see Languages::SUPPORTED}). If
     *                                 omitted the language is auto-detected.
     * @param int|null        $dirId   Directory to place the media into.
     * @param string|null     $title   Display title (defaults to the file name).
     * @param float           $timeout Override the client's upload timeout for this call, in
     *                                 seconds. -1 uses the configured upload timeout; 0 waits
     *                                 indefinitely.
     * @return TranscribeResult
     *
     * @throws BadRequestException if $lang is not a supported language.
     * @throws NotFoundException   if $dirId does not refer to an existing directory.
     * @throws GastonException     if $file cannot be read.
     */
    public function transcribe($file, $lang = null, $dirId = null, $title = null, float $timeout = -1.0): TranscribeResult
    {
        $this->assertSupportedLanguage($lang);

        $upload = $this->buildUpload($file);
        $params = array('lang' => $lang, 'dir_id' => $dirId, 'title' => $title);
        $effectiveTimeout = ($timeout < 0) ? $this->uploadTimeout : $timeout;

        $data = $this->request('POST', '/media/transcribe', $params, $upload, $effectiveTimeout);
        return TranscribeResult::fromArray($data);
    }

    /**
     * Queue transcription of a remote media URL (YouTube or web).
     *
     * @param string      $url   The media URL to download and transcribe.
     * @param string|null $lang  Source language hint (see {@see Languages::SUPPORTED}).
     * @param int|null    $dirId Directory to place the media into.
     * @return TranscribeResult
     *
     * @throws BadRequestException      if $lang is not a supported language, or the URL is
     *                                  unsupported/private.
     * @throws ExternalServiceException if fetching the URL or downloading the video fails
     *                                  upstream (e.g. YouTube retries exhausted).
     */
    public function transcribeUrl(string $url, $lang = null, $dirId = null): TranscribeResult
    {
        $this->assertSupportedLanguage($lang);
        $data = $this->request(
            'POST',
            '/media/transcribe-url',
            array('url' => $url, 'lang' => $lang, 'dir_id' => $dirId)
        );
        return TranscribeResult::fromArray($data);
    }

    /**
     * Queue a translation of a transcribed media into $targetLang.
     *
     * @param string $mediaId    The public media id.
     * @param string $targetLang Target language short code (see {@see Languages::translationLanguages()}).
     * @return TranslateResult
     *
     * @throws BadRequestException if $targetLang is not a supported translation target.
     */
    public function translate(string $mediaId, string $targetLang): TranslateResult
    {
        $targetLang = strtolower(trim($targetLang));
        if (!Languages::isTranslationTarget($targetLang)) {
            throw new BadRequestException(
                "Language '" . $targetLang . "' is not a supported translation target.",
                null,
                Languages::translationLanguages()
            );
        }
        $data = $this->request(
            'PATCH',
            '/media/translate',
            array('media_id' => $mediaId, 'target_lang' => $targetLang)
        );
        return TranslateResult::fromArray($data);
    }

    /**
     * Queue word-level alignment of a translation against source timestamps.
     *
     * Produces per-word start/end times for $targetLang (for karaoke-style
     * playback), based on the source-language WhisperX timestamps.
     *
     * @param string    $mediaId    The public media id.
     * @param string    $targetLang Target language short code; must already be fully
     *                              translated (available_languages[$targetLang] == 100) or the
     *                              API returns a 400.
     * @param bool|null $clamp      Force non-decreasing timestamps across a sentence. If
     *                              omitted, the worker uses its own default.
     * @return AlignTranslationResult
     *
     * @throws BadRequestException if $targetLang is not a supported translation target.
     */
    public function alignTranslation(string $mediaId, string $targetLang, $clamp = null): AlignTranslationResult
    {
        $targetLang = strtolower(trim($targetLang));
        if (!Languages::isTranslationTarget($targetLang)) {
            throw new BadRequestException(
                "Language '" . $targetLang . "' is not a supported translation target.",
                null,
                Languages::translationLanguages()
            );
        }
        $data = $this->request(
            'PATCH',
            '/media/align-translation',
            array('media_id' => $mediaId, 'target_lang' => $targetLang, 'clamp' => $clamp)
        );
        return AlignTranslationResult::fromArray($data);
    }

    /**
     * Queue speaker diarization for a (translated) media in $lang.
     *
     * @param string   $mediaId  The public media id.
     * @param string   $lang     Language of the transcript to diarize (must be fully translated first).
     * @param int|null $speakers Optional expected number of speakers.
     * @return TranscribeResult
     */
    public function diarize(string $mediaId, string $lang, $speakers = null): TranscribeResult
    {
        $data = $this->request(
            'PATCH',
            '/media/diarize',
            array('media_id' => $mediaId, 'lang' => $lang, 'speakers' => $speakers)
        );
        return TranscribeResult::fromArray($data);
    }

    // -- directories -----------------------------------------------------

    /**
     * Return the full nested directory tree for the user.
     *
     * @return array
     */
    public function directoryTree(): array
    {
        return $this->request('GET', '/directory/tree');
    }

    /**
     * Create a directory, optionally nested under $dirId.
     *
     * @param string   $title
     * @param int|null $dirId
     * @return Directory
     */
    public function createDirectory(string $title, $dirId = null): Directory
    {
        return Directory::fromArray(
            $this->request('POST', '/directory', array('title' => $title, 'dir_id' => $dirId))
        );
    }

    /**
     * Delete a directory. Returns true on success.
     *
     * @param int $dirId
     * @return bool
     */
    public function deleteDirectory(int $dirId): bool
    {
        $data = $this->request('DELETE', '/directory', array('dir_id' => $dirId));
        if (is_array($data)) {
            return !empty($data['result']);
        }
        return (bool) $data;
    }

    /**
     * Rename a directory and/or move it under $parentId.
     *
     * @param int      $dirId
     * @param string   $title
     * @param int|null $parentId
     * @return Directory
     */
    public function updateDirectory(int $dirId, string $title, $parentId = null): Directory
    {
        return Directory::fromArray(
            $this->request(
                'PATCH',
                '/directory',
                array('dir_id' => $dirId, 'title' => $title, 'parent_id' => $parentId)
            )
        );
    }

    // -- search ----------------------------------------------------------

    /**
     * Search for sentences across all transcribed media.
     *
     * The query supports a subset of the Lucene query_string syntax: boolean
     * AND/OR/NOT, grouping with parentheses, quoted phrases for exact matches
     * and trailing wildcards. Leading wildcards, field selectors, fuzzy (~),
     * boosts (^) and ranges are stripped server-side.
     *
     * @param string        $query  Full text query (must be at least 3 characters).
     * @param int           $from   Offset of the first result (for pagination).
     * @param int           $max    Maximum number of results to return.
     * @param int[]|string[]|null $dirIds Restrict the search to one or more directory ids.
     * @param string|null   $lang   Restrict the search to a single language.
     * @param int|string|null $mediaId Restrict the search to a single media.
     * @return SearchResults
     *
     * @throws BadRequestException if the query is shorter than 3 characters.
     */
    public function search(string $query, int $from = 0, int $max = 50, $dirIds = null, $lang = null, $mediaId = null): SearchResults
    {
        if (strlen($query) < 3) {
            throw new BadRequestException('Query must be at least 3 characters.');
        }
        $params = array(
            'query' => $query,
            '_from' => $from,
            '_max' => $max,
            'lang' => $lang,
        );
        if ($dirIds !== null) {
            $ids = array();
            foreach ($dirIds as $id) {
                $ids[] = (string) $id;
            }
            $params['dir_ids'] = $ids;
        }
        if ($mediaId !== null) {
            $params['media_id'] = (string) $mediaId;
        }
        return SearchResults::fromArray($this->request('GET', '/sentence/search', $params));
    }

    // -- low level -------------------------------------------------------

    /**
     * @param string|resource $file
     * @return UploadFile
     *
     * @throws GastonException
     */
    private function buildUpload($file): UploadFile
    {
        if (is_string($file)) {
            if (!is_readable($file)) {
                throw new GastonException("File '" . $file . "' does not exist or is not readable.");
            }
            return UploadFile::fromPath($file);
        }
        if (is_resource($file)) {
            $contents = stream_get_contents($file);
            if ($contents === false) {
                throw new GastonException('Failed to read the provided file stream.');
            }
            return UploadFile::fromContents($contents);
        }
        throw new GastonException('transcribe() expects a file path or a stream resource.');
    }

    /**
     * @param string|null $lang
     *
     * @throws BadRequestException
     */
    private function assertSupportedLanguage($lang)
    {
        if ($lang !== null && !Languages::isSupported($lang)) {
            throw new BadRequestException(
                "Language '" . $lang . "' is not supported.",
                null,
                Languages::SUPPORTED
            );
        }
    }

    /**
     * @param string          $method
     * @param string          $path
     * @param array           $params
     * @param UploadFile|null $upload
     * @param float|null      $timeout Per-call read timeout; null uses the default timeout.
     * @return mixed Decoded JSON (array) or, for non-JSON success responses, the raw body string.
     *
     * @throws GastonException
     */
    private function request($method, $path, array $params = array(), $upload = null, $timeout = null)
    {
        $url = $this->baseUrl . $path;
        $query = $this->buildQuery($params);
        if ($query !== '') {
            $url .= '?' . $query;
        }

        $headers = array(
            'token' => $this->token,
            'Accept' => 'application/json',
        );

        $effectiveTimeout = $timeout === null ? $this->timeout : $timeout;
        $response = $this->http->send($method, $url, $headers, $upload, $effectiveTimeout, $this->connectTimeout);

        return $this->handleResponse($response);
    }

    /**
     * @param Response $response
     * @return mixed
     *
     * @throws GastonException
     */
    private function handleResponse(Response $response)
    {
        $body = json_decode($response->body, true);
        $decoded = json_last_error() === JSON_ERROR_NONE;

        if (!$decoded) {
            if ($response->isOk()) {
                return $response->body;
            }
            throw new GastonApiException(
                'Non-JSON response from server: ' . substr($response->body, 0, 200),
                $response->statusCode
            );
        }

        // The API signals failures both via HTTP status codes and via an
        // "error" key in an otherwise 200 response. Handle both.
        $errorMessage = (is_array($body) && isset($body['error'])) ? $body['error'] : null;

        if (!$response->isOk() || $errorMessage !== null) {
            $exceptionClass = isset(self::$statusExceptions[$response->statusCode])
                ? self::$statusExceptions[$response->statusCode]
                : GastonApiException::class;

            $details = null;
            if (is_array($body)) {
                if (isset($body['details'])) {
                    $details = $body['details'];
                } elseif (isset($body['supported_languages'])) {
                    $details = $body['supported_languages'];
                } elseif (isset($body['supportedLanguages'])) {
                    $details = $body['supportedLanguages'];
                }
            }

            $message = $errorMessage !== null
                ? $errorMessage
                : 'Request failed with status ' . $response->statusCode;

            throw new $exceptionClass($message, $response->statusCode, $details, $body);
        }

        return $body;
    }

    /**
     * Build a query string, dropping null values and repeating list parameters
     * (so dir_ids => [1, 2] becomes "dir_ids=1&dir_ids=2", matching the API).
     *
     * @param array $params
     * @return string
     */
    private function buildQuery(array $params): string
    {
        $pairs = array();
        foreach ($params as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $item) {
                    $pairs[] = rawurlencode($key) . '=' . rawurlencode($this->scalarToString($item));
                }
                continue;
            }
            $pairs[] = rawurlencode($key) . '=' . rawurlencode($this->scalarToString($value));
        }
        return implode('&', $pairs);
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function scalarToString($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return (string) $value;
    }
}