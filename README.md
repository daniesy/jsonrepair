# JSON Repair - PHP 8.4+

[![Latest Version](https://img.shields.io/packagist/v/jsonrepair/jsonrepair.svg?style=flat-square)](https://packagist.org/packages/jsonrepair/jsonrepair)
[![PHP Version](https://img.shields.io/packagist/php-v/jsonrepair/jsonrepair.svg?style=flat-square)](https://packagist.org/packages/jsonrepair/jsonrepair)
[![License](https://img.shields.io/packagist/l/jsonrepair/jsonrepair.svg?style=flat-square)](https://packagist.org/packages/jsonrepair/jsonrepair)

A modern PHP 8.4+ port of the [jsonrepair](https://github.com/josdejong/jsonrepair) library that repairs invalid JSON documents.

## Features

- **Modern PHP 8.4+** with typed properties, constructor promotion, enums, and match expressions
- **Streaming support** for memory-efficient processing of large JSON files
- **100% test coverage** - All 81 tests passing
- Repairs common JSON issues:
  - Add missing quotes around keys
  - Add missing escape characters
  - Add missing commas
  - Add missing closing brackets
  - Repair truncated JSON
  - Replace single quotes with double quotes
  - Replace special quote characters
  - Strip trailing commas
  - Strip comments (`/* */` and `//`)
  - Strip markdown fenced code blocks
  - Strip JSONP notation
  - Handle Python constants (None, True, False)
  - Concatenate strings
  - And much more...

## Requirements

- PHP 8.4 or higher

## Installation

Install via Composer:

```bash
composer require jsonrepair/jsonrepair
```

For development:

```bash
git clone https://github.com/daniesy/jsonrepair-php.git
cd jsonrepair-php
composer install
```

## Usage

### Regular (Non-streaming)

For regular-sized JSON documents:

```php
use function JsonRepair\jsonrepair;

try {
    $json = "{name: 'John'}";
    $repaired = jsonrepair($json);
    echo $repaired; // {"name": "John"}
} catch (JsonRepair\Utils\JSONRepairError $e) {
    echo "Error: " . $e->getMessage();
}
```

### Streaming

For large JSON documents or streams (memory-efficient):

```php
use function JsonRepair\jsonrepairStream;
use function JsonRepair\jsonrepairStreamToString;

// From a file stream
$stream = fopen('large.json', 'r');
foreach (jsonrepairStream($stream) as $chunk) {
    echo $chunk; // Process chunks as they're repaired
}
fclose($stream);

// Or get complete result
$stream = fopen('data.json', 'r');
$repaired = jsonrepairStreamToString($stream);
fclose($stream);

// From an array of chunks
$chunks = ["{name: ", "'John'}"];
foreach (jsonrepairStream($chunks) as $chunk) {
    echo $chunk;
}
```

## Examples

Check out the [examples](examples/) directory for practical usage examples:

```bash
php examples/streaming_example.php
```

## Testing

Run the test suite using Pest:

```bash
# Run all tests
composer test

# Run only unit tests
composer test:unit

# Run only streaming tests
composer test:streaming

# Run tests with coverage
composer test:coverage

# Generate HTML coverage report
composer test:coverage-html
```

### Test Results

**All 81 tests passing (100% pass rate)** ✅
- 66 regular (non-streaming) tests
- 15 streaming tests

All functionality from the original JavaScript/TypeScript library has been successfully ported and verified, including streaming support.

See [TEST_STATUS.md](TEST_STATUS.md) for detailed test results.

## Architecture

This port maintains the same dual-implementation architecture as the original:
- **Regular (Non-streaming)**: `JsonRepair\Regular\JsonRepair` - Loads entire document into memory, ideal for regular-sized documents
- **Streaming**: `JsonRepair\Streaming\StreamingJsonRepair` - Processes JSON in chunks using PHP generators, ideal for large documents or streaming data

The streaming implementation uses:
- **InputBuffer**: Manages incoming chunks with efficient memory usage
- **OutputBuffer**: Manages outgoing chunks with automatic flushing
- **Stack**: Tracks parsing state (objects, arrays, etc.)
- **PHP Generators**: Provides memory-efficient iteration over large datasets

## Original Project

This is a PHP port of [jsonrepair](https://github.com/josdejong/jsonrepair) by Jos de Jong.

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests for your changes
4. Ensure all tests pass (`composer test`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

Please make sure to:
- Follow PSR-12 coding standards
- Add tests for new functionality
- Update documentation as needed
- Maintain PHP 8.4+ compatibility

## Credits

- **Original Author**: [Jos de Jong](https://github.com/josdejong)
- **Original Project**: [jsonrepair](https://github.com/josdejong/jsonrepair)
- **PHP Port**: Dan Florian
- This PHP port maintains feature parity with the JavaScript/TypeScript implementation

## License

ISC License (same as original project)

Copyright (c) 2020-2025 by Jos de Jong

See [LICENSE](LICENSE) file for details.
