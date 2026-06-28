<?php

namespace StreamsSro\Gaston\Model;

/**
 * A directory in the user's library.
 */
class Directory
{
    /** @var int|null */
    public $id;

    /** @var string|null */
    public $title;

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
        $self->raw = $data;
        return $self;
    }
}