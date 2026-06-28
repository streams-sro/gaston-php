<?php

namespace StreamsSro\Gaston\Model;

/**
 * Result of a translation request.
 */
class TranslateResult
{
    /** @var string|null */
    public $id;

    /** @var array */
    public $availableLanguages;

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
        $self->availableLanguages = !empty($data['available_languages']) ? $data['available_languages'] : array();
        $self->raw = $data;
        return $self;
    }
}