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
 * Example with beautify:
 *   $json = '{"message": "He said "hello" to me"}';
 *   $repaired = jsonrepair($json, true);
 *   echo $repaired; // {"message": "He said "hello" to me"}
 *
 * @param string $text The JSON text to repair
 * @param bool $beautify Whether to replace inner quotes with " instead of escaping them
 * @throws JSONRepairError When JSON cannot be repaired
 */
function jsonrepair(string $text, bool $beautify = false): string
{
    $repairer = new JsonRepair();
    return $repairer->repair($text, $beautify);
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
 * @param bool $beautify Whether to replace inner quotes with " instead of escaping them
 * @return \Generator<string> Yields repaired JSON chunks
 * @throws JSONRepairError When JSON cannot be repaired
 */
function jsonrepairStream($input, int $chunkSize = 65536, bool $beautify = false): \Generator
{
    $repairer = new StreamingJsonRepair($chunkSize, $beautify);
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
 * @param bool $beautify Whether to replace inner quotes with " instead of escaping them
 * @return string Complete repaired JSON
 * @throws JSONRepairError When JSON cannot be repaired
 */
function jsonrepairStreamToString($input, int $chunkSize = 65536, bool $beautify = false): string
{
    $repairer = new StreamingJsonRepair($chunkSize, $beautify);
    return $repairer->repairStreamToString($input);
}
