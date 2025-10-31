# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-01-XX

### Added
- Initial release of PHP 8.4+ port
- Complete regular (non-streaming) JSON repair implementation
- Streaming support for memory-efficient processing of large files
- Support for all JSON repair features from the original library:
  - Missing quotes repair
  - Single to double quote conversion
  - Special quote character handling
  - Trailing comma removal
  - Comment stripping (block and line comments)
  - JSONP notation stripping
  - Markdown code block stripping
  - Python constants replacement (None, True, False)
  - Missing comma insertion
  - Missing colon insertion
  - Missing bracket/brace completion
  - Truncated JSON repair
  - MongoDB data type stripping
  - Unquoted string repair
  - Invalid number handling
  - Regular expression repair
  - String concatenation
  - Newline-delimited JSON support
- Helper functions: `jsonrepair()`, `jsonrepairStream()`, `jsonrepairStreamToString()`
- Comprehensive test suite with 81 tests (100% pass rate)
- PSR-4 autoloading
- Modern PHP 8.4 features: enums, readonly properties, constructor promotion, match expressions
- Streaming components: InputBuffer, OutputBuffer, Stack
- PHP Generators for memory-efficient streaming
- Complete documentation with examples

### Tests
- 66 regular (non-streaming) tests
- 15 streaming tests
- Examples for both regular and streaming usage

## [Unreleased]

### Planned
- Performance optimizations
- Additional streaming configuration options
- PHP 8.5+ support when available
