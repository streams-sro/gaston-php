<?php

namespace StreamsSro\Gaston\Model;

/**
 * A citation inside a digest text, pointing at the transcript sentences that
 * support a statement.
 */
class DigestCitation
{
    /** @var string|null The marker as it appears in the text, e.g. "[S12-S15]". */
    public $marker;

    /** @var string|null */
    public $title;

    /** @var int[] Ids of the cited sentences. */
    public $sentenceIds;

    /** @var float|null Start of the first cited sentence, in seconds. */
    public $start;

    /** @var float|null End of the last cited sentence, in seconds. */
    public $end;

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $self = new self();
        $self->marker = isset($data['marker']) ? $data['marker'] : null;
        $self->title = isset($data['title']) ? $data['title'] : null;
        $self->sentenceIds = !empty($data['sentence_ids']) ? $data['sentence_ids'] : array();
        $self->start = isset($data['start']) ? $data['start'] : null;
        $self->end = isset($data['end']) ? $data['end'] : null;
        return $self;
    }
}
