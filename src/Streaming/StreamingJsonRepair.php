<?php

declare(strict_types=1);

namespace JsonRepair\Streaming;

use JsonRepair\Regular\JsonRepair;
use JsonRepair\Utils\JSONRepairError;

/**
 * Streaming JSON repair implementation
 * Processes JSON in chunks for memory-efficient handling of large documents
 */
class StreamingJsonRepair
{
    private JsonRepair $regularRepair;
    private string $buffer = '';
    private bool $inString = false;
    private int $braceDepth = 0;
    private int $bracketDepth = 0;

    public function __construct(
        private readonly int $chunkSize = 65536
    ) {
        $this->regularRepair = new JsonRepair();
    }

    /**
     * Repair JSON from a stream
     *
     * @param resource|iterable<string> $input Stream resource or iterable of chunks
     * @return \Generator<string> Yields repaired JSON chunks
     * @throws JSONRepairError
     */
    public function repairStream($input): \Generator
    {
        $this->buffer = '';
        $this->inString = false;
        $this->braceDepth = 0;
        $this->bracketDepth = 0;

        if (is_resource($input)) {
            while (!feof($input)) {
                $chunk = fread($input, $this->chunkSize);
                if ($chunk === false) {
                    break;
                }
                yield from $this->processChunk($chunk);
            }
        } elseif (is_iterable($input)) {
            foreach ($input as $chunk) {
                yield from $this->processChunk($chunk);
            }
        } else {
            throw new \InvalidArgumentException('Input must be a stream resource or iterable');
        }

        // Process any remaining buffer
        if ($this->buffer !== '') {
            yield from $this->flushBuffer();
        }
    }

    /**
     * Process a chunk of input
     *
     * @return \Generator<string>
     */
    private function processChunk(string $chunk): \Generator
    {
        $this->buffer .= $chunk;

        // Try to extract complete JSON values
        while ($completeJson = $this->extractCompleteJson()) {
            $repaired = $this->regularRepair->repair($completeJson);
            yield $repaired;
        }
    }

    /**
     * Extract a complete JSON value from the buffer
     * Returns the JSON string and removes it from buffer, or null if incomplete
     */
    private function extractCompleteJson(): ?string
    {
        if ($this->buffer === '') {
            return null;
        }

        $length = strlen($this->buffer);
        $this->braceDepth = 0;
        $this->bracketDepth = 0;
        $this->inString = false;
        $escaped = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $this->buffer[$i];

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($char === '\\' && $this->inString) {
                $escaped = true;
                continue;
            }

            if ($char === '"' && !$escaped) {
                $this->inString = !$this->inString;
                continue;
            }

            if (!$this->inString) {
                if ($char === '{') {
                    $this->braceDepth++;
                } elseif ($char === '}') {
                    $this->braceDepth--;
                } elseif ($char === '[') {
                    $this->bracketDepth++;
                } elseif ($char === ']') {
                    $this->bracketDepth--;
                }

                // Check if we have a complete JSON value
                if ($this->braceDepth === 0 && $this->bracketDepth === 0 && $i > 0) {
                    // Extract complete JSON
                    $json = substr($this->buffer, 0, $i + 1);
                    $this->buffer = substr($this->buffer, $i + 1);

                    // Skip whitespace/newlines after extracted JSON
                    $this->buffer = ltrim($this->buffer);

                    return $json;
                }
            }
        }

        // Check if we have a simple value (not object/array)
        if ($this->braceDepth === 0 && $this->bracketDepth === 0) {
            $trimmed = trim($this->buffer);
            if ($trimmed !== '' && !str_starts_with($trimmed, '{') && !str_starts_with($trimmed, '[')) {
                // Could be a primitive value, check for complete value
                if (preg_match('/^[^,\n]+/', $trimmed, $matches)) {
                    $value = $matches[0];
                    $this->buffer = substr($this->buffer, strlen($value));
                    $this->buffer = ltrim($this->buffer);
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Flush remaining buffer at end of stream
     *
     * @return \Generator<string>
     */
    private function flushBuffer(): \Generator
    {
        if ($this->buffer !== '') {
            $repaired = $this->regularRepair->repair($this->buffer);
            yield $repaired;
            $this->buffer = '';
        }
    }

    /**
     * Convenience method to repair a stream and return complete result
     *
     * @param resource|iterable<string> $input
     * @return string
     * @throws JSONRepairError
     */
    public function repairStreamToString($input): string
    {
        $result = '';
        foreach ($this->repairStream($input) as $chunk) {
            $result .= $chunk;
        }
        return $result;
    }
}
