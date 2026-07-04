# Gaston API Client (PHP)

A small, dependency-free PHP client for the **Gaston API**: transcription,
translation and full-text search of sentences within transcribed recordings.

> Requires a Gaston account and an API token (see [Configuration](#configuration)).

This is a PHP port of the [official Python client](https://pypi.org/project/gaston/)
and tracks the same API.

## Requirements

- PHP **7.0+**
- The `curl` and `json` extensions (both bundled with PHP by default)

No third-party runtime dependencies.

## Installation

```bash
composer require streams-sro/gaston
```

## Quick start

```php
use StreamsSro\Gaston\GastonClient;

$client = new GastonClient('gapi-...');

// Who am I + remaining quota
$me = $client->me();
echo $me->email, ' files left: ', $me->usage->filesLeft, PHP_EOL;

// Transcribe a local file
$result = $client->transcribe('interview.mp4', 'en', null, 'My interview');
echo $result->id, ' ', $result->state, PHP_EOL;

// Transcribe from a URL (YouTube or web)
$client->transcribeUrl('https://youtu.be/dQw4w9WgXcQ', 'en');

// Translate an existing transcription
$client->translate($result->id, 'de');

// Speaker diarization (requires a completed translation in that language)
$client->diarize($result->id, 'de', 2);

// Fetch a media item with its sentences
$media = $client->getMedia($result->id, 'en');
foreach ($media->sentences as $sentence) {
    echo $sentence->id, ' ', $sentence->getText(), ' ', $sentence->speaker, PHP_EOL;
}

// Full text search across the whole library
$results = $client->search('climate change', 0, 20);
echo 'total matches: ', $results->total, PHP_EOL;
foreach ($results as $hit) {
    echo $hit['_sentence']['body'], ' -> ', implode(' ', $hit['_highlight']['body']), PHP_EOL;
}
```

The constructor signature is:

```php
new GastonClient(
    string $token = null,          // falls back to GASTON_API_TOKEN
    float  $timeout = 30.0,         // ordinary requests, seconds (0 = no limit)
    float  $uploadTimeout = 600.0,  // file-upload endpoint, seconds
    float  $connectTimeout = 10.0,  // connection timeout, seconds
    HttpClientInterface $httpClient = null
);
```

### Configuration

Generate an API token in the Gaston app under
[Settings -> API](https://www.gaston.live/user/settings/api/en). Full endpoint
documentation is available at <https://www.gaston.live/en/api>.

The token can be supplied directly or via the `GASTON_API_TOKEN` environment
variable:

| Argument | Environment variable | Default    |
|----------|----------------------|------------|
| `$token` | `GASTON_API_TOKEN`   | (required) |

```php
// Uses GASTON_API_TOKEN from the environment
$client = new GastonClient();
```

### Timeouts

Ordinary requests use a 30s timeout. The file upload in `transcribe()` can take
minutes for large files, so it uses a separate, more generous `$uploadTimeout`
(default 600s). All timeouts are in seconds; pass `0` to wait indefinitely.

```php
// Customise the defaults for all calls
$client = new GastonClient('gapi-...', 30.0, 1800.0); // up to 30 min uploads

// Or override per call (e.g. no timeout for a very large file)
$client->transcribe('huge-recording.mp4', null, null, null, 0.0);
```

`transcribe()` accepts either a file path or an open stream resource:

```php
$fh = fopen('interview.mp4', 'rb');
$client->transcribe($fh, 'en');
```

## Directories

```php
$folder = $client->createDirectory('Podcasts');
$client->updateDirectory($folder->id, 'Podcast archive');
$client->moveMedia('me...', $folder->id);
$tree = $client->directoryTree();
$client->deleteDirectory($folder->id);
```

## Search

`$client->search($query, $from = 0, $max = 50, $dirIds = null, $lang = null, $mediaId = null)`
runs a full-text search over every sentence in your transcribed media.

### Query syntax

The query supports a subset of the Lucene `query_string` syntax:

| Feature           | Example                  | Notes                                    |
|-------------------|--------------------------|------------------------------------------|
| Boolean `AND`     | `cats AND dogs`          | both terms must appear                   |
| Boolean `OR`      | `cats OR dogs`           | either term                              |
| Boolean `NOT`     | `cats NOT dogs`          | exclude a term                           |
| Grouping          | `(cats OR dogs) AND vet` | combine operators with parentheses       |
| Exact phrase      | `"climate change"`       | quoted terms match as a phrase           |
| Trailing wildcard | `transcri*`              | matches `transcribe`, `transcription`... |

Leading wildcards (`*tion`), field selectors, fuzzy (`~`), boosts (`^`) and
ranges are not supported and are stripped server-side. Queries must be at least
3 characters.

```php
$results = $client->search('(invoice OR receipt) AND "due date" NOT draft');
```

### Filtering and pagination

```php
// Search within a single directory
$client->search('budget', 0, 50, [42]);

// Search across several directories
$client->search('budget', 0, 50, [42, 43, 7]);

// Restrict to one language, and page through results
$page2 = $client->search('budget', 50, 50, null, 'en');

// Search within a single media
$client->search('budget', 0, 50, null, null, 'me...');
```

### Reading results

`search()` returns a `SearchResults` object. Iterate it for hits, or read
`->total` for the overall match count. Each hit is an array with:

- `_sentence` - the matched sentence plus its `media` metadata (id, title,
  duration, directory, thumbnail, file, originUrl).
- `_highlight` - matched fragments with the hit terms wrapped in
  `<hlt>...</hlt>` tags.

```php
$results = $client->search('climate change', 0, 20);
echo 'total matches: ', $results->total, PHP_EOL;
foreach ($results as $hit) {
    $sentence = $hit['_sentence'];
    echo $sentence['media']['title'], ' | ', implode(' ', $hit['_highlight']['body']), PHP_EOL;
}
```

## Error handling

All failures throw a subclass of `GastonException`:

```php
use StreamsSro\Gaston\Exception\AuthenticationException;
use StreamsSro\Gaston\Exception\NotFoundException;
use StreamsSro\Gaston\Exception\RateLimitException;

try {
    $client->transcribe('clip.mp4');
} catch (RateLimitException $e) {
    echo 'File limit reached';
} catch (AuthenticationException $e) {
    echo 'Bad token / disabled account';
} catch (NotFoundException $e) {
    echo 'Not found: ', $e->getMessage();
}
```

| Exception                  | Trigger                                        |
|----------------------------|-------------------------------------------------|
| `AuthenticationException`  | HTTP 403, invalid token / disabled user          |
| `BadRequestException`      | HTTP 400, invalid parameters                     |
| `NotFoundException`        | HTTP 404, resource not found                     |
| `RateLimitException`       | HTTP 429, usage limit exceeded                   |
| `ExternalServiceException` | HTTP 502, an external dependency failed upstream |
| `GastonApiException`       | any other API error                              |

Every API exception carries `->getStatusCode()`, `->getMessage()`,
`->getDetails()` and the raw `->getPayload()`. `GastonException` is the base
class for all of the above (including transport-level failures).

## Supported languages

```php
use StreamsSro\Gaston\Languages;

Languages::SUPPORTED;                  // transcription source languages
Languages::translationLanguages();     // available translation targets
Languages::isSupported('en');          // bool
Languages::isTranslationTarget('de');  // bool
```

## Development

The test suite uses PHPUnit (a dev-only dependency; the library itself has none):

```bash
composer install
composer test
```

## License

MIT - see [LICENSE](LICENSE).