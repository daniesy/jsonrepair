<?php

declare(strict_types=1);

namespace JsonRepair\Regular;

use JsonRepair\Utils\JSONRepairError;
use JsonRepair\Utils\StringUtils;

/**
 * Repair invalid JSON documents
 * Non-streaming implementation for regular-sized documents
 */
class JsonRepair
{
    private const CONTROL_CHARACTERS = [
        "\b" => '\\b',
        "\f" => '\\f',
        "\n" => '\\n',
        "\r" => '\\r',
        "\t" => '\\t',
    ];

    private const ESCAPE_CHARACTERS = [
        '"' => '"',
        '\\' => '\\',
        '/' => '/',
        'b' => "\b",
        'f' => "\f",
        'n' => "\n",
        'r' => "\r",
        't' => "\t",
    ];

    private string $text;
    private int $i = 0;
    private string $output = '';
    private bool $beautify = false;

    /**
     * Repair a string containing an invalid JSON document
     *
     * @param string $text The JSON text to repair
     * @param bool $beautify Whether to replace inner quotes with " instead of escaping them
     * @throws JSONRepairError
     */
    public function repair(string $text, bool $beautify = false): string
    {
        $this->text = $text;
        $this->i = 0;
        $this->output = '';
        $this->beautify = $beautify;

        $this->parseMarkdownCodeBlock(['```', '[```', '{```']);

        $processed = $this->parseValue();
        if (!$processed) {
            $this->throwUnexpectedEnd();
        }

        $this->parseMarkdownCodeBlock(['```', '```]', '```}']);

        $processedComma = $this->parseCharacter(',');
        if ($processedComma) {
            $this->parseWhitespaceAndSkipComments();
        }

        if (StringUtils::isStartOfValue($this->text[$this->i] ?? '') && StringUtils::endsWithCommaOrNewline($this->output)) {
            if (!$processedComma) {
                $this->output = StringUtils::insertBeforeLastWhitespace($this->output, ',');
            }
            $this->parseNewlineDelimitedJSON();
        } elseif ($processedComma) {
            $this->output = StringUtils::stripLastOccurrence($this->output, ',');
        }

        // Repair redundant end quotes
        while (($this->text[$this->i] ?? '') === '}' || ($this->text[$this->i] ?? '') === ']') {
            $this->i++;
            $this->parseWhitespaceAndSkipComments();
        }

        if ($this->i >= strlen($this->text)) {
            return $this->output;
        }

        $this->throwUnexpectedCharacter();
    }

    private function parseValue(): bool
    {
        $this->parseWhitespaceAndSkipComments();
        $processed = $this->parseObject()
            || $this->parseArray()
            || $this->parseString()
            || $this->parseNumber()
            || $this->parseKeywords()
            || $this->parseUnquotedString(false)
            || $this->parseRegex();
        $this->parseWhitespaceAndSkipComments();

        return $processed;
    }

    private function parseWhitespaceAndSkipComments(bool $skipNewline = true): bool
    {
        $start = $this->i;

        $changed = $this->parseWhitespace($skipNewline);
        do {
            $changed = $this->parseComment();
            if ($changed) {
                $changed = $this->parseWhitespace($skipNewline);
            }
        } while ($changed);

        return $this->i > $start;
    }

    private function parseWhitespace(bool $skipNewline): bool
    {
        $isWhiteSpace = $skipNewline
            ? fn($text, $i) => StringUtils::isWhitespace($text, $i)
            : fn($text, $i) => StringUtils::isWhitespaceExceptNewline($text, $i);

        $whitespace = '';

        while (true) {
            if ($isWhiteSpace($this->text, $this->i)) {
                $whitespace .= $this->text[$this->i];
                $this->i++;
            } elseif (StringUtils::isSpecialWhitespace($this->text, $this->i)) {
                $whitespace .= ' ';
                // Get the full UTF-8 character to know how many bytes to skip
                $char = StringUtils::charAt($this->text, $this->i);
                $this->i += strlen($char);
            } else {
                break;
            }
        }

        if (strlen($whitespace) > 0) {
            $this->output .= $whitespace;
            return true;
        }

        return false;
    }

