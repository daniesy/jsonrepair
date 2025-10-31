<?php

declare(strict_types=1);

namespace JsonRepair\Streaming\Buffer;

use JsonRepair\Utils\StringUtils;

/**
 * Output buffer for streaming JSON repair
 * Manages outgoing chunks with automatic flushing
 */
class OutputBuffer
{
    private string $buffer = '';
    private int $offset = 0;

    public function __construct(
        private readonly \Closure $write,
        private readonly int $chunkSize = 65536,
        private readonly int $bufferSize = 65536
    ) {
    }

    /**
     * Add text to the output buffer
     */
    public function push(string $text): void
    {
        $this->buffer .= $text;
        $this->flushChunks();
    }

    /**
     * Add text to the beginning of buffer
     */
    public function unshift(string $text): void
    {
        if ($this->offset > 0) {
            throw new \RuntimeException('Cannot unshift: start of the output is already flushed from the buffer');
        }

        $this->buffer = $text . $this->buffer;
        $this->flushChunks();
    }

    /**
     * Remove text from buffer
     */
    public function remove(int $start, ?int $end = null): void
    {
        if ($start < $this->offset) {
            throw new \RuntimeException('Cannot remove: start of the output is already flushed from the buffer');
        }

        if ($end !== null) {
            $this->buffer = substr($this->buffer, 0, $start - $this->offset)
                . substr($this->buffer, $end - $this->offset);
        } else {
            $this->buffer = substr($this->buffer, 0, $start - $this->offset);
        }
    }

    /**
     * Insert text at specific position
     */
    public function insertAt(int $index, string $text): void
    {
        if ($index < $this->offset) {
            throw new \RuntimeException('Cannot insert: start of the output is already flushed from the buffer');
        }

        $this->buffer = substr($this->buffer, 0, $index - $this->offset)
            . $text
            . substr($this->buffer, $index - $this->offset);
    }

    /**
     * Get current output length
     */
    public function length(): int
    {
        return $this->offset + strlen($this->buffer);
    }

    /**
     * Flush all remaining output
     */
    public function flush(): void
    {
        $this->flushChunks(0);

        if (strlen($this->buffer) > 0) {
            ($this->write)($this->buffer);
            $this->offset += strlen($this->buffer);
            $this->buffer = '';
        }
    }

    /**
     * Strip last occurrence of text
     */
    public function stripLastOccurrence(string $textToStrip, bool $stripRemainingText = false): void
    {
        $bufferIndex = strrpos($this->buffer, $textToStrip);

        if ($bufferIndex !== false) {
            if ($stripRemainingText) {
                $this->buffer = substr($this->buffer, 0, $bufferIndex);
            } else {
                $this->buffer = substr($this->buffer, 0, $bufferIndex)
                    . substr($this->buffer, $bufferIndex + strlen($textToStrip));
            }
        }
    }

    /**
     * Insert text before last whitespace
     */
    public function insertBeforeLastWhitespace(string $textToInsert): void
    {
        $bufferIndex = strlen($this->buffer);

        if (!StringUtils::isWhitespace($this->buffer, $bufferIndex - 1)) {
            // no trailing whitespaces
            $this->push($textToInsert);
            return;
        }

        while (StringUtils::isWhitespace($this->buffer, $bufferIndex - 1)) {
            $bufferIndex--;
        }

        if ($bufferIndex <= 0) {
            throw new \RuntimeException('Cannot insert: start of the output is already flushed from the buffer');
        }

        $this->buffer = substr($this->buffer, 0, $bufferIndex)
            . $textToInsert
            . substr($this->buffer, $bufferIndex);
        $this->flushChunks();
    }

    /**
     * Check if buffer ends with character (ignoring whitespace)
     */
    public function endsWithIgnoringWhitespace(string $char): bool
    {
        $i = strlen($this->buffer) - 1;

        while ($i > 0) {
            if ($char === $this->buffer[$i]) {
                return true;
            }

            if (!StringUtils::isWhitespace($this->buffer, $i)) {
                return false;
            }

            $i--;
        }

        return false;
    }

    /**
     * Flush chunks when buffer is large enough
     */
    private function flushChunks(int $minSize = null): void
    {
        $minSize ??= $this->bufferSize;

        while (strlen($this->buffer) >= $minSize + $this->chunkSize) {
            $chunk = substr($this->buffer, 0, $this->chunkSize);
            ($this->write)($chunk);
            $this->offset += $this->chunkSize;
            $this->buffer = substr($this->buffer, $this->chunkSize);
        }
    }
}
