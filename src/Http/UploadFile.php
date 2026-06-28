<?php

namespace StreamsSro\Gaston\Http;

/**
 * Describes a single multipart file part to upload.
 *
 * It is created either from a filesystem path (streamed by cURL, low memory)
 * or from an in-memory string of bytes.
 */
class UploadFile
{
    /** @var string|null Path on disk, or null when created from contents. */
    public $path;

    /** @var string|null In-memory contents, or null when created from a path. */
    public $contents;

    /** @var string The form field name. */
    public $field;

    /** @var string The filename sent to the server. */
    public $filename;

    /**
     * @param string|null $path
     * @param string|null $contents
     * @param string      $field
     * @param string      $filename
     */
    private function __construct($path, $contents, $field, $filename)
    {
        $this->path = $path;
        $this->contents = $contents;
        $this->field = $field;
        $this->filename = $filename;
    }

    /**
     * @param string      $path
     * @param string      $field
     * @param string|null $filename Defaults to the basename of $path.
     * @return self
     */
    public static function fromPath($path, $field = 'file', $filename = null)
    {
        if ($filename === null) {
            $filename = basename($path);
        }
        return new self($path, null, $field, $filename);
    }

    /**
     * @param string $contents
     * @param string $field
     * @param string $filename
     * @return self
     */
    public static function fromContents($contents, $field = 'file', $filename = 'file')
    {
        return new self(null, $contents, $field, $filename);
    }
}