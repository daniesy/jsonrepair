<?php

declare(strict_types=1);

namespace JsonRepair\Utils;

/**
 * String utility functions for JSON repair operations
 */
class StringUtils
{
    // Character codes for whitespace detection
    private const CODE_SPACE = 0x20; // " "
    private const CODE_NEWLINE = 0x0A; // "\n"
    private const CODE_TAB = 0x09; // "\t"
    private const CODE_RETURN = 0x0D; // "\r"
    private const CODE_NON_BREAKING_SPACE = 0xA0;
    private const CODE_EN_QUAD = 0x2000;
    private const CODE_HAIR_SPACE = 0x200A;
    private const CODE_NARROW_NO_BREAK_SPACE = 0x202F;
    private const CODE_MEDIUM_MATHEMATICAL_SPACE = 0x205F;
    private const CODE_IDEOGRAPHIC_SPACE = 0x3000;

    // Regular expression patterns
    private const REGEX_URL_START = '/^(http|https|ftp|mailto|file|data|irc):\/\/$/';
    private const REGEX_URL_CHAR = '/^[A-Za-z0-9\-._~:\/?#@!$&\'()*+;=]$/';
    private const REGEX_START_OF_VALUE = '/^[[\{\w\-]$/u';

    /**
     * Check if character is a hexadecimal digit
     */
    public static function isHex(string $char): bool
    {
        return preg_match('/^[0-9A-Fa-f]$/', $char) === 1;
    }

    /**
     * Check if character is a digit
     */
    public static function isDigit(string $char): bool
    {
        return $char >= '0' && $char <= '9';
    }

    /**
     * Check if character is a valid string character (>= \u0020)
     * Note: For multi-byte UTF-8, we check the raw byte value.
     */
    public static function isValidStringCharacter(string $char): bool
    {
        if ($char === '') {
            return false;
        }

        // Check if byte value is >= 0x20 (space)
        // UTF-8 multi-byte characters have first byte >= 0x80, which passes this check
        return ord($char) >= 0x20;
    }

    /**
     * Check if character is a delimiter
     */
    public static function isDelimiter(string $char): bool
    {
        return str_contains(",:[]/{}\n+()", $char);
    }

    /**
     * Check if character can start a function name
     */
    public static function isFunctionNameCharStart(string $char): bool
    {
        return ($char >= 'a' && $char <= 'z')
            || ($char >= 'A' && $char <= 'Z')
            || $char === '_'
            || $char === '$';
    }

    /**
     * Check if character can be part of a function name
     */
    public static function isFunctionNameChar(string $char): bool
    {
        return ($char >= 'a' && $char <= 'z')
            || ($char >= 'A' && $char <= 'Z')
            || $char === '_'
            || $char === '$'
            || ($char >= '0' && $char <= '9');
    }

    /**
     * Check if string matches URL start pattern
     */
    public static function matchesUrlStart(string $str): bool
    {
        return preg_match(self::REGEX_URL_START, $str) === 1;
    }

    /**
     * Check if character is a valid URL character
     */
    public static function isUrlChar(string $char): bool
    {
        return preg_match(self::REGEX_URL_CHAR, $char) === 1;
    }

    /**
     * Check if character is an unquoted string delimiter
     */
    public static function isUnquotedStringDelimiter(string $char): bool
    {
        return str_contains(",[]/{}\n+", $char);
    }

    /**
     * Check if character can start a value
     */
    public static function isStartOfValue(string $char): bool
    {
        return self::isQuote($char) || preg_match(self::REGEX_START_OF_VALUE, $char) === 1;
    }

    /**
     * Check if character is a control character
     */
    public static function isControlCharacter(string $char): bool
    {
        return $char === "\n" || $char === "\r" || $char === "\t" || $char === "\b" || $char === "\f";
    }

    /**
     * Check if character at index is whitespace (space, tab, newline, or return)
     */
    public static function isWhitespace(string $text, int $index): bool
    {
        if (!isset($text[$index])) {
            return false;
        }

        $code = ord($text[$index]);
        return $code === self::CODE_SPACE
            || $code === self::CODE_NEWLINE
            || $code === self::CODE_TAB
            || $code === self::CODE_RETURN;
    }

    /**
     * Check if character at index is whitespace excluding newline
     */
    public static function isWhitespaceExceptNewline(string $text, int $index): bool
    {
        if (!isset($text[$index])) {
            return false;
        }

        $code = ord($text[$index]);
        return $code === self::CODE_SPACE
            || $code === self::CODE_TAB
            || $code === self::CODE_RETURN;
    }

