<?php

declare(strict_types=1);

namespace JsonRepair;

use JsonRepair\Regular\JsonRepair;
use JsonRepair\Streaming\StreamingJsonRepair;
use JsonRepair\Utils\JSONRepairError;

/**
 * Repair a string containing an invalid JSON document
 *
 * Example:
 *   $json = "{name: 'John'}";
 *   $repaired = jsonrepair($json);
 *   echo $repaired; // {"name": "John"}
 *
 * @throws JSONRepairError When JSON cannot be repaired
 */
function jsonrepair(string $text): string
{
    $repairer = new JsonRepair();
    return $repairer->repair($text);
}

/**
 * Repair JSON from a stream in chunks (memory efficient for large documents)
 *
 * Example:
 *   $stream = fopen('large.json', 'r');
 *   foreach (jsonrepairStream($stream) as $chunk) {
 *       echo $chunk;
 *   }
 *   fclose($stream);
 *
 * @param resource|iterable<string> $input Stream resource or iterable of chunks
 * @param int $chunkSize Size of chunks to process
 * @return \Generator<string> Yields repaired JSON chunks
 * @throws JSONRepairError When JSON cannot be repaired
 */
function jsonrepairStream($input, int $chunkSize = 65536): \Generator
{
    $repairer = new StreamingJsonRepair($chunkSize);
    yield from $repairer->repairStream($input);
}

/**
 * Repair JSON from a stream and return the complete result as a string
 *
 * Example:
 *   $stream = fopen('data.json', 'r');
 *   $repaired = jsonrepairStreamToString($stream);
 *   fclose($stream);
 *
 * @param resource|iterable<string> $input Stream resource or iterable of chunks
 * @param int $chunkSize Size of chunks to process
 * @return string Complete repaired JSON
 * @throws JSONRepairError When JSON cannot be repaired
 */
function jsonrepairStreamToString($input, int $chunkSize = 65536): string
{
    $repairer = new StreamingJsonRepair($chunkSize);
    return $repairer->repairStreamToString($input);
}
