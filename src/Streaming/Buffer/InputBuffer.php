<?php

declare(strict_types=1);

namespace JsonRepair\Streaming\Buffer;

/**
 * Input buffer for streaming JSON repair
 * Manages incoming chunks of data with efficient memory usage
 */
class InputBuffer
{
    private string $buffer = '';
    private int $offset = 0;
    private int $currentLength = 0;
    private bool $closed = false;

    /**
     * Add a chunk to the buffer
     */
    public function push(string $chunk): void
    {
        $this->buffer .= $chunk;
        $this->currentLength += strlen($chunk);
    }

    /**
     * Flush buffer up to a given position to free memory
     */
    public function flush(int $position): void
    {
        if ($position > $this->currentLength) {
            return;
        }

        $this->buffer = substr($this->buffer, $position - $this->offset);
        $this->offset = $position;
    }

    /**
     * Get character at index
     */
    public function charAt(int $index): string
    {
        $this->ensure($index);
        return $this->buffer[$index - $this->offset] ?? '';
    }

    /**
     * Get character code at index
     */
    public function charCodeAt(int $index): int
    {
        $this->ensure($index);
        $char = $this->buffer[$index - $this->offset] ?? '';
        return $char !== '' ? ord($char) : 0;
    }

    /**
     * Get substring
     */
    public function substring(int $start, int $end): string
    {
        $this->ensure($end - 1); // -1 because end is excluded
        $this->ensure($start);

        return substr($this->buffer, $start - $this->offset, $end - $start);
    }

    /**
     * Get total length (only available after close)
     */
    public function length(): int
    {
        if (!$this->closed) {
            throw new \RuntimeException('Cannot get length: input is not yet closed');
        }

        return $this->currentLength;
    }

    /**
     * Get current length
     */
    public function currentLength(): int
    {
        return $this->currentLength;
    }

    /**
     * Get current buffer size
     */
    public function currentBufferSize(): int
    {
        return strlen($this->buffer);
    }

    /**
     * Check if index is at end of input
     */
    public function isEnd(int $index): bool
    {
        if (!$this->closed) {
            $this->ensure($index);
        }

        return $index >= $this->currentLength;
    }

    /**
     * Mark input as complete (no more chunks will be added)
     */
    public function close(): void
    {
        $this->closed = true;
    }

    private function ensure(int $index): void
    {
        if ($index < $this->offset) {
            throw new \RuntimeException(
                "Index out of range, please configure a larger buffer size " .
                "(index: {$index}, offset: {$this->offset})"
            );
        }

        if ($index >= $this->currentLength) {
            if (!$this->closed) {
                throw new \RuntimeException("Index out of range (index: {$index})");
            }
        }
    }
}
