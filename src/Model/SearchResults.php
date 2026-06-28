<?php

namespace StreamsSro\Gaston\Model;

/**
 * Results of a sentence search (GET /sentence/search).
 *
 * Each entry in {@see $results} is an array with two keys: "_sentence" (the
 * matched sentence and its "media" metadata) and "_highlight" (the matched
 * fragments with <hlt>...</hlt> markers around the hit terms).
 *
 * Iterable and countable over the hits.
 */
class SearchResults implements \IteratorAggregate, \Countable
{
    /** @var array[] */
    public $results;

    /** @var int|null */
    public $total;

    /** @var array The original decoded payload. */
    public $raw;

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $self = new self();
        $self->results = !empty($data['results']) ? $data['results'] : array();

        // "total" is an Elasticsearch total object: {"value": N, ...}.
        $total = null;
        if (isset($data['total'])) {
            if (is_array($data['total']) && isset($data['total']['value'])) {
                $total = (int) $data['total']['value'];
            } elseif (is_int($data['total'])) {
                $total = $data['total'];
            }
        }
        $self->total = $total;
        $self->raw = $data;
        return $self;
    }

    /**
     * @return \ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->results);
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->results);
    }
}