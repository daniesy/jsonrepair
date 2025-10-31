<?php

declare(strict_types=1);

use JsonRepair\Utils\JSONRepairError;
use PHPUnit\Framework\TestCase;
use function JsonRepair\jsonrepair;

final class JsonRepairTest extends TestCase
{
    // Helper function
    private function assertRepair(string $text): void
    {
        $this->assertSame($text, jsonrepair($text));
    }

    // parse valid JSON tests
    public function testParseFullJsonObject(): void
    {
        $text = '{"a":2.3e100,"b":"str","c":null,"d":false,"e":[1,2,3]}';
        $this->assertSame($text, jsonrepair($text));
    }

    public function testParseWhitespace(): void
    {
        $this->assertRepair("  { \n } \t ");
    }

    public function testParseObject(): void
    {
        $this->assertRepair('{}');
        $this->assertRepair('{  }');
        $this->assertRepair('{"a": {}}');
        $this->assertRepair('{"a": "b"}');
        $this->assertRepair('{"a": 2}');
    }

    public function testParseArray(): void
    {
        $this->assertRepair('[]');
        $this->assertRepair('[  ]');
        $this->assertRepair('[1,2,3]');
        $this->assertRepair('[ 1 , 2 , 3 ]');
        $this->assertRepair('[1,2,[3,4,5]]');
        $this->assertRepair('[{}]');
        $this->assertRepair('{"a":[]}');
        $this->assertRepair('[1, "hi", true, false, null, {}, []]');
    }

    public function testParseNumber(): void
    {
        $this->assertRepair('23');
        $this->assertRepair('0');
        $this->assertRepair('0e+2');
        $this->assertRepair('0.0');
        $this->assertRepair('-0');
        $this->assertRepair('2.3');
        $this->assertRepair('2300e3');
        $this->assertRepair('2300e+3');
        $this->assertRepair('2300e-3');
        $this->assertRepair('-2');
        $this->assertRepair('2e-3');
        $this->assertRepair('2.3e-3');
    }

    public function testParseString(): void
    {
        $this->assertRepair('"str"');
        $this->assertRepair('"\\"\\\\\\/\\b\\f\\n\\r\\t"');
        $this->assertRepair('"\\u260E"');
    }

    public function testParseKeywords(): void
    {
        $this->assertRepair('true');
        $this->assertRepair('false');
        $this->assertRepair('null');
    }

    public function testCorrectlyHandleStringsEqualingAJsonDelimiter(): void
    {
        $this->assertRepair('""');
        $this->assertRepair('"["');
        $this->assertRepair('"]"');
        $this->assertRepair('"{"');
        $this->assertRepair('"}"');
        $this->assertRepair('":"');
        $this->assertRepair('","');
    }

    public function testSupportsUnicodeCharactersInAString(): void
    {
        $this->assertSame('"★"', jsonrepair('"★"'));
        $this->assertSame('"😀"', jsonrepair('"😀"'));
        $this->assertSame('"йнформация"', jsonrepair('"йнформация"'));
    }

    public function testSupportsEscapedUnicodeCharactersInAString(): void
    {
        $this->assertSame('"\\u2605"', jsonrepair('"\\u2605"'));
        $this->assertSame('"\\u2605A"', jsonrepair('"\\u2605A"'));
        $this->assertSame('"\\ud83d\\ude00"', jsonrepair('"\\ud83d\\ude00"'));
    }

    public function testSupportsUnicodeCharactersInAKey(): void
    {
        $this->assertSame('{"★":true}', jsonrepair('{"★":true}'));
        $this->assertSame('{"😀":true}', jsonrepair('{"😀":true}'));
    }

    // repair invalid JSON tests
    public function testShouldAddMissingQuotes(): void
    {
        $this->assertSame('"abc"', jsonrepair('abc'));
        $this->assertSame('"hello   world"', jsonrepair('hello   world'));
        $this->assertSame("{\n\"message\": \"hello world\"\n}", jsonrepair("{\nmessage: hello world\n}"));
        $this->assertSame('{"a":2}', jsonrepair('{a:2}'));
        $this->assertSame('{"a": 2}', jsonrepair('{a: 2}'));
        $this->assertSame('{"2": 2}', jsonrepair('{2: 2}'));
        $this->assertSame('{"true": 2}', jsonrepair('{true: 2}'));
        $this->assertSame("{\n  \"a\": 2\n}", jsonrepair("{\n  a: 2\n}"));
        $this->assertSame('["a","b"]', jsonrepair('[a,b]'));
        $this->assertSame("[\n\"a\",\n\"b\"\n]", jsonrepair("[\na,\nb\n]"));
    }

    public function testShouldRepairAnUnquotedUrl(): void
    {
        $this->assertSame('"https://www.bible.com/"', jsonrepair('https://www.bible.com/'));
        $this->assertSame('{"url":"https://www.bible.com/"}', jsonrepair('{url:https://www.bible.com/}'));
        $this->assertSame('{"url":"https://www.bible.com/","id":2}', jsonrepair('{url:https://www.bible.com/,"id":2}'));
        $this->assertSame('["https://www.bible.com/"]', jsonrepair('[https://www.bible.com/]'));
        $this->assertSame('["https://www.bible.com/",2]', jsonrepair('[https://www.bible.com/,2]'));
    }

