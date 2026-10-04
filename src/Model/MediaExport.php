<?php

namespace StreamsSro\Gaston\Model;

use StreamsSro\Gaston\Exception\GastonException;

/**
 * An exported media file (GET /media/export).
 *
 * Unlike the other models this wraps a file rather than a JSON payload, so
 * there is no $raw array.
 */
class MediaExport
{
    /** @var string The file contents (raw bytes; UTF-8 text for the text, CSV and SRT formats). */
    public $content;

    /** @var string|null The filename suggested by the server, if any. */
    public $filename;

    /** @var string|null The response Content-Type, if any. */
    public $contentType;

    /**
     * @param string      $content
     * @param string|null $filename
     * @param string|null $contentType
     */
    public function __construct($content, $filename = null, $contentType = null)
    {
        $this->content = (string) $content;
        $this->filename = $filename;
        $this->contentType = $contentType;
    }

    /**
     * Write the export to $path.
     *
     * If $path is an existing directory, the file is written inside it using
     * {@see $filename} as suggested by the server.
     *
     * @param string $path
     * @return string The path the file was written to.
     *
     * @throws GastonException if the server suggested no filename for a directory
     *                         target, or the file cannot be written.
     */
    public function save($path)
    {
        $target = $path;
        if (is_dir($path)) {
            if ($this->filename === null) {
                throw new GastonException('The server did not suggest a filename; pass a file path.');
            }
            $target = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $this->filename;
        }
        if (@file_put_contents($target, $this->content) === false) {
            throw new GastonException("Failed to write export to '" . $target . "'.");
        }
        return $target;
    }
}
