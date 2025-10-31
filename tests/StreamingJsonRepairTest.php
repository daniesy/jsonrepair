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
}
