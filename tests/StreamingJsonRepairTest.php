<?php

use function JsonRepair\jsonrepairStream;
use function JsonRepair\jsonrepairStreamToString;
use JsonRepair\Streaming\StreamingJsonRepair;

describe('Streaming JSON Repair', function () {
    test('should repair JSON from a stream', function () {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "{name: 'John'}");
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        expect($result)->toBe('{"name": "John"}');
    });

    test('should repair JSON from array of chunks', function () {
        $chunks = ["{name: ", "'John'}"];

        $result = '';
        foreach (jsonrepairStream($chunks) as $chunk) {
            $result .= $chunk;
        }

        expect($result)->toBe('{"name": "John"}');
    });

    test('should handle newline-delimited JSON', function () {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "{\"a\":1}\n{\"b\":2}\n{\"c\":3}");
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        expect($result)->toBe('{"a":1}{"b":2}{"c":3}');
    });

    test('should handle arrays split across chunks', function () {
        $chunks = ['[1, ', '2, ', '3]'];

        $result = '';
        foreach (jsonrepairStream($chunks) as $chunk) {
            $result .= $chunk;
        }

        expect($result)->toBe('[1, 2, 3]');
    });

    test('should handle objects split across chunks', function () {
        $chunks = ['{\"a\":', '1, \"b\":', '2}'];

        $result = '';
        foreach (jsonrepairStream($chunks) as $chunk) {
            $result .= $chunk;
        }

        expect($result)->toBe('{"a":1, "b":2}');
    });

    test('should repair missing quotes in stream', function () {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "{name: John, age: 30}");
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        expect($result)->toBe('{"name": "John", "age": 30}');
    });

    test('should handle trailing commas in stream', function () {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "[1, 2, 3,]");
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        expect($result)->toBe('[1, 2, 3]');
    });

    test('should handle single quotes in stream', function () {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "{'a': 'b'}");
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        expect($result)->toBe('{"a": "b"}');
    });

    test('should handle comments in stream', function () {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "{/* comment */\"a\": 1}");
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        expect($result)->toBe('{"a": 1}');
    });

    test('should yield multiple chunks when processing', function () {
        $chunks = [
            '{"a": 1}',
            '{"b": 2}',
            '{"c": 3}'
        ];

        $results = [];
        foreach (jsonrepairStream($chunks) as $chunk) {
            $results[] = $chunk;
        }

        expect(count($results))->toBeGreaterThanOrEqual(1);
        expect(implode('', $results))->toBe('{"a": 1}{"b": 2}{"c": 3}');
    });

    test('should handle incomplete JSON at end of stream', function () {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, '{"a": 1, "b": 2');
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        expect($result)->toBe('{"a": 1, "b": 2}');
    });

    test('should handle large JSON documents', function () {
        $largeArray = array_fill(0, 1000, ['id' => 1, 'name' => 'test']);
        $json = json_encode($largeArray);

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $json);
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        $decoded = json_decode($result, true);
        expect($decoded)->toEqual($largeArray);
    });

    test('should process chunks with custom chunk size', function () {
        $repairer = new StreamingJsonRepair(8);
        $chunks = ["{name: 'John', age: 30}"];

        $result = '';
        foreach ($repairer->repairStream($chunks) as $chunk) {
            $result .= $chunk;
        }

        expect($result)->toBe('{"name": "John", "age": 30}');
    });

    test('should handle whitespace-only stream', function () {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "   \n  \t  ");
        rewind($stream);

        expect(function () use ($stream) {
            jsonrepairStreamToString($stream);
        })->toThrow(JsonRepair\Utils\JSONRepairError::class);

        fclose($stream);
    });

    test('should handle malformed nested structures', function () {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, '[{"a": 1, {"b": 2}]');
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        expect($result)->toBe('[{"a": 1}, {"b": 2}]');
    });
});
