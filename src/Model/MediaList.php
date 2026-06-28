<?php

namespace StreamsSro\Gaston\Model;

/**
 * A page of media items (GET /media/list).
 *
 * Iterable and countable over hydrated {@see Media} objects, so the items have
 * the same shape as the result of {@see GastonClient::getMedia()} (list items
 * simply carry no sentences). The original decoded payload is still available
 * via {@see $raw}.
 */
class MediaList implements \IteratorAggregate, \Countable
{
    /** @var Media[] */
    public $media;

    /** @var int */
    public $total;

    /** @var int */
    public $pages;

    /** @var array The original decoded payload. */
    public $raw;

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $self = new self();
        $media = array();
        if (!empty($data['media'])) {
            foreach ($data['media'] as $item) {
                $media[] = Media::fromArray($item);
            }
        }
        $self->media = $media;
        $self->total = isset($data['total']) ? (int) $data['total'] : 0;
        $self->pages = isset($data['pages']) ? (int) $data['pages'] : 0;
        $self->raw = $data;
        return $self;
    }

    /**
     * @return \ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->media);
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->media);
    }
}
