<?php

namespace StreamsSro\Gaston\Tests;

use PHPUnit\Framework\TestCase;
use StreamsSro\Gaston\Exception\AuthenticationException;
use StreamsSro\Gaston\Exception\BadRequestException;
use StreamsSro\Gaston\Exception\ExternalServiceException;
use StreamsSro\Gaston\Exception\GastonApiException;
use StreamsSro\Gaston\Exception\GastonException;
use StreamsSro\Gaston\Exception\RateLimitException;
use StreamsSro\Gaston\GastonClient;

/**
 * Tests for GastonClient using a fake HTTP transport.
 *
 * Mirrors the Python client's test suite.
 */
class GastonClientTest extends TestCase
{
    /** @var FakeHttpClient */
    private $http;

    /** @var GastonClient */
    private $client;

    protected function setUp(): void
    {
        $this->http = new FakeHttpClient();
        $this->client = new GastonClient('gapi-test', 30.0, 600.0, 10.0, $this->http);
    }

    public function testMe()
    {
        $this->http->queueJson(array(
            'id' => 'u1',
            'email' => 'a@b.c',
            'enabled' => true,
            'usage' => array('filesLeft' => 7),
        ));

        $me = $this->client->me();

        $this->assertSame('u1', $me->id);
        $this->assertSame('a@b.c', $me->email);
        $this->assertTrue($me->enabled);
        $this->assertSame(7, $me->usage->filesLeft);

        $call = $this->http->lastCall();
        $this->assertSame('gapi-test', $call['headers']['token']);
        $this->assertStringEndsWith('/user/me', $call['url']);
    }

    public function testListMediaSkipsNullParams()
    {
        $this->http->queueJson(array(
            'media' => array(array('id' => 'm1')),
            'total' => 1,
            'pages' => 1,
        ));

        $result = $this->client->listMedia(2);

        $this->assertCount(1, $result);
        $this->assertSame(1, $result->total);
        $items = iterator_to_array($result);
        // List items are hydrated Media objects, like getMedia().
        $this->assertInstanceOf(\StreamsSro\Gaston\Model\Media::class, $items[0]);
        $this->assertSame('m1', $items[0]->id);

        $url = $this->http->lastCall()['url'];
        $this->assertStringNotContainsString('dir_id', $url);
        $this->assertStringContainsString('page=2', $url);
    }

    public function testGetMediaParsesSentences()
    {
        $this->http->queueJson(array(
            'id' => 'm1',
            'title' => 'T',
            'state' => 'transcribed',
            'available_languages' => array('en' => 100),
            'sentences' => array(array('id' => 1, 'text' => 'hi', 'speaker' => 'A')),
            'diarized_sentences' => array(),
        ));

        $media = $this->client->getMedia('m1', 'en');

        $this->assertSame('T', $media->title);
        $this->assertSame('hi', $media->sentences[0]->getText());
        $this->assertSame('A', $media->sentences[0]->speaker);
        $this->assertSame(array('en' => 100), $media->availableLanguages);
    }

    public function testGetMediaSentenceTextFromBodyField()
    {
        // The live GET /media endpoint returns sentence text under "body".
        $this->http->queueJson(array(
            'id' => 'm1',
            'sentences' => array(array('id' => 1, 'body' => 'hello there', 'speaker' => 2)),
        ));

        $media = $this->client->getMedia('m1');

        $this->assertSame('hello there', $media->sentences[0]->getText());
    }

    public function testTranscribeFromStream()
    {
        $this->http->queueJson(array('id' => 'me123', 'state' => 'uploaded'));

        $fh = fopen('php://memory', 'r+');
        fwrite($fh, 'fake audio');
        rewind($fh);

        $result = $this->client->transcribe($fh, null, null, 'clip');

        $this->assertSame('me123', $result->id);
        $this->assertSame('uploaded', $result->state);

        $call = $this->http->lastCall();
        $this->assertSame('POST', $call['method']);
        $this->assertNotNull($call['upload']);
        $this->assertStringContainsString('title=clip', $call['url']);
        // The generous upload timeout is used, not the ordinary 30s one.
        $this->assertSame(600.0, $call['timeout']);
    }

    public function testTranscribeRejectsBadLang()
    {
        $this->expectException(BadRequestException::class);
        $fh = fopen('php://memory', 'r+');
        $this->client->transcribe($fh, 'zz');
    }

