<?php

namespace StreamsSro\Gaston\Model;

use StreamsSro\Gaston\Languages;

/**
 * An AI digest of a media item (POST /digest, GET /digest).
 *
 * POST /digest only returns the ids and states; the remaining fields are
 * filled in by GET /digest.
 */
class Digest
{
    /** @var string|null The public id of the media being digested. */
    public $id;

    /** @var string|null State of the media (see the Languages::STATE_* constants). */
    public $state;

    /** @var string|null */
    public $digestId;

    /** @var string|null State of the digest (see the Languages::DIGEST_STATE_* constants). */
    public $digestState;

    /** @var string|null "brief" or "detailed". */
    public $detail;

    /** @var string|null The question asked about the media, if any. */
    public $question;

    /** @var string|null Language of the summary and answer; set once the digest is ready. */
    public $language;

    /** @var int|null Reported while the media is still being transcribed. */
    public $transcriptionProgress;

    /** @var DigestText|null Set once the digest is ready. */
    public $summary;

    /**
     * @var DigestText|null Set once the digest is ready, if a question was asked. If only
     *                      the question failed, its text is null and its error is set.
     */
    public $answer;

    /** @var string|null Why the digest failed, when its state is "error". */
    public $error;

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
        $self->state = isset($data['state']) ? $data['state'] : null;
        $self->digestId = isset($data['digest_id']) ? $data['digest_id'] : null;
        $self->digestState = isset($data['digest_state']) ? $data['digest_state'] : null;
        $self->detail = isset($data['detail']) ? $data['detail'] : null;
        $self->question = isset($data['question']) ? $data['question'] : null;
        $self->language = isset($data['language']) ? $data['language'] : null;
        $self->transcriptionProgress = isset($data['transcription_progress']) ? $data['transcription_progress'] : null;
        $self->summary = isset($data['summary']) && is_array($data['summary'])
            ? DigestText::fromArray($data['summary'])
            : null;
        $self->answer = isset($data['answer']) && is_array($data['answer'])
            ? DigestText::fromArray($data['answer'])
            : null;
        $self->error = isset($data['error']) ? $data['error'] : null;
        $self->raw = $data;
        return $self;
    }

    /**
     * Whether the summary is available. The answer may still have failed; check
     * $answer->error.
     *
     * @return bool
     */
    public function isReady()
    {
        return $this->digestState === Languages::DIGEST_STATE_READY;
    }

    /**
     * Whether the digest failed (see {@see $error}).
     *
     * @return bool
     */
    public function isFailed()
    {
        return $this->digestState === Languages::DIGEST_STATE_ERROR;
    }

    /**
     * Whether the digest reached a final state and polling can stop.
     *
     * @return bool
     */
    public function isFinished()
    {
        return $this->isReady() || $this->isFailed();
    }
}
