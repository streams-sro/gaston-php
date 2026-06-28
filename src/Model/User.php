<?php

namespace StreamsSro\Gaston\Model;

/**
 * The authenticated user (GET /user/me).
 */
class User
{
    /** @var string|null */
    public $id;

    /** @var string|null */
    public $email;

    /** @var bool */
    public $enabled;

    /** @var Usage */
    public $usage;

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
        $self->email = isset($data['email']) ? $data['email'] : null;
        $self->enabled = isset($data['enabled']) ? (bool) $data['enabled'] : false;
        $self->usage = Usage::fromArray(isset($data['usage']) ? $data['usage'] : array());
        $self->raw = $data;
        return $self;
    }
}