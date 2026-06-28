<?php

namespace StreamsSro\Gaston\Model;

/**
 * A single transcribed (or translated) sentence.
 */
class Sentence
{
    /** @var int|null */
    public $id;

    /** @var mixed|null */
    public $speaker;

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
        $self->speaker = isset($data['speaker']) ? $data['speaker'] : null;
        $self->raw = $data;
        return $self;
    }

    /**
     * The sentence text.
     *
     * Different endpoints name the field differently: GET /media returns it as
     * "body" (same as search hits), while other shapes use "text" or
     * "sentence". The first non-empty one wins.
     *
     * @return string|null
     */
    public function getText()
    {
        foreach (array('text', 'sentence', 'body') as $key) {
            if (isset($this->raw[$key]) && $this->raw[$key] !== '') {
                return $this->raw[$key];
            }
        }
        return null;
    }
}