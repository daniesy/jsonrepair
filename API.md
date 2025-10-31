# API Reference

## Functions

### jsonrepair()

Repair a string containing an invalid JSON document.

```php
function jsonrepair(string $text): string
```

**Parameters:**
- `$text` (string): The invalid JSON string to repair

**Returns:**
- (string): The repaired JSON string

**Throws:**
- `JsonRepair\Utils\JSONRepairError`: When JSON cannot be repaired

**Example:**
```php
use function JsonRepair\jsonrepair;

$json = "{name: 'John'}";
$repaired = jsonrepair($json);
echo $repaired; // {"name": "John"}
```

---

### jsonrepairStream()

Repair JSON from a stream in chunks (memory efficient for large documents).

```php
function jsonrepairStream(
    resource|iterable<string> $input,
    int $chunkSize = 65536
): \Generator<string>
```

**Parameters:**
- `$input` (resource|iterable): Stream resource or iterable of chunks
- `$chunkSize` (int): Size of chunks to process (default: 65536)

**Returns:**
- (Generator): Yields repaired JSON chunks

**Throws:**
- `JsonRepair\Utils\JSONRepairError`: When JSON cannot be repaired

**Example:**
```php
use function JsonRepair\jsonrepairStream;

$stream = fopen('large.json', 'r');
foreach (jsonrepairStream($stream) as $chunk) {
    echo $chunk;
}
fclose($stream);
```

---

### jsonrepairStreamToString()

Repair JSON from a stream and return the complete result as a string.

```php
function jsonrepairStreamToString(
    resource|iterable<string> $input,
    int $chunkSize = 65536
): string
```

**Parameters:**
- `$input` (resource|iterable): Stream resource or iterable of chunks
- `$chunkSize` (int): Size of chunks to process (default: 65536)

**Returns:**
- (string): Complete repaired JSON

**Throws:**
- `JsonRepair\Utils\JSONRepairError`: When JSON cannot be repaired

**Example:**
```php
use function JsonRepair\jsonrepairStreamToString;

$stream = fopen('data.json', 'r');
$repaired = jsonrepairStreamToString($stream);
fclose($stream);
```

---

## Classes

### JsonRepair\Regular\JsonRepair

Regular (non-streaming) JSON repair implementation.

```php
$repairer = new JsonRepair\Regular\JsonRepair();
$repaired = $repairer->repair($json);
```

**Methods:**
- `repair(string $text): string` - Repair a JSON string

---

### JsonRepair\Streaming\StreamingJsonRepair

Streaming JSON repair implementation for large documents.

```php
$repairer = new JsonRepair\Streaming\StreamingJsonRepair($chunkSize);
```

**Constructor:**
- `__construct(int $chunkSize = 65536)`

**Methods:**
- `repairStream(resource|iterable $input): \Generator` - Yields repaired chunks
- `repairStreamToString(resource|iterable $input): string` - Returns complete result

---

### JsonRepair\Utils\JSONRepairError

Exception thrown when JSON cannot be repaired.

```php
class JSONRepairError extends \Exception
{
    public readonly int $position;
}
```

**Properties:**
- `$position` (int): Position in the input where the error occurred

**Example:**
```php
try {
    $repaired = jsonrepair($invalidJson);
} catch (JsonRepair\Utils\JSONRepairError $e) {
    echo "Error at position {$e->position}: {$e->getMessage()}";
}
```

---

## Streaming Components

### JsonRepair\Streaming\Buffer\InputBuffer

Manages incoming chunks of data with efficient memory usage.

**Methods:**
- `push(string $chunk): void` - Add a chunk to the buffer
- `flush(int $position): void` - Flush buffer up to a position
- `charAt(int $index): string` - Get character at index
- `substring(int $start, int $end): string` - Get substring
- `currentLength(): int` - Get current length
- `close(): void` - Mark input as complete

---

### JsonRepair\Streaming\Buffer\OutputBuffer

Manages outgoing chunks with automatic flushing.

**Constructor:**
```php
public function __construct(
    private readonly \Closure $write,
    private readonly int $chunkSize = 65536,
    private readonly int $bufferSize = 65536
)
```

**Methods:**
- `push(string $text): void` - Add text to buffer
- `flush(): void` - Flush all remaining output
- `stripLastOccurrence(string $text, bool $stripRemaining = false): void`
- `insertBeforeLastWhitespace(string $text): void`

---

### JsonRepair\Streaming\Stack

Tracks parsing state (objects, arrays, etc.).

**Enums:**
```php
enum Caret: string {
    case BEFORE_VALUE = 'beforeValue';
    case AFTER_VALUE = 'afterValue';
    case BEFORE_KEY = 'beforeKey';
}

enum StackType: string {
    case ROOT = 'root';
    case OBJECT = 'object';
    case ARRAY = 'array';
    case ND_JSON = 'ndJson';
    case FUNCTION_CALL = 'dataType';
}
```

**Methods:**
- `type(): StackType` - Get current stack type
- `caret(): Caret` - Get current caret position
- `push(StackType $type, Caret $caret): bool`
- `pop(): bool`
- `update(Caret $caret): bool`

---

## Common Repairs

The library repairs many common JSON issues:

| Issue | Input | Output |
|-------|-------|--------|
| Missing quotes | `{name: "John"}` | `{"name": "John"}` |
| Single quotes | `{'name': 'John'}` | `{"name": "John"}` |
| Trailing commas | `[1, 2, 3,]` | `[1, 2, 3]` |
| Comments | `{"a": 1 /* comment */}` | `{"a": 1}` |
| Missing commas | `{"a": 1 "b": 2}` | `{"a": 1, "b": 2}` |
| Missing colons | `{"name" "John"}` | `{"name": "John"}` |
| Truncated | `{"name": "Joh` | `{"name": "John"}` |
| Python constants | `{"value": None}` | `{"value": null}` |

For a complete list, see [TEST_STATUS.md](TEST_STATUS.md).
