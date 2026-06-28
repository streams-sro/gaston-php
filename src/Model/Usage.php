<?php

namespace StreamsSro\Gaston\Model;

/**
 * Account usage information.
 */
class Usage
{
    /** @var int */
    public $filesLeft;

    /** @var array The original decoded payload. */
    public $raw;

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $self = new self();
        $self->filesLeft = isset($data['filesLeft']) ? (int) $data['filesLeft'] : 0;
        $self->raw = $data;
        return $self;
    }
}