    private function parseComment(): bool
    {
        // Block comment /* ... */
        if (($this->text[$this->i] ?? '') === '/' && ($this->text[$this->i + 1] ?? '') === '*') {
            while ($this->i < strlen($this->text) && !$this->atEndOfBlockComment()) {
                $this->i++;
            }
            $this->i += 2;
            return true;
        }

        // Line comment // ...
        if (($this->text[$this->i] ?? '') === '/' && ($this->text[$this->i + 1] ?? '') === '/') {
            while ($this->i < strlen($this->text) && $this->text[$this->i] !== "\n") {
                $this->i++;
            }
            return true;
        }

        return false;
    }

    private function atEndOfBlockComment(): bool
    {
        return ($this->text[$this->i] ?? '') === '*' && ($this->text[$this->i + 1] ?? '') === '/';
    }

    private function parseMarkdownCodeBlock(array $blocks): bool
    {
        if ($this->skipMarkdownCodeBlock($blocks)) {
            if (StringUtils::isFunctionNameCharStart($this->text[$this->i] ?? '')) {
                while ($this->i < strlen($this->text) && StringUtils::isFunctionNameChar($this->text[$this->i])) {
                    $this->i++;
                }
            }
            $this->parseWhitespaceAndSkipComments();
            return true;
        }
        return false;
    }

    private function skipMarkdownCodeBlock(array $blocks): bool
    {
        $this->parseWhitespace(true);

        foreach ($blocks as $block) {
            $end = $this->i + strlen($block);
            if (substr($this->text, $this->i, strlen($block)) === $block) {
                $this->i = $end;
                return true;
            }
        }

        return false;
    }

    private function parseCharacter(string $char): bool
    {
        if (($this->text[$this->i] ?? '') === $char) {
            $this->output .= $this->text[$this->i];
            $this->i++;
            return true;
        }
        return false;
    }

    private function skipCharacter(string $char): bool
    {
        if (($this->text[$this->i] ?? '') === $char) {
            $this->i++;
            return true;
        }
        return false;
    }

    private function skipEscapeCharacter(): bool
    {
        return $this->skipCharacter('\\');
    }

    private function skipEllipsis(): bool
    {
        $this->parseWhitespaceAndSkipComments();

        if (($this->text[$this->i] ?? '') === '.'
            && ($this->text[$this->i + 1] ?? '') === '.'
            && ($this->text[$this->i + 2] ?? '') === '.') {
            $this->i += 3;
            $this->parseWhitespaceAndSkipComments();
            $this->skipCharacter(',');
            return true;
        }

        return false;
    }

    private function parseObject(): bool
    {
        if (($this->text[$this->i] ?? '') === '{') {
            $this->output .= '{';
            $this->i++;
            $this->parseWhitespaceAndSkipComments();

            // Repair: skip leading comma
            if ($this->skipCharacter(',')) {
                $this->parseWhitespaceAndSkipComments();
            }

            $initial = true;
            while ($this->i < strlen($this->text) && ($this->text[$this->i] ?? '') !== '}') {
                if (!$initial) {
                    $processedComma = $this->parseCharacter(',');
                    if (!$processedComma) {
                        $this->output = StringUtils::insertBeforeLastWhitespace($this->output, ',');
                    }
                    $this->parseWhitespaceAndSkipComments();
                } else {
                    $initial = false;
                }

                $this->skipEllipsis();

                $processedKey = $this->parseString() || $this->parseUnquotedString(true);
                if (!$processedKey) {
                    if (in_array($this->text[$this->i] ?? '', ['}', '{', ']', '[', null, ''], true)) {
                        $this->output = StringUtils::stripLastOccurrence($this->output, ',');
                    } else {
                        $this->throwObjectKeyExpected();
                    }
                    break;
                }

                $this->parseWhitespaceAndSkipComments();
                $processedColon = $this->parseCharacter(':');
                $truncatedText = $this->i >= strlen($this->text);
                if (!$processedColon) {
                    if (StringUtils::isStartOfValue($this->text[$this->i] ?? '') || $truncatedText) {
                        $this->output = StringUtils::insertBeforeLastWhitespace($this->output, ':');
                    } else {
                        $this->throwColonExpected();
                    }
                }

                $processedValue = $this->parseValue();
                if (!$processedValue) {
                    if ($processedColon || $truncatedText) {
                        $this->output .= 'null';
                    } else {
                        $this->throwColonExpected();
                    }
                }
            }

            if (($this->text[$this->i] ?? '') === '}') {
                $this->output .= '}';
                $this->i++;
            } else {
                $this->output = StringUtils::insertBeforeLastWhitespace($this->output, '}');
            }

            return true;
        }

        return false;
    }