    public function testShouldRepairAnUrlWithMissingEndQuote(): void
    {
        $this->assertSame('"https://www.bible.com/"', jsonrepair('"https://www.bible.com/'));
        $this->assertSame('{"url":"https://www.bible.com/"}', jsonrepair('{"url":"https://www.bible.com/}'));
        $this->assertSame('{"url":"https://www.bible.com/","id":2}', jsonrepair('{"url":"https://www.bible.com/,"id":2}'));
        $this->assertSame('["https://www.bible.com/"]', jsonrepair('["https://www.bible.com/]'));
        $this->assertSame('["https://www.bible.com/",2]', jsonrepair('["https://www.bible.com/,2]'));
    }

    public function testShouldAddMissingEndQuote(): void
    {
        $this->assertSame('"abc"', jsonrepair('"abc'));
        $this->assertSame('"abc"', jsonrepair("'abc"));
        $this->assertSame('"12:20"', jsonrepair('"12:20'));
        $this->assertSame('{"time":"12:20"}', jsonrepair('{"time":"12:20}'));
        $this->assertSame('{"date":"2024-10-18T18:35:22.229Z"}', jsonrepair('{"date":2024-10-18T18:35:22.229Z}'));
        $this->assertSame('"She said:"', jsonrepair('"She said:'));
        $this->assertSame('{"text": "She said:"}', jsonrepair('{"text": "She said:'));
        $this->assertSame('["hello", "world"]', jsonrepair('["hello, world]'));
        $this->assertSame('["hello","world"]', jsonrepair('["hello,"world"]'));
        $this->assertSame('{"a":"b"}', jsonrepair('{"a":"b}'));
        $this->assertSame('{"a":"b","c":"d"}', jsonrepair('{"a":"b,"c":"d"}'));
        $this->assertSame('{"a":"b,c","d":"e"}', jsonrepair('{"a":"b,c,"d":"e"}'));
        $this->assertSame('{"a":"b,c","d":"e"}', jsonrepair('{a:"b,c,"d":"e"}'));
        $this->assertSame('["b","c"]', jsonrepair('["b,c,]'));
        $this->assertSame('"abc"', jsonrepair("\u{2018}abc"));
        $this->assertSame('"it\'s working"', jsonrepair('"it\'s working'));
        $this->assertSame('["abcdef"]', jsonrepair('["abc+/*comment*/"def"]'));
        $this->assertSame('["abcdef"]', jsonrepair('["abc/*comment*/+"def"]'));
        $this->assertSame('["abc","def"]', jsonrepair('["abc,/*comment*/"def"]'));
    }

    public function testShouldRepairTruncatedJson(): void
    {
        $this->assertSame('"foo"', jsonrepair('"foo'));
        $this->assertSame('[]', jsonrepair('['));
        $this->assertSame('["foo"]', jsonrepair('["foo'));
        $this->assertSame('["foo"]', jsonrepair('["foo"'));
        $this->assertSame('["foo"]', jsonrepair('["foo",'));
        $this->assertSame('{"foo":"bar"}', jsonrepair('{"foo":"bar"'));
        $this->assertSame('{"foo":"bar"}', jsonrepair('{"foo":"bar'));
        $this->assertSame('{"foo":null}', jsonrepair('{"foo":'));
        $this->assertSame('{"foo":null}', jsonrepair('{"foo"'));
        $this->assertSame('{"foo":null}', jsonrepair('{"foo'));
        $this->assertSame('{}', jsonrepair('{'));
        $this->assertSame('2.0', jsonrepair('2.'));
        $this->assertSame('2e0', jsonrepair('2e'));
        $this->assertSame('2e+0', jsonrepair('2e+'));
        $this->assertSame('2e-0', jsonrepair('2e-'));
        $this->assertSame('{"foo":"bar"}', jsonrepair('{"foo":"bar\\u20'));
        $this->assertSame('""', jsonrepair('"\\u'));
        $this->assertSame('""', jsonrepair('"\\u2'));
        $this->assertSame('""', jsonrepair('"\\u260'));
        $this->assertSame('"\\u2605"', jsonrepair('"\\u2605'));
        $this->assertSame('{"s": null}', jsonrepair('{"s \\ud'));
        $this->assertSame('{"message": "it\'s working"}', jsonrepair('{"message": "it\'s working'));
        $this->assertSame('{"text":"Hello Sergey,I hop"}', jsonrepair('{"text":"Hello Sergey,I hop'));
        $this->assertSame('{"message": "with, multiple, commma\'s, you see?"}', jsonrepair('{"message": "with, multiple, commma\'s, you see?'));
    }

    public function testShouldRepairEllipsisInAnArray(): void
    {
        $this->assertSame('[1,2,3]', jsonrepair('[1,2,3,...]'));
        $this->assertSame('[1, 2, 3  ]', jsonrepair('[1, 2, 3, ... ]'));
        $this->assertSame('[1,2,3]', jsonrepair('[1,2,3,/*comment1*/.../*comment2*/]'));
        $this->assertSame("[\n  1,\n  2,\n  3\n    \n]", jsonrepair("[\n  1,\n  2,\n  3,\n  /*comment1*/  .../*comment2*/\n]"));
        $this->assertSame('{"array":[1,2,3]}', jsonrepair('{"array":[1,2,3,...]}'));
        $this->assertSame('[1,2,3,9]', jsonrepair('[1,2,3,...,9]'));
        $this->assertSame('[7,8,9]', jsonrepair('[...,7,8,9]'));
        $this->assertSame('[ 7,8,9]', jsonrepair('[..., 7,8,9]'));
        $this->assertSame('[]', jsonrepair('[...]'));
        $this->assertSame('[  ]', jsonrepair('[ ... ]'));
    }

