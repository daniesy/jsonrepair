<?php

declare(strict_types=1);

namespace JsonRepair\Utils;

use Exception;

/**
 * Exception thrown when JSON repair encounters an unrecoverable error
 */
class JSONRepairError extends Exception
{
    public function __construct(
        string $message,
        public readonly int $position
    ) {
        parent::__construct("{$message} at position {$position}");
    }
}
