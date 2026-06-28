<?php

/**
 * Minimal end-to-end example.
 *
 * Run with:  GASTON_API_TOKEN=gapi-... php examples/quickstart.php path/to/file.mp4
 */

require __DIR__ . '/../vendor/autoload.php';

use StreamsSro\Gaston\Exception\GastonApiException;
use StreamsSro\Gaston\GastonClient;

$client = new GastonClient(); // reads GASTON_API_TOKEN from the environment

try {
    // Who am I + remaining quota.
    $me = $client->me();
    echo $me->email, ' - files left: ', $me->usage->filesLeft, PHP_EOL;

    if ($argc > 1) {
        // Transcribe a local file.
        $result = $client->transcribe($argv[1], 'en', null, 'My interview');
        echo 'Queued ', $result->id, ' (', $result->state, ')', PHP_EOL;

        // Fetch the media item with its sentences.
        $media = $client->getMedia($result->id, 'en');
        foreach ($media->sentences as $sentence) {
            echo $sentence->id, ': ', $sentence->getText(), PHP_EOL;
        }
    }

    // Full text search across the whole library.
    $results = $client->search('climate change', 0, 20);
    echo 'total matches: ', $results->total, PHP_EOL;
    foreach ($results as $hit) {
        echo $hit['_sentence']['media']['title'], ' | ',
            implode(' ', $hit['_highlight']['body']), PHP_EOL;
    }
} catch (GastonApiException $e) {
    fwrite(STDERR, 'API error [' . $e->getStatusCode() . ']: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}