    public function testShouldRepairEllipsisInAnObject(): void
    {
        $this->assertSame('{"a":2,"b":3}', jsonrepair('{"a":2,"b":3,...}'));
        $this->assertSame('{"a":2,"b":3}', jsonrepair('{"a":2,"b":3,/*comment1*/.../*comment2*/}'));
        $this->assertSame("{\n  \"a\":2,\n  \"b\":3\n  \n}", jsonrepair("{\n  \"a\":2,\n  \"b\":3,\n  /*comment1*/.../*comment2*/\n}"));
        $this->assertSame('{"a":2,"b":3  }', jsonrepair('{"a":2,"b":3, ... }'));
        $this->assertSame('{"nested":{"a":2,"b":3  }}', jsonrepair('{"nested":{"a":2,"b":3, ... }}'));
        $this->assertSame('{"a":2,"b":3,"z":26}', jsonrepair('{"a":2,"b":3,...,"z":26}'));
        $this->assertSame('{}', jsonrepair('{...}'));
        $this->assertSame('{  }', jsonrepair('{ ... }'));
    }

    public function testShouldAddMissingStartQuote(): void
    {
        $this->assertSame('"abc"', jsonrepair('abc"'));
        $this->assertSame('["a","b"]', jsonrepair('[a","b"]'));
        $this->assertSame('["a","b"]', jsonrepair('[a",b"]'));
        $this->assertSame('{"a":"foo","b":"bar"}', jsonrepair('{"a":"foo","b":"bar"}'));
        $this->assertSame('{"a":"foo","b":"bar"}', jsonrepair('{a":"foo","b":"bar"}'));
        $this->assertSame('{"a":"foo","b":"bar"}', jsonrepair('{"a":"foo",b":"bar"}'));
        $this->assertSame('{"a":"foo","b":"bar"}', jsonrepair('{"a":foo","b":"bar"}'));
    }

    public function testShouldStopAtTheFirstNextReturnWhenMissingAnEndQuote(): void
    {
        $this->assertSame("[\n\"abc\",\n\"def\"\n]", jsonrepair("[\n\"abc,\n\"def\"\n]"));
        $this->assertSame("[\n\"abc\",  \n\"def\"\n]", jsonrepair("[\n\"abc,  \n\"def\"\n]"));
        $this->assertSame("[\"abc\"]\n", jsonrepair("[\"abc]\n"));
        $this->assertSame("[\"abc\"  ]\n", jsonrepair("[\"abc  ]\n"));
        $this->assertSame("[\n[\n\"abc\"\n]\n]\n", jsonrepair("[\n[\n\"abc\n]\n]\n"));
    }

    public function testShouldReplaceSingleQuotesWithDoubleQuotes(): void
    {
        $this->assertSame('{"a":2}', jsonrepair("{'a':2}"));
        $this->assertSame('{"a":"foo"}', jsonrepair("{'a':'foo'}"));
        $this->assertSame('{"a":"foo"}', jsonrepair('{"a":\'foo\'}'));
        $this->assertSame('{"a":"foo","b":"bar"}', jsonrepair("{a:'foo',b:'bar'}"));
    }

    public function testShouldReplaceSpecialQuotesWithDoubleQuotes(): void
    {
        $this->assertSame('{"a":"b"}', jsonrepair('{"a":"b"}'));
        $this->assertSame('{"a":"b"}', jsonrepair("{'a':'b'}"));
        $this->assertSame('{"a":"b"}', jsonrepair('{`a´:`b´}'));
    }

    public function testShouldNotReplaceSpecialQuotesInsideANormalString(): void
    {
        $input1 = '"Rounded ' . "\u{201C}" . ' quote"';
        $expected1 = '"Rounded ' . "\u{201C}" . ' quote"';
        $this->assertSame($expected1, jsonrepair($input1));

        $input2 = "'Rounded " . "\u{201C}" . " quote'";
        $expected2 = '"Rounded ' . "\u{201C}" . ' quote"';
        $this->assertSame($expected2, jsonrepair($input2));

        $input3 = '"Rounded ' . "\u{2018}" . ' quote"';
        $expected3 = '"Rounded ' . "\u{2018}" . ' quote"';
        $this->assertSame($expected3, jsonrepair($input3));

        $input4 = "'" . 'Rounded ' . "\u{2018}" . " quote'";
        $expected4 = '"Rounded ' . "\u{2018}" . ' quote"';
        $this->assertSame($expected4, jsonrepair($input4));

        $this->assertSame('"Double \\" quote"', jsonrepair("'Double \" quote'"));
    }

    public function testShouldNotCrashWhenRepairingQuotes(): void
    {
        $this->assertSame("{\"pattern\": \"\u{2019}\"}", jsonrepair("{pattern: '\u{2019}'}"));
    }

    public function testShouldLeaveStringContentUntouched(): void
    {
        $this->assertSame('"{a:b}"', jsonrepair('"{a:b}"'));
    }

    public function testShouldAddRemoveEscapeCharacters(): void
    {
        $this->assertSame('"foo\'bar"', jsonrepair('"foo\'bar"'));
        $this->assertSame('"foo\\"bar"', jsonrepair('"foo\\"bar"'));
        $this->assertSame('"foo\\"bar"', jsonrepair("'foo\"bar'"));
        $this->assertSame('"foo\'bar"', jsonrepair("'foo\\'bar'"));
        $this->assertSame('"foo\'bar"', jsonrepair('"foo\\\'bar"'));
        $this->assertSame('"a"', jsonrepair('"\\a"'));
    }