    private function parseArray(): bool
    {
        if (($this->text[$this->i] ?? '') === '[') {
            $this->output .= '[';
            $this->i++;
            $this->parseWhitespaceAndSkipComments();

            // Repair: skip leading comma
            if ($this->skipCharacter(',')) {
                $this->parseWhitespaceAndSkipComments();
            }

            $initial = true;
            while ($this->i < strlen($this->text) && ($this->text[$this->i] ?? '') !== ']') {
                if (!$initial) {
                    $processedComma = $this->parseCharacter(',');
                    if (!$processedComma) {
                        $this->output = StringUtils::insertBeforeLastWhitespace($this->output, ',');
                    }
                } else {
                    $initial = false;
                }

                $this->skipEllipsis();

                $processedValue = $this->parseValue();
                if (!$processedValue) {
                    $this->output = StringUtils::stripLastOccurrence($this->output, ',');
                    break;
                }
            }

            if (($this->text[$this->i] ?? '') === ']') {
                $this->output .= ']';
                $this->i++;
            } else {
                $this->output = StringUtils::insertBeforeLastWhitespace($this->output, ']');
            }

            return true;
        }

        return false;
    }

    private function parseNewlineDelimitedJSON(): void
    {
        $initial = true;
        $processedValue = true;

        while ($processedValue) {
            if (!$initial) {
                $processedComma = $this->parseCharacter(',');
                if (!$processedComma) {
                    $this->output = StringUtils::insertBeforeLastWhitespace($this->output, ',');
                }
            } else {
                $initial = false;
            }

            $processedValue = $this->parseValue();
        }

        if (!$processedValue) {
            $this->output = StringUtils::stripLastOccurrence($this->output, ',');
        }

        $this->output = "[\n{$this->output}\n]";
    }

