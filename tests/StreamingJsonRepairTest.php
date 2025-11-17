<?php

declare(strict_types=1);

use JsonRepair\Streaming\StreamingJsonRepair;
use JsonRepair\Utils\JSONRepairError;
use PHPUnit\Framework\TestCase;
use function JsonRepair\jsonrepairStream;
use function JsonRepair\jsonrepairStreamToString;

final class StreamingJsonRepairTest extends TestCase
{
    public function testShouldRepairJsonFromAStream(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "{name: 'John'}");
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        $this->assertSame('{"name": "John"}', $result);
    }

    public function testShouldRepairJsonFromArrayOfChunks(): void
    {
        $chunks = ["{name: ", "'John'}"];

        $result = '';
        foreach (jsonrepairStream($chunks) as $chunk) {
            $result .= $chunk;
        }

        $this->assertSame('{"name": "John"}', $result);
    }

    public function testShouldHandleNewlineDelimitedJson(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "{\"a\":1}\n{\"b\":2}\n{\"c\":3}");
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        $this->assertSame('{"a":1}{"b":2}{"c":3}', $result);
    }

    public function testShouldHandleArraysSplitAcrossChunks(): void
    {
        $chunks = ['[1, ', '2, ', '3]'];

        $result = '';
        foreach (jsonrepairStream($chunks) as $chunk) {
            $result .= $chunk;
        }

        $this->assertSame('[1, 2, 3]', $result);
    }

    public function testShouldHandleObjectsSplitAcrossChunks(): void
    {
        $chunks = ['{\"a\":', '1, \"b\":', '2}'];

        $result = '';
        foreach (jsonrepairStream($chunks) as $chunk) {
            $result .= $chunk;
        }

        $this->assertSame('{"a":1, "b":2}', $result);
    }

    public function testShouldRepairMissingQuotesInStream(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "{name: John, age: 30}");
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        $this->assertSame('{"name": "John", "age": 30}', $result);
    }

    public function testShouldHandleTrailingCommasInStream(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "[1, 2, 3,]");
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        $this->assertSame('[1, 2, 3]', $result);
    }

    public function testShouldHandleSingleQuotesInStream(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "{'a': 'b'}");
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        $this->assertSame('{"a": "b"}', $result);
    }

    public function testShouldHandleCommentsInStream(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "{/* comment */\"a\": 1}");
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        $this->assertSame('{"a": 1}', $result);
    }

    public function testShouldYieldMultipleChunksWhenProcessing(): void
    {
        $chunks = [
            '{"a": 1}',
            '{"b": 2}',
            '{"c": 3}'
        ];

        $results = [];
        foreach (jsonrepairStream($chunks) as $chunk) {
            $results[] = $chunk;
        }

        $this->assertGreaterThanOrEqual(1, count($results));
        $this->assertSame('{"a": 1}{"b": 2}{"c": 3}', implode('', $results));
    }

    public function testShouldHandleIncompleteJsonAtEndOfStream(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, '{"a": 1, "b": 2');
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        $this->assertSame('{"a": 1, "b": 2}', $result);
    }

    public function testShouldHandleLargeJsonDocuments(): void
    {
        $largeArray = array_fill(0, 1000, ['id' => 1, 'name' => 'test']);
        $json = json_encode($largeArray);

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $json);
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        $decoded = json_decode($result, true);
        $this->assertEquals($largeArray, $decoded);
    }

    public function testShouldProcessChunksWithCustomChunkSize(): void
    {
        $repairer = new StreamingJsonRepair(8);
        $chunks = ["{name: 'John', age: 30}"];

        $result = '';
        foreach ($repairer->repairStream($chunks) as $chunk) {
            $result .= $chunk;
        }

        $this->assertSame('{"name": "John", "age": 30}', $result);
    }

    public function testShouldHandleWhitespaceOnlyStream(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "   \n  \t  ");
        rewind($stream);

        $this->expectException(JSONRepairError::class);
        jsonrepairStreamToString($stream);

        fclose($stream);
    }

    public function testShouldHandleMalformedNestedStructures(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, '[{"a": 1, {"b": 2}]');
        rewind($stream);

        $result = jsonrepairStreamToString($stream);
        fclose($stream);

        $this->assertSame('[{"a": 1}, {"b": 2}]', $result);
    }

    // Beautify option tests for streaming
    public function testStreamingBeautifyOptionReplacesInnerQuotes(): void
    {
        $input = '{"message": "He said ' . chr(34) . 'hello' . chr(34) . ' to me"}';

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $input);
        rewind($stream);

        // Without beautify
        $resultDefault = jsonrepairStreamToString($stream, 65536, false);
        $this->assertSame('{"message": "He said \"hello\" to me"}', $resultDefault);

        // Reset stream
        rewind($stream);

        // With beautify
        $resultBeautify = jsonrepairStreamToString($stream, 65536, true);
        $this->assertSame('{"message": "He said ' . "\u{201D}" . 'hello' . "\u{201D}" . ' to me"}', $resultBeautify);

        fclose($stream);
    }

    public function testStreamingBeautifyWithChunks(): void
    {
        $chunks = ['{"msg": "test ', chr(34) . 'quote', chr(34) . ' here"}'];

        // Without beautify
        $resultDefault = '';
        foreach (jsonrepairStream($chunks, 65536, false) as $chunk) {
            $resultDefault .= $chunk;
        }
        $this->assertSame('{"msg": "test \"quote\" here"}', $resultDefault);

        // With beautify
        $resultBeautify = '';
        foreach (jsonrepairStream($chunks, 65536, true) as $chunk) {
            $resultBeautify .= $chunk;
        }
        $this->assertSame('{"msg": "test ' . "\u{201D}" . 'quote' . "\u{201D}" . ' here"}', $resultBeautify);
    }

    public function testStreamingBeautifyAcrossChunks(): void
    {
        // Split the input across multiple chunks - with spaces to avoid consecutive quotes issue
        $chunks = [
            '{"message": "Part one ',
            chr(34) . ' quote ' . chr(34),
            ' and part two ',
            chr(34) . ' another ' . chr(34),
            ' end"}'
        ];

        // Without beautify
        $resultDefault = jsonrepairStreamToString($chunks, 65536, false);
        $this->assertStringContainsString('\" quote \"', $resultDefault);
        $this->assertStringContainsString('\" another \"', $resultDefault);

        // With beautify
        $resultBeautify = jsonrepairStreamToString($chunks, 65536, true);
        $this->assertStringContainsString("\u{201D} quote \u{201D}", $resultBeautify);
        $this->assertStringContainsString("\u{201D} another \u{201D}", $resultBeautify);
    }

    public function testStreamingBeautifyWithCustomChunkSize(): void
    {
        $input = '{"text": "Small ' . chr(34) . 'chunk' . chr(34) . ' test"}';

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $input);
        rewind($stream);

        // Small chunk size with beautify
        $repairer = new StreamingJsonRepair(8, true);
        $result = $repairer->repairStreamToString($stream);
        $this->assertSame('{"text": "Small ' . "\u{201D}" . 'chunk' . "\u{201D}" . ' test"}', $result);

        fclose($stream);
    }

    public function testStreamingBeautifyWithNewlineDelimitedJson(): void
    {
        // Use spaces around quotes to avoid consecutive quotes issue
        $input = '{"a": "quote ' . chr(34) . ' here ' . chr(34) . ' text"}' . "\n" .
                 '{"b": "another ' . chr(34) . ' quote ' . chr(34) . ' text"}';

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $input);
        rewind($stream);

        $result = jsonrepairStreamToString($stream, 65536, true);

        // Each object should have beautified quotes
        $this->assertStringContainsString("\u{201D} here \u{201D}", $result);
        $this->assertStringContainsString("\u{201D} quote \u{201D}", $result);

        fclose($stream);
    }
}
