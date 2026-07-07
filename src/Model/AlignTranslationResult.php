<?php

namespace StreamsSro\Gaston\Model;

/**
 * Result of a word-level translation alignment request.
 */
class AlignTranslationResult
{
    /** @var string|null */
    public $id;

    /** @var string|null */
    public $targetLang;

    /** @var int|null */
    public $alignmentProgress;

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
        $self->targetLang = isset($data['target_lang']) ? $data['target_lang'] : null;
        $self->alignmentProgress = isset($data['alignment_progress']) ? $data['alignment_progress'] : null;
        $self->raw = $data;
        return $self;
    }
}