    public function testShouldRepairAMissingObjectValue(): void
    {
        $this->assertSame('{"a":null}', jsonrepair('{"a":}'));
        $this->assertSame('{"a":null,"b":2}', jsonrepair('{"a":,"b":2}'));
        $this->assertSame('{"a":null}', jsonrepair('{"a":'));
    }

    public function testShouldRepairUndefinedValues(): void
    {
        $this->assertSame('{"a":null}', jsonrepair('{"a":undefined}'));
        $this->assertSame('[null]', jsonrepair('[undefined]'));
        $this->assertSame('null', jsonrepair('undefined'));
    }

    public function testShouldEscapeUnescapedControlCharacters(): void
    {
        $this->assertSame('"hello\\bworld"', jsonrepair("\"hello\bworld\""));
        $this->assertSame('"hello\\fworld"', jsonrepair("\"hello\fworld\""));
        $this->assertSame('"hello\\nworld"', jsonrepair("\"hello\nworld\""));
        $this->assertSame('"hello\\rworld"', jsonrepair("\"hello\rworld\""));
        $this->assertSame('"hello\\tworld"', jsonrepair("\"hello\tworld\""));
        $this->assertSame('{"key\\nafter": "foo"}', jsonrepair("{\"key\nafter\": \"foo\"}"));
        $this->assertSame('["hello\\nworld"]', jsonrepair("[\"hello\nworld\"]"));
        $this->assertSame('["hello\\nworld"  ]', jsonrepair("[\"hello\nworld\"  ]"));
        $this->assertSame("[\"hello\\nworld\"\n]", jsonrepair("[\"hello\nworld\"\n]"));
    }

    public function testShouldEscapeUnescapedDoubleQuotes(): void
    {
        $this->assertSame('"The TV has a 24\\" screen"', jsonrepair('"The TV has a 24" screen"'));
        $this->assertSame('{"key": "apple \\"bee\\" carrot"}', jsonrepair('{"key": "apple "bee" carrot"}'));
        $this->assertSame('[",",":"]', jsonrepair('[",",":"]'));
        $this->assertSame('["a", 2]', jsonrepair('["a" 2]'));
        $this->assertSame('["a", 2]', jsonrepair('["a" 2'));
        $this->assertSame('[",", 2]', jsonrepair('["," 2'));
    }

    public function testShouldReplaceSpecialWhiteSpaceCharacters(): void
    {
        $this->assertSame("{\"a\": \"foo\u{00A0}bar\"}", jsonrepair("{\"a\":\u{00A0}\"foo\u{00A0}bar\"}"));
        $this->assertSame('{"a": "foo"}', jsonrepair("{\"a\":\u{202F}\"foo\"}"));
        $this->assertSame('{"a": "foo"}', jsonrepair("{\"a\":\u{205F}\"foo\"}"));
        $this->assertSame('{"a": "foo"}', jsonrepair("{\"a\":\u{3000}\"foo\"}"));
    }

    public function testShouldReplaceNonNormalizedLeftRightQuotes(): void
    {
        $this->assertSame('"foo"', jsonrepair("\u{2018}foo\u{2019}"));
        $this->assertSame('"foo"', jsonrepair("\u{201C}foo\u{201D}"));
        $this->assertSame('"foo"', jsonrepair("\u{0060}foo\u{00B4}"));
        $this->assertSame('"foo"', jsonrepair("\u{0060}foo'"));
    }

    public function testShouldRemoveBlockComments(): void
    {
        $this->assertSame(' {}', jsonrepair('/* foo */ {}'));
        $this->assertSame('{}  ', jsonrepair('{} /* foo */ '));
        $this->assertSame('{} ', jsonrepair('{} /* foo '));
        $this->assertSame("\n\n{}", jsonrepair("\n/* foo */\n{}"));
        $this->assertSame('{"a":"foo","b":"bar"}', jsonrepair('{"a":"foo",/*hello*/"b":"bar"}'));
        $this->assertSame('{"flag":true}', jsonrepair('{"flag":/*boolean*/true}'));
    }

    public function testShouldRemoveLineComments(): void
    {
        $this->assertSame('{} ', jsonrepair('{} // comment'));
        $this->assertSame("{\n\"a\":\"foo\",\n\"b\":\"bar\"\n}", jsonrepair("{\n\"a\":\"foo\",//hello\n\"b\":\"bar\"\n}"));
    }

    public function testShouldNotRemoveCommentsInsideAString(): void
    {
        $this->assertSame('"/* foo */"', jsonrepair('"/* foo */"'));
    }

    public function testShouldRemoveCommentsAfterAStringContainingADelimiter(): void
    {
        $this->assertSame('["a"]', jsonrepair('["a"/* foo */]'));
        $this->assertSame('["(a)"]', jsonrepair('["(a)"/* foo */]'));
        $this->assertSame('["a]"]', jsonrepair('["a]"/* foo */]'));
        $this->assertSame('{"a":"b"}', jsonrepair('{"a":"b"/* foo */}'));
        $this->assertSame('{"a":"(b)"}', jsonrepair('{"a":"(b)"/* foo */}'));
    }

