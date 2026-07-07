# Changelog

All notable changes to this project are documented here. This project follows
[Semantic Versioning](https://semver.org/).

## [0.6.0] - 2026-07-07

### Added
- `alignTranslation()` for word-level alignment of a translation against
  source timestamps, returning `AlignTranslationResult`. Progress is
  surfaced via the new `Media::$translationAlignment` field.
- `Media::$directoryId`, populated when the API includes `directory_id`
  (currently only on `listMedia()`/`directoryTree()` results, not
  `getMedia()`).

### Changed
- `moveMedia()` now returns a `Media` object, matching `getMedia()`'s shape.

## [0.5.0] - 2026-07-04

### Added
- `search()` accepts an optional `$mediaId` parameter to restrict full-text
  search to a single media.
- `ExternalServiceException`, thrown on HTTP 502 when an upstream dependency
  (e.g. fetching/downloading a video URL) fails.

## [0.4.0] - 2026-06-28

First release of the PHP client, ported from the Python client. The version is
aligned with the Python `gaston` package.

### Added
- `GastonClient` covering the full Gaston API: user, media (transcribe, upload,
  transcribe-url, translate, diarize), directories and sentence search.
- Typed response models (`User`, `Usage`, `Media`, `MediaList`, `Sentence`,
  `Directory`, `TranscribeResult`, `TranslateResult`, `SearchResults`).
- Exception hierarchy (`GastonException`, `GastonApiException`,
  `AuthenticationException`, `BadRequestException`, `NotFoundException`,
  `RateLimitException`) thrown from API errors.
- Separate, generous upload timeout for the file-upload endpoint.
- Pluggable HTTP transport (`HttpClientInterface`) with a dependency-free
  cURL implementation (`CurlHttpClient`).
- `Sentence::getText()` resolves the sentence text from the `text`, `sentence`
  or `body` field (GET /media returns it under `body`).
- `MediaList` yields hydrated `Media` objects, so list items have the same
  shape as `getMedia()`.
- Runs on PHP 7.0+ with only the `curl` and `json` extensions; no third-party
  runtime dependencies.

[0.6.0]: https://github.com/streams-sro/gaston-php/releases/tag/v0.6.0
[0.5.0]: https://github.com/streams-sro/gaston-php/releases/tag/v0.5.0
[0.4.0]: https://github.com/streams-sro/gaston-php/releases/tag/v0.4.0