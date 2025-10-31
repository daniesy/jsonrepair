<?php

require_once __DIR__ . '/../vendor/autoload.php';

use function JsonRepair\jsonrepairStream;
use function JsonRepair\jsonrepairStreamToString;

echo "=== JSON Repair Streaming Examples ===\n\n";

// Example 1: Repair from memory stream
echo "Example 1: Memory stream\n";
$stream = fopen('php://memory', 'r+');
fwrite($stream, "{name: 'John', age: 30, city: 'New York'}");
rewind($stream);

$repaired = jsonrepairStreamToString($stream);
echo "Original: {name: 'John', age: 30, city: 'New York'}\n";
echo "Repaired: $repaired\n\n";
fclose($stream);

// Example 2: Process chunks from array
echo "Example 2: Array of chunks\n";
$chunks = [
    '{users: [',
    '{name: "Alice"},',
    '{name: "Bob"},',
    '{name: "Carol"}',
    ']}'
];

$result = '';
foreach (jsonrepairStream($chunks) as $chunk) {
    $result .= $chunk;
}
echo "Repaired: $result\n\n";

// Example 3: Newline-delimited JSON (NDJSON)
echo "Example 3: Newline-delimited JSON\n";
$stream = fopen('php://memory', 'r+');
fwrite($stream, "{id: 1, name: 'Product A'}\n");
fwrite($stream, "{id: 2, name: 'Product B'}\n");
fwrite($stream, "{id: 3, name: 'Product C'}\n");
rewind($stream);

$repaired = jsonrepairStreamToString($stream);
echo "Repaired:\n";
// Parse each line
foreach (explode('}{', $repaired) as $i => $line) {
    if ($i > 0) $line = '{' . $line;
    if ($i < 2) $line .= '}';
    $obj = json_decode($line);
    echo "  Line " . ($i + 1) . ": ID={$obj->id}, Name={$obj->name}\n";
}
echo "\n";
fclose($stream);

// Example 4: Large data simulation
echo "Example 4: Large dataset (1000 records)\n";
$largeData = [];
for ($i = 1; $i <= 1000; $i++) {
    $largeData[] = ['id' => $i, 'value' => "Item $i"];
}
$json = json_encode($largeData);

$stream = fopen('php://memory', 'r+');
fwrite($stream, $json);
rewind($stream);

$repaired = jsonrepairStreamToString($stream);
$decoded = json_decode($repaired, true);
echo "Processed " . count($decoded) . " records\n";
echo "First record: " . json_encode($decoded[0]) . "\n";
echo "Last record: " . json_encode($decoded[999]) . "\n\n";
fclose($stream);

// Example 5: Streaming with custom chunk size
echo "Example 5: Custom chunk size\n";
use JsonRepair\Streaming\StreamingJsonRepair;

$repairer = new StreamingJsonRepair(16); // 16-byte chunks
$chunks = ["{data: [1, 2, 3, 4, 5]}"];

$result = '';
$chunkCount = 0;
foreach ($repairer->repairStream($chunks) as $chunk) {
    $result .= $chunk;
    $chunkCount++;
}
echo "Result: $result\n";
echo "Processed in $chunkCount chunk(s)\n\n";

echo "=== All examples completed successfully! ===\n";