    public function testShouldStripJsonpNotation(): void
    {
        $this->assertSame('{}', jsonrepair('callback_123({});'));
        $this->assertSame('[]', jsonrepair('callback_123([]);'));
        $this->assertSame('2', jsonrepair('callback_123(2);'));
        $this->assertSame('"foo"', jsonrepair('callback_123("foo");'));
        $this->assertSame('null', jsonrepair('callback_123(null);'));
        $this->assertSame('true', jsonrepair('callback_123(true);'));
        $this->assertSame('false', jsonrepair('callback_123(false);'));
        $this->assertSame('{}', jsonrepair('callback({}'));
        $this->assertSame(' {}', jsonrepair('/* foo bar */ callback_123 ({})'));
        $this->assertSame("\n{}", jsonrepair("/* foo bar */\ncallback_123({})"));
        $this->assertSame('   {}  ', jsonrepair('/* foo bar */ callback_123 (  {}  )'));
        $this->assertSame('     {}  ', jsonrepair('  /* foo bar */   callback_123({});  '));
        $this->assertSame("\n\n{}\n\n", jsonrepair("\n/* foo\nbar */\ncallback_123 ({});\n\n"));
    }

    public function testShouldStripMarkdownFencedCodeBlocks(): void
    {
        $this->assertSame("\n{\"a\":\"b\"}\n", jsonrepair("```\n{\"a\":\"b\"}\n```"));
        $this->assertSame("\n{\"a\":\"b\"}\n", jsonrepair("```json\n{\"a\":\"b\"}\n```"));
        $this->assertSame("\n{\"a\":\"b\"}\n", jsonrepair("```\n{\"a\":\"b\"}\n"));
        $this->assertSame("\n{\"a\":\"b\"}\n", jsonrepair("\n{\"a\":\"b\"}\n```"));
        $this->assertSame('{"a":"b"}', jsonrepair('```{"a":"b"}```'));
        $this->assertSame("\n[1,2,3]\n", jsonrepair("```\n[1,2,3]\n```"));
        $this->assertSame("\n{\"a\":\"b\"}\n", jsonrepair("```python\n{\"a\":\"b\"}\n```"));
        $this->assertSame("\n \n{\"a\":\"b\"}\n\n  ", jsonrepair("\n ```json\n{\"a\":\"b\"}\n```\n  "));
    }

    public function testShouldStripInvalidMarkdownFencedCodeBlocks(): void
    {
        $this->assertSame("\n{\"a\":\"b\"}\n", jsonrepair("[```\n{\"a\":\"b\"}\n```]"));
        $this->assertSame("\n{\"a\":\"b\"}\n", jsonrepair("[```json\n{\"a\":\"b\"}\n```]"));
        $this->assertSame("\n{\"a\":\"b\"}\n", jsonrepair("{```\n{\"a\":\"b\"}\n```}"));
        $this->assertSame("\n{\"a\":\"b\"}\n", jsonrepair("{```json\n{\"a\":\"b\"}\n```}"));
    }

    public function testShouldRepairEscapedStringContents(): void
    {
        $this->assertSame('"hello world"', jsonrepair('\\"hello world\\"'));
        $this->assertSame('"hello world"', jsonrepair('\\"hello world\\'));
        $this->assertSame('"hello \\"world\\""', jsonrepair('\\"hello \\\\"world\\\\"\\"'));
        $this->assertSame('["hello \\"world\\""]', jsonrepair('[\\"hello \\\\"world\\\\"\\"]'));
        $this->assertSame('{"stringified": "hello \\"world\\""}', jsonrepair('{\\"stringified\\": \\"hello \\\\"world\\\\"\\"}'  ));
        $this->assertSame('"hello"', jsonrepair('\\"hello"'));
    }

    public function testShouldStripALeadingCommaFromAnArray(): void
    {
        $this->assertSame('[1,2,3]', jsonrepair('[,1,2,3]'));
        $this->assertSame('[1,2,3]', jsonrepair('[/* a */,/* b */1,2,3]'));
        $this->assertSame('[ 1,2,3]', jsonrepair('[, 1,2,3]'));
        $this->assertSame('[  1,2,3]', jsonrepair('[ , 1,2,3]'));
    }

    public function testShouldStripALeadingCommaFromAnObject(): void
    {
        $this->assertSame('{"message": "hi"}', jsonrepair('{,"message": "hi"}'));
        $this->assertSame('{"message": "hi"}', jsonrepair('{/* a */,/* b */"message": "hi"}'));
        $this->assertSame('{ "message": "hi"}', jsonrepair('{ ,"message": "hi"}'));
        $this->assertSame('{ "message": "hi"}', jsonrepair('{, "message": "hi"}'));
    }

    public function testShouldStripTrailingCommasFromAnArray(): void
    {
        $this->assertSame('[1,2,3]', jsonrepair('[1,2,3,]'));
        $this->assertSame("[1,2,3\n]", jsonrepair("[1,2,3,\n]"));
        $this->assertSame("[1,2,3  \n  ]", jsonrepair("[1,2,3,  \n  ]"));
        $this->assertSame('[1,2,3]', jsonrepair('[1,2,3,/*foo*/]'));
        $this->assertSame('{"array":[1,2,3]}', jsonrepair('{"array":[1,2,3,]}'));
        $this->assertSame('"[1,2,3,]"', jsonrepair('"[1,2,3,]"'));
    }

