<?php

namespace StreamsSro\Gaston\Model;

/**
 * An AI-generated text of a digest (its summary, or the answer to its
 * question) with the citations it refers to.
 */
class DigestText
{
    /** @var string|null The text; null when generating it failed (see {@see $error}). */
    public $text;

    /** @var DigestCitation[] */
    public $citations;

    /** @var string|null Why the text could not be generated, if it failed. */
    public $error;

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $self = new self();
        $self->text = isset($data['text']) ? $data['text'] : null;

        $citations = array();
        if (!empty($data['citations'])) {
            foreach ($data['citations'] as $citation) {
                $citations[] = DigestCitation::fromArray($citation);
            }
        }
        $self->citations = $citations;
        $self->error = isset($data['error']) ? $data['error'] : null;
        return $self;
    }
}
