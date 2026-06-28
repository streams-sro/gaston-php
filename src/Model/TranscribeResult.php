<?php

namespace StreamsSro\Gaston\Model;

/**
 * Result of a transcription (or diarization) request (id + state).
 */
class TranscribeResult
{
    /** @var string|null */
    public $id;

    /** @var string|null */
    public $state;

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
        $self->state = isset($data['state']) ? $data['state'] : null;
        $self->raw = $data;
        return $self;
    }
}