    public function testShouldStripTrailingCommasFromAnObject(): void
    {
        $this->assertSame('{"a":2}', jsonrepair('{"a":2,}'));
        $this->assertSame('{"a":2    }', jsonrepair('{"a":2  ,  }'));
        $this->assertSame("{\"a\":2   \n }", jsonrepair("{\"a\":2  , \n }"));
        $this->assertSame('{"a":2}', jsonrepair('{"a":2/*foo*/,/*foo*/}'));
        $this->assertSame('{}', jsonrepair('{},'));
        $this->assertSame('"{a:2,}"', jsonrepair('"{a:2,}"'));
    }

    public function testShouldStripTrailingCommaAtTheEnd(): void
    {
        $this->assertSame('4', jsonrepair('4,'));
        $this->assertSame('4 ', jsonrepair('4 ,'));
        $this->assertSame('4  ', jsonrepair('4 , '));
        $this->assertSame('{"a":2}', jsonrepair('{"a":2},'));
        $this->assertSame('[1,2,3]', jsonrepair('[1,2,3],'));
    }

    public function testShouldAddAMissingClosingBraceForAnObject(): void
    {
        $this->assertSame('{}', jsonrepair('{'));
        $this->assertSame('{"a":2}', jsonrepair('{"a":2'));
        $this->assertSame('{"a":2}', jsonrepair('{"a":2,'));
        $this->assertSame('{"a":{"b":2}}', jsonrepair('{"a":{"b":2}'));
        $this->assertSame("{\n  \"a\":{\"b\":2\n}}", jsonrepair("{\n  \"a\":{\"b\":2\n}"));
        $this->assertSame('[{"b":2}]', jsonrepair('[{"b":2]'));
        $this->assertSame("[{\"b\":2}\n]", jsonrepair("[{\"b\":2\n]"));
        $this->assertSame('[{"i":1},{"i":2}]', jsonrepair('[{"i":1{"i":2}]'));
        $this->assertSame('[{"i":1},{"i":2}]', jsonrepair('[{"i":1,{"i":2}]'));
    }

    public function testShouldRemoveARedundantClosingBracketForAnObject(): void
    {
        $this->assertSame('{"a": 1}', jsonrepair('{"a": 1}}'));
        $this->assertSame('{"a": 1}', jsonrepair('{"a": 1}}]}'));
        $this->assertSame('{"a": 1 }        ', jsonrepair('{"a": 1 }  }  ]  }  '));
        $this->assertSame('{"a":2}', jsonrepair('{"a":2]'));
        $this->assertSame('{"a":2}', jsonrepair('{"a":2,]'));
        $this->assertSame('{}', jsonrepair('{}}}'));
        $this->assertSame('[2]', jsonrepair('[2,}'));
        $this->assertSame('[]', jsonrepair('[}'));
        $this->assertSame('{}', jsonrepair('{]'));
    }

    public function testShouldAddAMissingClosingBracketForAnArray(): void
    {
        $this->assertSame('[]', jsonrepair('['));
        $this->assertSame('[1,2,3]', jsonrepair('[1,2,3'));
        $this->assertSame('[1,2,3]', jsonrepair('[1,2,3,'));
        $this->assertSame('[[1,2,3]]', jsonrepair('[[1,2,3,'));
        $this->assertSame("{\n\"values\":[1,2,3]\n}", jsonrepair("{\n\"values\":[1,2,3\n}"));
        $this->assertSame("{\n\"values\":[1,2,3]}\n", jsonrepair("{\n\"values\":[1,2,3\n"));
    }

    public function testShouldStripMongodbDataTypes(): void
    {
        $this->assertSame('"2"', jsonrepair('NumberLong("2")'));
        $this->assertSame('{"_id":"123"}', jsonrepair('{"_id":ObjectId("123")}'));

        $mongoDocument = "{\n" .
            "   \"_id\" : ObjectId(\"123\"),\n" .
            "   \"isoDate\" : ISODate(\"2012-12-19T06:01:17.171Z\"),\n" .
            "   \"regularNumber\" : 67,\n" .
            "   \"long\" : NumberLong(\"2\"),\n" .
            "   \"long2\" : NumberLong(2),\n" .
            "   \"int\" : NumberInt(\"3\"),\n" .
            "   \"int2\" : NumberInt(3),\n" .
            "   \"decimal\" : NumberDecimal(\"4\"),\n" .
            "   \"decimal2\" : NumberDecimal(4)\n" .
            "}";

        $expectedJson = "{\n" .
            "   \"_id\" : \"123\",\n" .
            "   \"isoDate\" : \"2012-12-19T06:01:17.171Z\",\n" .
            "   \"regularNumber\" : 67,\n" .
            "   \"long\" : \"2\",\n" .
            "   \"long2\" : 2,\n" .
            "   \"int\" : \"3\",\n" .
            "   \"int2\" : 3,\n" .
            "   \"decimal\" : \"4\",\n" .
            "   \"decimal2\" : 4\n" .
            "}";

        $this->assertSame($expectedJson, jsonrepair($mongoDocument));
    }

    public function testShouldParseAnUnquotedString(): void
    {
        $this->assertSame('"hello world"', jsonrepair('hello world'));
        $this->assertSame('"She said: no way"', jsonrepair('She said: no way'));
        $this->assertSame('["This is C(2)", "This is F(3)"]', jsonrepair('["This is C(2)", "This is F(3)]'));
        $this->assertSame('["This is C(2)", "This is F(3)"]', jsonrepair('["This is C(2)", This is F(3)]'));
    }