    public function testTranslateRejectsBadLang()
    {
        $this->expectException(BadRequestException::class);
        $this->client->translate('m1', 'zz');
    }

    public function testTranslateLowercasesTarget()
    {
        $this->http->queueJson(array(
            'id' => 'm1',
            'available_languages' => array('en' => 100, 'de' => 0),
        ));

        $result = $this->client->translate('m1', 'DE');

        $this->assertSame(0, $result->availableLanguages['de']);
        $this->assertStringContainsString('target_lang=de', $this->http->lastCall()['url']);
    }

    public function testSearchBuildsParams()
    {
        $this->http->queueJson(array(
            'results' => array(
                array('_sentence' => array('body' => 'a'), '_highlight' => array('body' => array('<hlt>a</hlt>'))),
                array('_sentence' => array('body' => 'b'), '_highlight' => array('body' => array('<hlt>b</hlt>'))),
            ),
            'total' => array('value' => 2, 'relation' => 'eq'),
        ));

        $results = $this->client->search('hello world', 0, 10, array(1, 2), 'en');

        $this->assertCount(2, $results);
        $this->assertSame(2, $results->total);
        $this->assertSame('a', $results->results[0]['_sentence']['body']);

        $url = $this->http->lastCall()['url'];
        $this->assertStringContainsString('_from=0', $url);
        $this->assertStringContainsString('_max=10', $url);
        $this->assertStringContainsString('dir_ids=1', $url);
        $this->assertStringContainsString('dir_ids=2', $url);
    }

    public function testSearchByMediaId()
    {
        $this->http->queueJson(array(
            'results' => array(),
            'total' => array('value' => 0, 'relation' => 'eq'),
        ));

        $this->client->search('hello world', 0, 10, null, null, 'me...');

        $url = $this->http->lastCall()['url'];
        $this->assertStringContainsString('media_id=me...', $url);
    }

    public function testSearchTooShort()
    {
        $this->expectException(BadRequestException::class);
        $this->client->search('hi');
    }

    public function testAuthErrorFromStatus()
    {
        $this->http->queueJson(array('error' => 'Token is invalid or user is disabled'), 403);
        $this->expectException(AuthenticationException::class);
        $this->client->me();
    }

    public function testRateLimitError()
    {
        $this->http->queueJson(array('error' => 'File limit reached'), 429);
        $this->expectException(RateLimitException::class);
        $fh = fopen('php://memory', 'r+');
        $this->client->transcribe($fh);
    }

    public function testExternalServiceErrorFromStatus()
    {
        $this->http->queueJson(array('error' => 'Failed to fetch URL info'), 502);
        $this->expectException(ExternalServiceException::class);
        $this->client->transcribeUrl('http://x');
    }

    public function testErrorKeyOn200Response()
    {
        // The API sometimes returns an error body with a 200 status.
        $this->http->queueJson(array('error' => "This URL isn't supported or may be private."), 200);
        try {
            $this->client->transcribeUrl('http://x');
            $this->fail('Expected a GastonApiException.');
        } catch (GastonApiException $e) {
            $this->assertStringContainsString('supported', $e->getMessage());
        }
    }

    public function testDeleteDirectoryReturnsBool()
    {
        $this->http->queueJson(array('result' => true));
        $this->assertTrue($this->client->deleteDirectory(5));
    }

    public function testRequiresToken()
    {
        $previous = getenv('GASTON_API_TOKEN');
        putenv('GASTON_API_TOKEN');
        try {
            $this->expectException(GastonException::class);
            new GastonClient();
        } finally {
            if ($previous !== false) {
                putenv('GASTON_API_TOKEN=' . $previous);
            }
        }
    }

    public function testDefaultBaseUrl()
    {
        $previousOverride = getenv(GastonClient::BASE_URL_OVERRIDE_ENV);
        putenv(GastonClient::BASE_URL_OVERRIDE_ENV);
        try {
            $http = new FakeHttpClient();
            $http->queueJson(array('id' => 'u1'));
            $client = new GastonClient('gapi-test', 30.0, 600.0, 10.0, $http);
            $client->me();
            $this->assertStringStartsWith('https://api.gaston.live/', $http->lastCall()['url']);
        } finally {
            if ($previousOverride !== false) {
                putenv(GastonClient::BASE_URL_OVERRIDE_ENV . '=' . $previousOverride);
            }
        }
    }
}