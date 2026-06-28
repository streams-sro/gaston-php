<?php

namespace StreamsSro\Gaston\Model;

/**
 * Full media detail (GET /media).
 */
class Media
{
    /** @var string|null */
    public $id;

    /** @var string|null */
    public $title;

    /** @var string|null */
    public $state;

    /** @var string|null */
    public $origin;

    /** @var string|null */
    public $originUrl;

    /** @var string|null */
    public $file;

    /** @var string|null */
    public $error;

    /** @var string|null */
    public $thumbnail;

    /** @var int|null */
    public $duration;

    /** @var mixed|null */
    public $publishedAt;

    /** @var mixed|null */
    public $addedAt;

    /** @var int|null */
    public $transcriptionProgress;

    /** @var int|null */
    public $downloadProgress;

    /** @var string|null */
    public $language;

    /** @var array */
    public $availableLanguages;

    /** @var Sentence[] */
    public $sentences;

    /** @var array */
    public $diarizedSentences;

    /** @var array The original decoded payload. */
    public $raw;

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $self = new self();
        $self->id = isset($data['id']) ? $data['id'] : null;
        $self->title = isset($data['title']) ? $data['title'] : null;
        $self->state = isset($data['state']) ? $data['state'] : null;
        $self->origin = isset($data['origin']) ? $data['origin'] : null;
        $self->originUrl = isset($data['originUrl']) ? $data['originUrl'] : null;
        $self->file = isset($data['file']) ? $data['file'] : null;
        $self->error = isset($data['error']) ? $data['error'] : null;
        $self->thumbnail = isset($data['thumbnail']) ? $data['thumbnail'] : null;
        $self->duration = isset($data['duration']) ? $data['duration'] : null;
        $self->publishedAt = isset($data['published_at']) ? $data['published_at'] : null;
        $self->addedAt = isset($data['added_at']) ? $data['added_at'] : null;
        $self->transcriptionProgress = isset($data['transcription_progress']) ? $data['transcription_progress'] : null;
        $self->downloadProgress = isset($data['download_progress']) ? $data['download_progress'] : null;
        $self->language = isset($data['language']) ? $data['language'] : null;
        $self->availableLanguages = !empty($data['available_languages']) ? $data['available_languages'] : array();

        $sentences = array();
        if (!empty($data['sentences'])) {
            foreach ($data['sentences'] as $sentence) {
                $sentences[] = Sentence::fromArray($sentence);
            }
        }
        $self->sentences = $sentences;
        $self->diarizedSentences = !empty($data['diarized_sentences']) ? $data['diarized_sentences'] : array();
        $self->raw = $data;
        return $self;
    }
}