    public function testShouldReplacePythonConstantsNoneTrueFalse(): void
    {
        $this->assertSame('true', jsonrepair('True'));
        $this->assertSame('false', jsonrepair('False'));
        $this->assertSame('null', jsonrepair('None'));
    }

    public function testShouldTurnUnknownSymbolsIntoAString(): void
    {
        $this->assertSame('"foo"', jsonrepair('foo'));
        $this->assertSame('[1,"foo",4]', jsonrepair('[1,foo,4]'));
        $this->assertSame('{"foo": "bar"}', jsonrepair('{foo: bar}'));
        $this->assertSame('"foo 2 bar"', jsonrepair('foo 2 bar'));
        $this->assertSame('{"greeting": "hello world"}', jsonrepair('{greeting: hello world}'));
        $this->assertSame("{\"greeting\": \"hello world\",\n\"next\": \"line\"}", jsonrepair("{greeting: hello world\nnext: \"line\"}"));
        $this->assertSame('{"greeting": "hello world!"}', jsonrepair('{greeting: hello world!}'));
    }

    public function testShouldTurnInvalidNumbersIntoStrings(): void
    {
        $this->assertSame('"ES2020"', jsonrepair('ES2020'));
        $this->assertSame('"0.0.1"', jsonrepair('0.0.1'));
        $this->assertSame('"746de9ad-d4ff-4c66-97d7-00a92ad46967"', jsonrepair('746de9ad-d4ff-4c66-97d7-00a92ad46967'));
        $this->assertSame('"234..5"', jsonrepair('234..5'));
        $this->assertSame('["0.0.1",2]', jsonrepair('[0.0.1,2]'));
        $this->assertSame('[2, "0.0.1 2"]', jsonrepair('[2 0.0.1 2]'));
        $this->assertSame('"2e3.4"', jsonrepair('2e3.4'));
    }

    public function testShouldRepairRegularExpressions(): void
    {
        $this->assertSame('{"regex": "/standalone-styles.css/"}', jsonrepair('{regex: /standalone-styles.css/}'));
        $this->assertSame('{"regex": "/with escape char \\/ [a-z]_/"}', jsonrepair('{regex: /with escape char \\/ [a-z]_/}'));
    }

    public function testShouldConcatenateStrings(): void
    {
        $this->assertSame('"hello world"', jsonrepair('"hello" + " world"'));
        $this->assertSame('"hello world"', jsonrepair("\"hello\" +\n \" world\""));
        $this->assertSame('"abc"', jsonrepair('"a"+"b"+"c"'));
        $this->assertSame('"hello world"', jsonrepair('"hello" + /*comment*/ " world"'));
        $this->assertSame("{\n  \"greeting\": \"helloworld\"\n}", jsonrepair("{\n  \"greeting\": 'hello' +\n 'world'\n}"));
        $this->assertSame('"hello world"', jsonrepair("\"hello +\n \" world\""));
        $this->assertSame('"hello"', jsonrepair('"hello +'));
        $this->assertSame('["hello"]', jsonrepair('["hello +]'));
    }

    public function testShouldRepairMissingCommaBetweenArrayItems(): void
    {
        $this->assertSame('{"array": [{},{}]}', jsonrepair('{"array": [{}{}]}'));
        $this->assertSame("{\"array\": [{},\n{}]}", jsonrepair("{\"array\": [{}\n{}]}"));
        $this->assertSame("{\"array\": [\n{},\n{}\n]}", jsonrepair("{\"array\": [\n{}\n{}\n]}"));
        $this->assertSame("{\"array\": [\n1,\n2\n]}", jsonrepair("{\"array\": [\n1\n2\n]}"));
        $this->assertSame("{\"array\": [\n\"a\",\n\"b\"\n]}", jsonrepair("{\"array\": [\n\"a\"\n\"b\"\n]}"));
        $this->assertSame("[\n{},\n{}\n]", jsonrepair("[\n{},\n{}\n]"));
    }

    public function testShouldRepairMissingCommaBetweenObjectProperties(): void
    {
        $this->assertSame("{\"a\":2,\n\"b\":3\n}", jsonrepair("{\"a\":2\n\"b\":3\n}"));
        $this->assertSame("{\"a\":2,\n\"b\":3,\n\"c\":4}", jsonrepair("{\"a\":2\n\"b\":3\nc:4}"));
        $this->assertSame("{\n  \"firstName\": \"John\",\n  \"lastName\": \"Smith\"}", jsonrepair("{\n  \"firstName\": \"John\"\n  lastName: Smith"));
        $this->assertSame("{\n  \"firstName\": \"John\",  \n  \"lastName\": \"Smith\"}", jsonrepair("{\n  \"firstName\": \"John\" /* comment */ \n  lastName: Smith"));
        $this->assertSame("{\n  \"firstName\": \"John\"\n  ,  \"lastName\": \"Smith\"}", jsonrepair("{\n  \"firstName\": \"John\"\n  ,  lastName: Smith"));
    }

    public function testShouldRepairNumbersAtTheEnd(): void
    {
        $this->assertSame('{"a":2.0}', jsonrepair('{"a":2.'));
        $this->assertSame('{"a":2e0}', jsonrepair('{"a":2e'));
        $this->assertSame('{"a":2e-0}', jsonrepair('{"a":2e-'));
        $this->assertSame('{"a":-0}', jsonrepair('{"a":-'));
        $this->assertSame('[2e0]', jsonrepair('[2e,'));
        $this->assertSame('[2e0] ', jsonrepair('[2e '));
        $this->assertSame('[-0]', jsonrepair('[-,'));
    }