    /**
     * Check if character at index is special whitespace (unicode variants)
     */
    public static function isSpecialWhitespace(string $text, int $index): bool
    {
        if (!isset($text[$index])) {
            return false;
        }

        // For multi-byte characters, we need to use mb_substr
        $char = mb_substr($text, $index, 1, 'UTF-8');
        if ($char === '' || $char === false) {
            return false;
        }

        $code = mb_ord($char, 'UTF-8');
        if ($code === false) {
            return false;
        }

        return $code === self::CODE_NON_BREAKING_SPACE
            || ($code >= self::CODE_EN_QUAD && $code <= self::CODE_HAIR_SPACE)
            || $code === self::CODE_NARROW_NO_BREAK_SPACE
            || $code === self::CODE_MEDIUM_MATHEMATICAL_SPACE
            || $code === self::CODE_IDEOGRAPHIC_SPACE;
    }

    /**
     * Test whether character is a quote (single or double, including special variants)
     */
    public static function isQuote(string $char): bool
    {
        return self::isDoubleQuoteLike($char) || self::isSingleQuoteLike($char);
    }

    /**
     * Test whether character is a double quote (including special variants)
     */
    public static function isDoubleQuoteLike(string $char): bool
    {
        return $char === '"' || $char === "\u{201C}" || $char === "\u{201D}";
    }

    /**
     * Test whether character is a double quote (standard only)
     */
    public static function isDoubleQuote(string $char): bool
    {
        return $char === '"';
    }

    /**
     * Test whether character is a single quote (including special variants)
     */
    public static function isSingleQuoteLike(string $char): bool
    {
        return $char === "'"
            || $char === "\u{2018}"
            || $char === "\u{2019}"
            || $char === "\u{0060}"
            || $char === "\u{00B4}";
    }

    /**
     * Test whether character is a single quote (standard only)
     */
    public static function isSingleQuote(string $char): bool
    {
        return $char === "'";
    }

    /**
     * Strip last occurrence of textToStrip from text
     */
    public static function stripLastOccurrence(
        string $text,
        string $textToStrip,
        bool $stripRemainingText = false
    ): string {
        $index = strrpos($text, $textToStrip);
        if ($index === false) {
            return $text;
        }

        return substr($text, 0, $index)
            . ($stripRemainingText ? '' : substr($text, $index + 1));
    }

    /**
     * Insert text before last whitespace in string
     */
    public static function insertBeforeLastWhitespace(string $text, string $textToInsert): string
    {
        $index = strlen($text);

        if (!self::isWhitespace($text, $index - 1)) {
            return $text . $textToInsert;
        }

        while ($index > 0 && self::isWhitespace($text, $index - 1)) {
            $index--;
        }

        return substr($text, 0, $index) . $textToInsert . substr($text, $index);
    }

    /**
     * Remove characters at index
     */
    public static function removeAtIndex(string $text, int $start, int $count): string
    {
        return substr($text, 0, $start) . substr($text, $start + $count);
    }

    /**
     * Test whether string ends with comma or newline and optional whitespace
     */
    public static function endsWithCommaOrNewline(string $text): bool
    {
        return preg_match('/[,\n][ \t\r]*$/', $text) === 1;
    }

    /**
     * Get character at position in UTF-8 safe manner
     * If the byte at index is part of a multi-byte UTF-8 sequence, returns the full character
     */
    public static function charAt(string $text, int $index): string
    {
        if (!isset($text[$index])) {
            return '';
        }

        $byte = ord($text[$index]);

        // ASCII character (single byte)
        if ($byte < 0x80) {
            return $text[$index];
        }

        // Multi-byte UTF-8 character
        // Determine how many bytes this character uses
        if ($byte < 0xC0) {
            // Continuation byte, return single byte
            return $text[$index];
        } elseif ($byte < 0xE0) {
            // 2-byte character
            return substr($text, $index, 2);
        } elseif ($byte < 0xF0) {
            // 3-byte character
            return substr($text, $index, 3);
        } else {
            // 4-byte character
            return substr($text, $index, 4);
        }
    }

    /**
     * Get character code at position
     */
    public static function charCodeAt(string $text, int $index): int
    {
        if (!isset($text[$index])) {
            return 0;
        }
        return ord($text[$index]);
    }
}