    private function parseString(bool $stopAtDelimiter = false, int $stopAtIndex = -1): bool
    {
        $skipEscapeChars = ($this->text[$this->i] ?? '') === '\\';
        if ($skipEscapeChars) {
            $this->i++;
        }

        // Get the full UTF-8 character for quote detection
        $currentChar = StringUtils::charAt($this->text, $this->i);
        if (StringUtils::isQuote($currentChar)) {
            $isEndQuote = match (true) {
                StringUtils::isDoubleQuote($currentChar) => fn($c) => StringUtils::isDoubleQuote($c),
                StringUtils::isSingleQuote($currentChar) => fn($c) => StringUtils::isSingleQuote($c),
                StringUtils::isSingleQuoteLike($currentChar) => fn($c) => StringUtils::isSingleQuoteLike($c),
                default => fn($c) => StringUtils::isDoubleQuoteLike($c),
            };

            $iBefore = $this->i;
            $oBefore = strlen($this->output);

            $str = '"';
            // Skip past the opening quote (may be multi-byte)
            $this->i += strlen($currentChar);

            while (true) {
                if ($this->i >= strlen($this->text)) {
                    $iPrev = $this->prevNonWhitespaceIndex($this->i - 1);
                    $prevChar = $this->text[$iPrev] ?? '';
                    if (!$stopAtDelimiter && StringUtils::isDelimiter($prevChar)) {
                        // Restart with stopAtDelimiter=true
                        $this->i = $iBefore;
                        $this->output = substr($this->output, 0, $oBefore);
                        return $this->parseString(true);
                    }

                    $str = StringUtils::insertBeforeLastWhitespace($str, '"');
                    $this->output .= $str;
                    return true;
                }

                if ($this->i === $stopAtIndex) {
                    $str = StringUtils::insertBeforeLastWhitespace($str, '"');
                    $this->output .= $str;
                    return true;
                }

                $currentCheckChar = StringUtils::charAt($this->text, $this->i);
                if ($isEndQuote($currentCheckChar)) {
                    $iQuote = $this->i;
                    $oQuote = strlen($str);
                    $str .= '"';
                    $this->i += strlen($currentCheckChar);
                    $this->output .= $str;

                    $this->parseWhitespaceAndSkipComments(false);

                    if ($stopAtDelimiter
                        || $this->i >= strlen($this->text)
                        || StringUtils::isDelimiter($this->text[$this->i] ?? '')
                        || StringUtils::isQuote($this->text[$this->i] ?? '')
                        || StringUtils::isDigit($this->text[$this->i] ?? '')) {
                        $this->parseConcatenatedString();
                        return true;
                    }

                    $iPrevChar = $this->prevNonWhitespaceIndex($iQuote - 1);
                    $prevChar = $this->text[$iPrevChar] ?? '';

                    if ($prevChar === ',') {
                        $this->i = $iBefore;
                        $this->output = substr($this->output, 0, $oBefore);
                        return $this->parseString(false, $iPrevChar);
                    }

                    if (StringUtils::isDelimiter($prevChar)) {
                        $this->i = $iBefore;
                        $this->output = substr($this->output, 0, $oBefore);
                        return $this->parseString(true);
                    }

                    $this->output = substr($this->output, 0, $oBefore);
                    $this->i = $iQuote + 1;
                    // If beautify is enabled, replace the quote with curly quote instead of escaping
                    if ($this->beautify) {
                        $str = substr($str, 0, $oQuote) . "\u{201D}" . substr($str, $oQuote + 1);
                    } else {
                        $str = substr($str, 0, $oQuote) . '\\' . substr($str, $oQuote);
                    }
                } elseif (($this->text[$this->i] ?? '') === '\\') {
                    $char = $this->text[$this->i + 1] ?? '';
                    $escapeChar = self::ESCAPE_CHARACTERS[$char] ?? null;

                    if ($escapeChar !== null) {
                        $str .= substr($this->text, $this->i, 2);
                        $this->i += 2;
                    } elseif ($char === 'u') {
                        $j = 2;
                        while ($j < 6 && StringUtils::isHex($this->text[$this->i + $j] ?? '')) {
                            $j++;
                        }

                        if ($j === 6) {
                            $str .= substr($this->text, $this->i, 6);
                            $this->i += 6;
                        } elseif ($this->i + $j >= strlen($this->text)) {
                            $this->i = strlen($this->text);
                        } else {
                            $this->throwInvalidUnicodeCharacter();
                        }
                    } else {
                        $str .= $char;
                        $this->i += 2;
                    }
                } else if ($stopAtDelimiter && StringUtils::isUnquotedStringDelimiter($this->text[$this->i] ?? '')) {
                    $char = $this->text[$this->i];

                    // Check for URL special case
                    if ($char !== "\n" && ($this->text[$this->i - 1] ?? '') === ':'
                        && StringUtils::matchesUrlStart(substr($this->text, $iBefore + 1, $this->i - $iBefore + 1))) {
                        while ($this->i < strlen($this->text) && StringUtils::isUrlChar($this->text[$this->i])) {
                            $str .= $this->text[$this->i];
                            $this->i++;
                        }
                    }

                    $str = StringUtils::insertBeforeLastWhitespace($str, '"');
                    $this->output .= $str;
                    $this->parseConcatenatedString();
                    return true;
                } else {
                    $char = $this->text[$this->i] ?? '';

                    if ($char === '"' && ($this->text[$this->i - 1] ?? '') !== '\\') {
                        // If beautify is enabled, replace with " instead of escaping
                        if ($this->beautify) {
                            $str .= "\u{201D}";  // Right double quotation mark (U+201D)
                        } else {
                            $str .= "\\{$char}";
                        }
                        $this->i++;
                    } elseif (StringUtils::isControlCharacter($char)) {
                        $str .= self::CONTROL_CHARACTERS[$char];
                        $this->i++;
                    } else {
                        if (!StringUtils::isValidStringCharacter($char)) {
                            $this->throwInvalidCharacter($char);
                        }
                        $str .= $char;
                        $this->i++;
                    }
                }

                if ($skipEscapeChars) {
                    $this->skipEscapeCharacter();
                }
            }
        }

        return false;
    }

