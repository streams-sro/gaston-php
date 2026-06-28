# Changelog

All notable changes to this project are documented here. This project follows
[Semantic Versioning](https://semver.org/).

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

[0.4.0]: https://github.com/streams-sro/gaston-php/releases/tag/v0.4.0