    public function testShouldRepairMissingColonBetweenObjectKeyAndValue(): void
    {
        $this->assertSame('{"a": "b"}', jsonrepair('{"a" "b"}'));
        $this->assertSame('{"a": 2}', jsonrepair('{"a" 2}'));
        $this->assertSame('{"a": true}', jsonrepair('{"a" true}'));
        $this->assertSame('{"a": false}', jsonrepair('{"a" false}'));
        $this->assertSame('{"a": null}', jsonrepair('{"a" null}'));
        $this->assertSame('{"a":2}', jsonrepair('{"a"2}'));
        $this->assertSame("{\n\"a\": \"b\"\n}", jsonrepair("{\n\"a\" \"b\"\n}"));
        $this->assertSame('{"a": "b"}', jsonrepair('{"a" \'b\'}'));
        $this->assertSame('{"a": "b"}', jsonrepair("{'a' 'b'}"));
        $this->assertSame('{"a": "b"}', jsonrepair('{"a" "b"}'));
        $this->assertSame('{"a": "b"}', jsonrepair("{a 'b'}"));
        $this->assertSame('{"a": "b"}', jsonrepair('{a "b"}'));
    }

    public function testShouldRepairMissingACombinationOfCommaQuotesAndBrackets(): void
    {
        $this->assertSame("{\"array\": [\n\"a\",\n\"b\"\n]}", jsonrepair("{\"array\": [\na\nb\n]}"));
        $this->assertSame("[\n1,\n2\n]", jsonrepair("1\n2"));
        $this->assertSame("[\"a\",\"b\",\n\"c\"]", jsonrepair("[a,b\nc]"));
    }

    public function testShouldRepairNewlineSeparatedJsonForExampleFromMongodb(): void
    {
        $text = "/* 1 */\n{}\n\n/* 2 */\n{}\n\n/* 3 */\n{}\n";
        $expected = "[\n\n{},\n\n\n{},\n\n\n{}\n\n]";
        $this->assertSame($expected, jsonrepair($text));
    }

    public function testShouldRepairNewlineSeparatedJsonHavingCommas(): void
    {
        $text = "/* 1 */\n{},\n\n/* 2 */\n{},\n\n/* 3 */\n{}\n";
        $expected = "[\n\n{},\n\n\n{},\n\n\n{}\n\n]";
        $this->assertSame($expected, jsonrepair($text));
    }

    public function testShouldRepairNewlineSeparatedJsonHavingCommasAndTrailingComma(): void
    {
        $text = "/* 1 */\n{},\n\n/* 2 */\n{},\n\n/* 3 */\n{},\n";
        $expected = "[\n\n{},\n\n\n{},\n\n\n{}\n\n]";
        $this->assertSame($expected, jsonrepair($text));
    }

    public function testShouldRepairACommaSeparatedListWithValue(): void
    {
        $this->assertSame("[\n1,2,3\n]", jsonrepair('1,2,3'));
        $this->assertSame("[\n1,2,3\n]", jsonrepair('1,2,3,'));
        $this->assertSame("[\n1,\n2,\n3\n]", jsonrepair("1\n2\n3"));
        $this->assertSame("[\n\"a\",\n\"b\"\n]", jsonrepair("a\nb"));
        $this->assertSame("[\n\"a\",\"b\"\n]", jsonrepair('a,b'));
    }

    public function testShouldRepairANumberWithLeadingZero(): void
    {
        $this->assertSame('"0789"', jsonrepair('0789'));
        $this->assertSame('"000789"', jsonrepair('000789'));
        $this->assertSame('"001.2"', jsonrepair('001.2'));
        $this->assertSame('"002e3"', jsonrepair('002e3'));
        $this->assertSame('["0789"]', jsonrepair('[0789]'));
        $this->assertSame('{"value":"0789"}', jsonrepair('{value:0789}'));
    }

    public function testShouldThrowAnExceptionInCaseOfNonRepairableIssues(): void
    {
        $this->expectException(JSONRepairError::class);
        jsonrepair('');
    }

    public function testShouldThrowAnExceptionForInvalidComma(): void
    {
        $this->expectException(JSONRepairError::class);
        jsonrepair('{"a",');
    }

    public function testShouldThrowAnExceptionForMissingKey(): void
    {
        $this->expectException(JSONRepairError::class);
        jsonrepair('{:2}');
    }

    public function testShouldThrowAnExceptionForMultipleRootValues(): void
    {
        $this->expectException(JSONRepairError::class);
        jsonrepair('{"a":2}{}');
    }

    public function testShouldThrowAnExceptionForMismatchedBrackets(): void
    {
        $this->expectException(JSONRepairError::class);
        jsonrepair('{"a" ]');
    }

    public function testShouldThrowAnExceptionForTrailingText(): void
    {
        $this->expectException(JSONRepairError::class);
        jsonrepair('{"a":2}foo');
    }

    public function testShouldThrowAnExceptionForInvalidTextBeforeArray(): void
    {
        $this->expectException(JSONRepairError::class);
        jsonrepair('foo [');
    }

    public function testShouldThrowAnExceptionForInvalidUnicodeEscape(): void
    {
        $this->expectException(JSONRepairError::class);
        jsonrepair('"\\u26"');
    }

    public function testShouldThrowAnExceptionForInvalidUnicodeCharacter(): void
    {
        $this->expectException(JSONRepairError::class);
        jsonrepair('"\\uZ000"');
    }
}