    private function parseConcatenatedString(): bool
    {
        $processed = false;

        $this->parseWhitespaceAndSkipComments();
        while (($this->text[$this->i] ?? '') === '+') {
            $processed = true;
            $this->i++;
            $this->parseWhitespaceAndSkipComments();

            $this->output = StringUtils::stripLastOccurrence($this->output, '"', true);
            $start = strlen($this->output);
            $parsedStr = $this->parseString();
            if ($parsedStr) {
                $this->output = StringUtils::removeAtIndex($this->output, $start, 1);
            } else {
                $this->output = StringUtils::insertBeforeLastWhitespace($this->output, '"');
            }
        }

        return $processed;
    }

    private function parseNumber(): bool
    {
        $start = $this->i;

        if (($this->text[$this->i] ?? '') === '-') {
            $this->i++;
            if ($this->atEndOfNumber()) {
                $this->repairNumberEndingWithNumericSymbol($start);
                return true;
            }
            if (!StringUtils::isDigit($this->text[$this->i] ?? '')) {
                $this->i = $start;
                return false;
            }
        }

        while (StringUtils::isDigit($this->text[$this->i] ?? '')) {
            $this->i++;
        }

        if (($this->text[$this->i] ?? '') === '.') {
            $this->i++;
            if ($this->atEndOfNumber()) {
                $this->repairNumberEndingWithNumericSymbol($start);
                return true;
            }
            if (!StringUtils::isDigit($this->text[$this->i] ?? '')) {
                $this->i = $start;
                return false;
            }
            while (StringUtils::isDigit($this->text[$this->i] ?? '')) {
                $this->i++;
            }
        }

        if (in_array($this->text[$this->i] ?? '', ['e', 'E'], true)) {
            $this->i++;
            if (in_array($this->text[$this->i] ?? '', ['-', '+'], true)) {
                $this->i++;
            }
            if ($this->atEndOfNumber()) {
                $this->repairNumberEndingWithNumericSymbol($start);
                return true;
            }
            if (!StringUtils::isDigit($this->text[$this->i] ?? '')) {
                $this->i = $start;
                return false;
            }
            while (StringUtils::isDigit($this->text[$this->i] ?? '')) {
                $this->i++;
            }
        }

        if (!$this->atEndOfNumber()) {
            $this->i = $start;
            return false;
        }

        if ($this->i > $start) {
            $num = substr($this->text, $start, $this->i - $start);
            $hasInvalidLeadingZero = preg_match('/^0\d/', $num) === 1;

            $this->output .= $hasInvalidLeadingZero ? "\"{$num}\"" : $num;
            return true;
        }

        return false;
    }

    private function parseKeywords(): bool
    {
        return $this->parseKeyword('true', 'true')
            || $this->parseKeyword('false', 'false')
            || $this->parseKeyword('null', 'null')
            || $this->parseKeyword('True', 'true')
            || $this->parseKeyword('False', 'false')
            || $this->parseKeyword('None', 'null');
    }

    private function parseKeyword(string $name, string $value): bool
    {
        if (substr($this->text, $this->i, strlen($name)) === $name) {
            $this->output .= $value;
            $this->i += strlen($name);
            return true;
        }
        return false;
    }

    private function parseUnquotedString(bool $isKey): bool
    {
        $start = $this->i;

        if (StringUtils::isFunctionNameCharStart($this->text[$this->i] ?? '')) {
            while ($this->i < strlen($this->text) && StringUtils::isFunctionNameChar($this->text[$this->i])) {
                $this->i++;
            }

            $j = $this->i;
            while (StringUtils::isWhitespace($this->text, $j)) {
                $j++;
            }

            if (($this->text[$j] ?? '') === '(') {
                $this->i = $j + 1;
                $this->parseValue();

                if (($this->text[$this->i] ?? '') === ')') {
                    $this->i++;
                    if (($this->text[$this->i] ?? '') === ';') {
                        $this->i++;
                    }
                }

                return true;
            }
        }

        while ($this->i < strlen($this->text)
            && !StringUtils::isUnquotedStringDelimiter($this->text[$this->i] ?? '')
            && !StringUtils::isQuote($this->text[$this->i] ?? '')
            && (!$isKey || ($this->text[$this->i] ?? '') !== ':')) {
            $this->i++;
        }

        // Test for URL
        if (($this->text[$this->i - 1] ?? '') === ':'
            && StringUtils::matchesUrlStart(substr($this->text, $start, $this->i - $start + 2))) {
            while ($this->i < strlen($this->text) && StringUtils::isUrlChar($this->text[$this->i])) {
                $this->i++;
            }
        }

        if ($this->i > $start) {
            while (StringUtils::isWhitespace($this->text, $this->i - 1) && $this->i > 0) {
                $this->i--;
            }

            $symbol = substr($this->text, $start, $this->i - $start);
            $this->output .= $symbol === 'undefined' ? 'null' : json_encode($symbol, JSON_UNESCAPED_SLASHES);

            if (($this->text[$this->i] ?? '') === '"') {
                $this->i++;
            }

            return true;
        }

        return false;
    }

    private function parseRegex(): bool
    {
        if (($this->text[$this->i] ?? '') === '/') {
            $start = $this->i;
            $this->i++;

            while ($this->i < strlen($this->text)
                && (($this->text[$this->i] ?? '') !== '/' || ($this->text[$this->i - 1] ?? '') === '\\')) {
                $this->i++;
            }
            $this->i++;

            $this->output .= '"' . substr($this->text, $start, $this->i - $start) . '"';
            return true;
        }

        return false;
    }

    private function prevNonWhitespaceIndex(int $start): int
    {
        $prev = $start;
        while ($prev > 0 && StringUtils::isWhitespace($this->text, $prev)) {
            $prev--;
        }
        return $prev;
    }

    private function atEndOfNumber(): bool
    {
        return $this->i >= strlen($this->text)
            || StringUtils::isDelimiter($this->text[$this->i] ?? '')
            || StringUtils::isWhitespace($this->text, $this->i);
    }

    private function repairNumberEndingWithNumericSymbol(int $start): void
    {
        $this->output .= substr($this->text, $start, $this->i - $start) . '0';
    }

    private function throwInvalidCharacter(string $char): never
    {
        throw new JSONRepairError('Invalid character ' . json_encode($char), $this->i);
    }

    private function throwUnexpectedCharacter(): never
    {
        throw new JSONRepairError('Unexpected character ' . json_encode($this->text[$this->i] ?? ''), $this->i);
    }

    private function throwUnexpectedEnd(): never
    {
        throw new JSONRepairError('Unexpected end of json string', strlen($this->text));
    }

    private function throwObjectKeyExpected(): never
    {
        throw new JSONRepairError('Object key expected', $this->i);
    }

    private function throwColonExpected(): never
    {
        throw new JSONRepairError('Colon expected', $this->i);
    }

    private function throwInvalidUnicodeCharacter(): never
    {
        $chars = substr($this->text, $this->i, 6);
        throw new JSONRepairError("Invalid unicode character \"{$chars}\"", $this->i);
    }
}
