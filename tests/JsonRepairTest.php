<?php

declare(strict_types=1);

use JsonRepair\Utils\JSONRepairError;
use function JsonRepair\jsonrepair;

// Helper function
function assertRepair(string $text): void
{
    expect(jsonrepair($text))->toBe($text);
}

describe('parse valid JSON', function () {
    test('parse full JSON object', function () {
        $text = '{"a":2.3e100,"b":"str","c":null,"d":false,"e":[1,2,3]}';
        expect(jsonrepair($text))->toBe($text);
    });

    test('parse whitespace', function () {
        assertRepair("  { \n } \t ");
    });

    test('parse object', function () {
        assertRepair('{}');
        assertRepair('{  }');
        assertRepair('{"a": {}}');
        assertRepair('{"a": "b"}');
        assertRepair('{"a": 2}');
    });

    test('parse array', function () {
        assertRepair('[]');
        assertRepair('[  ]');
        assertRepair('[1,2,3]');
        assertRepair('[ 1 , 2 , 3 ]');
        assertRepair('[1,2,[3,4,5]]');
        assertRepair('[{}]');
        assertRepair('{"a":[]}');
        assertRepair('[1, "hi", true, false, null, {}, []]');
    });

    test('parse number', function () {
        assertRepair('23');
        assertRepair('0');
        assertRepair('0e+2');
        assertRepair('0.0');
        assertRepair('-0');
        assertRepair('2.3');
        assertRepair('2300e3');
        assertRepair('2300e+3');
        assertRepair('2300e-3');
        assertRepair('-2');
        assertRepair('2e-3');
        assertRepair('2.3e-3');
    });

    test('parse string', function () {
        assertRepair('"str"');
        assertRepair('"\\"\\\\\\/\\b\\f\\n\\r\\t"');
        assertRepair('"\\u260E"');
    });

    test('parse keywords', function () {
        assertRepair('true');
        assertRepair('false');
        assertRepair('null');
    });

    test('correctly handle strings equaling a JSON delimiter', function () {
        assertRepair('""');
        assertRepair('"["');
        assertRepair('"]"');
        assertRepair('"{"');
        assertRepair('"}"');
        assertRepair('":"');
        assertRepair('","');
    });

    test('supports unicode characters in a string', function () {
        expect(jsonrepair('"★"'))->toBe('"★"');
        expect(jsonrepair('"😀"'))->toBe('"😀"');
        expect(jsonrepair('"йнформация"'))->toBe('"йнформация"');
    });

    test('supports escaped unicode characters in a string', function () {
        expect(jsonrepair('"\\u2605"'))->toBe('"\\u2605"');
        expect(jsonrepair('"\\u2605A"'))->toBe('"\\u2605A"');
        expect(jsonrepair('"\\ud83d\\ude00"'))->toBe('"\\ud83d\\ude00"');
    });

    test('supports unicode characters in a key', function () {
        expect(jsonrepair('{"★":true}'))->toBe('{"★":true}');
        expect(jsonrepair('{"😀":true}'))->toBe('{"😀":true}');
    });
});

describe('repair invalid JSON', function () {
    test('should add missing quotes', function () {
        expect(jsonrepair('abc'))->toBe('"abc"');
        expect(jsonrepair('hello   world'))->toBe('"hello   world"');
        expect(jsonrepair("{\nmessage: hello world\n}"))->toBe("{\n\"message\": \"hello world\"\n}");
        expect(jsonrepair('{a:2}'))->toBe('{"a":2}');
        expect(jsonrepair('{a: 2}'))->toBe('{"a": 2}');
        expect(jsonrepair('{2: 2}'))->toBe('{"2": 2}');
        expect(jsonrepair('{true: 2}'))->toBe('{"true": 2}');
        expect(jsonrepair("{\n  a: 2\n}"))->toBe("{\n  \"a\": 2\n}");
        expect(jsonrepair('[a,b]'))->toBe('["a","b"]');
        expect(jsonrepair("[\na,\nb\n]"))->toBe("[\n\"a\",\n\"b\"\n]");
    });

    test('should repair an unquoted url', function () {
        expect(jsonrepair('https://www.bible.com/'))->toBe('"https://www.bible.com/"');
        expect(jsonrepair('{url:https://www.bible.com/}'))->toBe('{"url":"https://www.bible.com/"}');
        expect(jsonrepair('{url:https://www.bible.com/,"id":2}'))->toBe('{"url":"https://www.bible.com/","id":2}');
        expect(jsonrepair('[https://www.bible.com/]'))->toBe('["https://www.bible.com/"]');
        expect(jsonrepair('[https://www.bible.com/,2]'))->toBe('["https://www.bible.com/",2]');
    });

    test('should repair an url with missing end quote', function () {
        expect(jsonrepair('"https://www.bible.com/'))->toBe('"https://www.bible.com/"');
        expect(jsonrepair('{"url":"https://www.bible.com/}'))->toBe('{"url":"https://www.bible.com/"}');
        expect(jsonrepair('{"url":"https://www.bible.com/,"id":2}'))->toBe('{"url":"https://www.bible.com/","id":2}');
        expect(jsonrepair('["https://www.bible.com/]'))->toBe('["https://www.bible.com/"]');
        expect(jsonrepair('["https://www.bible.com/,2]'))->toBe('["https://www.bible.com/",2]');
    });

    test('should add missing end quote', function () {
        expect(jsonrepair('"abc'))->toBe('"abc"');
        expect(jsonrepair("'abc"))->toBe('"abc"');
        expect(jsonrepair('"12:20'))->toBe('"12:20"');
        expect(jsonrepair('{"time":"12:20}'))->toBe('{"time":"12:20"}');
        expect(jsonrepair('{"date":2024-10-18T18:35:22.229Z}'))->toBe('{"date":"2024-10-18T18:35:22.229Z"}');
        expect(jsonrepair('"She said:'))->toBe('"She said:"');
        expect(jsonrepair('{"text": "She said:'))->toBe('{"text": "She said:"}');
        expect(jsonrepair('["hello, world]'))->toBe('["hello", "world"]');
        expect(jsonrepair('["hello,"world"]'))->toBe('["hello","world"]');
        expect(jsonrepair('{"a":"b}'))->toBe('{"a":"b"}');
        expect(jsonrepair('{"a":"b,"c":"d"}'))->toBe('{"a":"b","c":"d"}');
        expect(jsonrepair('{"a":"b,c,"d":"e"}'))->toBe('{"a":"b,c","d":"e"}');
        expect(jsonrepair('{a:"b,c,"d":"e"}'))->toBe('{"a":"b,c","d":"e"}');
        expect(jsonrepair('["b,c,]'))->toBe('["b","c"]');
        expect(jsonrepair("\u{2018}abc"))->toBe('"abc"');
        expect(jsonrepair('"it\'s working'))->toBe('"it\'s working"');
        expect(jsonrepair('["abc+/*comment*/"def"]'))->toBe('["abcdef"]');
        expect(jsonrepair('["abc/*comment*/+"def"]'))->toBe('["abcdef"]');
        expect(jsonrepair('["abc,/*comment*/"def"]'))->toBe('["abc","def"]');
    });

    test('should repair truncated JSON', function () {
        expect(jsonrepair('"foo'))->toBe('"foo"');
        expect(jsonrepair('['))->toBe('[]');
        expect(jsonrepair('["foo'))->toBe('["foo"]');
        expect(jsonrepair('["foo"'))->toBe('["foo"]');
        expect(jsonrepair('["foo",'))->toBe('["foo"]');
        expect(jsonrepair('{"foo":"bar"'))->toBe('{"foo":"bar"}');
        expect(jsonrepair('{"foo":"bar'))->toBe('{"foo":"bar"}');
        expect(jsonrepair('{"foo":'))->toBe('{"foo":null}');
        expect(jsonrepair('{"foo"'))->toBe('{"foo":null}');
        expect(jsonrepair('{"foo'))->toBe('{"foo":null}');
        expect(jsonrepair('{'))->toBe('{}');
        expect(jsonrepair('2.'))->toBe('2.0');
        expect(jsonrepair('2e'))->toBe('2e0');
        expect(jsonrepair('2e+'))->toBe('2e+0');
        expect(jsonrepair('2e-'))->toBe('2e-0');
        expect(jsonrepair('{"foo":"bar\\u20'))->toBe('{"foo":"bar"}');
        expect(jsonrepair('"\\u'))->toBe('""');
        expect(jsonrepair('"\\u2'))->toBe('""');
        expect(jsonrepair('"\\u260'))->toBe('""');
        expect(jsonrepair('"\\u2605'))->toBe('"\\u2605"');
        expect(jsonrepair('{"s \\ud'))->toBe('{"s": null}');
        expect(jsonrepair('{"message": "it\'s working'))->toBe('{"message": "it\'s working"}');
        expect(jsonrepair('{"text":"Hello Sergey,I hop'))->toBe('{"text":"Hello Sergey,I hop"}');
        expect(jsonrepair('{"message": "with, multiple, commma\'s, you see?'))->toBe('{"message": "with, multiple, commma\'s, you see?"}');
    });

    test('should repair ellipsis in an array', function () {
        expect(jsonrepair('[1,2,3,...]'))->toBe('[1,2,3]');
        expect(jsonrepair('[1, 2, 3, ... ]'))->toBe('[1, 2, 3  ]');
        expect(jsonrepair('[1,2,3,/*comment1*/.../*comment2*/]'))->toBe('[1,2,3]');
        expect(jsonrepair("[\n  1,\n  2,\n  3,\n  /*comment1*/  .../*comment2*/\n]"))->toBe("[\n  1,\n  2,\n  3\n    \n]");
        expect(jsonrepair('{"array":[1,2,3,...]}'))->toBe('{"array":[1,2,3]}');
        expect(jsonrepair('[1,2,3,...,9]'))->toBe('[1,2,3,9]');
        expect(jsonrepair('[...,7,8,9]'))->toBe('[7,8,9]');
        expect(jsonrepair('[..., 7,8,9]'))->toBe('[ 7,8,9]');
        expect(jsonrepair('[...]'))->toBe('[]');
        expect(jsonrepair('[ ... ]'))->toBe('[  ]');
    });

    test('should repair ellipsis in an object', function () {
        expect(jsonrepair('{"a":2,"b":3,...}'))->toBe('{"a":2,"b":3}');
        expect(jsonrepair('{"a":2,"b":3,/*comment1*/.../*comment2*/}'))->toBe('{"a":2,"b":3}');
        expect(jsonrepair("{\n  \"a\":2,\n  \"b\":3,\n  /*comment1*/.../*comment2*/\n}"))->toBe("{\n  \"a\":2,\n  \"b\":3\n  \n}");
        expect(jsonrepair('{"a":2,"b":3, ... }'))->toBe('{"a":2,"b":3  }');
        expect(jsonrepair('{"nested":{"a":2,"b":3, ... }}'))->toBe('{"nested":{"a":2,"b":3  }}');
        expect(jsonrepair('{"a":2,"b":3,...,"z":26}'))->toBe('{"a":2,"b":3,"z":26}');
        expect(jsonrepair('{...}'))->toBe('{}');
        expect(jsonrepair('{ ... }'))->toBe('{  }');
    });

    test('should add missing start quote', function () {
        expect(jsonrepair('abc"'))->toBe('"abc"');
        expect(jsonrepair('[a","b"]'))->toBe('["a","b"]');
        expect(jsonrepair('[a",b"]'))->toBe('["a","b"]');
        expect(jsonrepair('{"a":"foo","b":"bar"}'))->toBe('{"a":"foo","b":"bar"}');
        expect(jsonrepair('{a":"foo","b":"bar"}'))->toBe('{"a":"foo","b":"bar"}');
        expect(jsonrepair('{"a":"foo",b":"bar"}'))->toBe('{"a":"foo","b":"bar"}');
        expect(jsonrepair('{"a":foo","b":"bar"}'))->toBe('{"a":"foo","b":"bar"}');
    });

    test('should stop at the first next return when missing an end quote', function () {
        expect(jsonrepair("[\n\"abc,\n\"def\"\n]"))->toBe("[\n\"abc\",\n\"def\"\n]");
        expect(jsonrepair("[\n\"abc,  \n\"def\"\n]"))->toBe("[\n\"abc\",  \n\"def\"\n]");
        expect(jsonrepair("[\"abc]\n"))->toBe("[\"abc\"]\n");
        expect(jsonrepair("[\"abc  ]\n"))->toBe("[\"abc\"  ]\n");
        expect(jsonrepair("[\n[\n\"abc\n]\n]\n"))->toBe("[\n[\n\"abc\"\n]\n]\n");
    });

    test('should replace single quotes with double quotes', function () {
        expect(jsonrepair("{'a':2}"))->toBe('{"a":2}');
        expect(jsonrepair("{'a':'foo'}"))->toBe('{"a":"foo"}');
        expect(jsonrepair('{"a":\'foo\'}'))->toBe('{"a":"foo"}');
        expect(jsonrepair("{a:'foo',b:'bar'}"))->toBe('{"a":"foo","b":"bar"}');
    });

    test('should replace special quotes with double quotes', function () {
        expect(jsonrepair('{"a":"b"}'))->toBe('{"a":"b"}');
        expect(jsonrepair("{'a':'b'}"))->toBe('{"a":"b"}');
        expect(jsonrepair('{`a´:`b´}'))->toBe('{"a":"b"}');
    });

    test('should not replace special quotes inside a normal string', function () {
        $input1 = '"Rounded ' . "\u{201C}" . ' quote"';
        $expected1 = '"Rounded ' . "\u{201C}" . ' quote"';
        expect(jsonrepair($input1))->toBe($expected1);

        $input2 = "'Rounded " . "\u{201C}" . " quote'";
        $expected2 = '"Rounded ' . "\u{201C}" . ' quote"';
        expect(jsonrepair($input2))->toBe($expected2);

        $input3 = '"Rounded ' . "\u{2018}" . ' quote"';
        $expected3 = '"Rounded ' . "\u{2018}" . ' quote"';
        expect(jsonrepair($input3))->toBe($expected3);

        $input4 = "'" . 'Rounded ' . "\u{2018}" . " quote'";
        $expected4 = '"Rounded ' . "\u{2018}" . ' quote"';
        expect(jsonrepair($input4))->toBe($expected4);

        expect(jsonrepair("'Double \" quote'"))->toBe('"Double \\" quote"');
    });

    test('should not crash when repairing quotes', function () {
        expect(jsonrepair("{pattern: '\u{2019}'}"))->toBe("{\"pattern\": \"\u{2019}\"}");
    });

    test('should leave string content untouched', function () {
        expect(jsonrepair('"{a:b}"'))->toBe('"{a:b}"');
    });

    test('should add/remove escape characters', function () {
        expect(jsonrepair('"foo\'bar"'))->toBe('"foo\'bar"');
        expect(jsonrepair('"foo\\"bar"'))->toBe('"foo\\"bar"');
        expect(jsonrepair("'foo\"bar'"))->toBe('"foo\\"bar"');
        expect(jsonrepair("'foo\\'bar'"))->toBe('"foo\'bar"');
        expect(jsonrepair('"foo\\\'bar"'))->toBe('"foo\'bar"');
        expect(jsonrepair('"\\a"'))->toBe('"a"');
    });

    test('should repair a missing object value', function () {
        expect(jsonrepair('{"a":}'))->toBe('{"a":null}');
        expect(jsonrepair('{"a":,"b":2}'))->toBe('{"a":null,"b":2}');
        expect(jsonrepair('{"a":'))->toBe('{"a":null}');
    });

    test('should repair undefined values', function () {
        expect(jsonrepair('{"a":undefined}'))->toBe('{"a":null}');
        expect(jsonrepair('[undefined]'))->toBe('[null]');
        expect(jsonrepair('undefined'))->toBe('null');
    });

    test('should escape unescaped control characters', function () {
        expect(jsonrepair("\"hello\bworld\""))->toBe('"hello\\bworld"');
        expect(jsonrepair("\"hello\fworld\""))->toBe('"hello\\fworld"');
        expect(jsonrepair("\"hello\nworld\""))->toBe('"hello\\nworld"');
        expect(jsonrepair("\"hello\rworld\""))->toBe('"hello\\rworld"');
        expect(jsonrepair("\"hello\tworld\""))->toBe('"hello\\tworld"');
        expect(jsonrepair("{\"key\nafter\": \"foo\"}"))->toBe('{"key\\nafter": "foo"}');
        expect(jsonrepair("[\"hello\nworld\"]"))->toBe('["hello\\nworld"]');
        expect(jsonrepair("[\"hello\nworld\"  ]"))->toBe('["hello\\nworld"  ]');
        expect(jsonrepair("[\"hello\nworld\"\n]"))->toBe("[\"hello\\nworld\"\n]");
    });

    test('should escape unescaped double quotes', function () {
        expect(jsonrepair('"The TV has a 24" screen"'))->toBe('"The TV has a 24\\" screen"');
        expect(jsonrepair('{"key": "apple "bee" carrot"}'))->toBe('{"key": "apple \\"bee\\" carrot"}');
        expect(jsonrepair('[",",":"]'))->toBe('[",",":"]');
        expect(jsonrepair('["a" 2]'))->toBe('["a", 2]');
        expect(jsonrepair('["a" 2'))->toBe('["a", 2]');
        expect(jsonrepair('["," 2'))->toBe('[",", 2]');
    });

    test('should replace special white space characters', function () {
        expect(jsonrepair("{\"a\":\u{00A0}\"foo\u{00A0}bar\"}"))->toBe("{\"a\": \"foo\u{00A0}bar\"}");
        expect(jsonrepair("{\"a\":\u{202F}\"foo\"}"))->toBe('{"a": "foo"}');
        expect(jsonrepair("{\"a\":\u{205F}\"foo\"}"))->toBe('{"a": "foo"}');
        expect(jsonrepair("{\"a\":\u{3000}\"foo\"}"))->toBe('{"a": "foo"}');
    });

    test('should replace non normalized left/right quotes', function () {
        expect(jsonrepair("\u{2018}foo\u{2019}"))->toBe('"foo"');
        expect(jsonrepair("\u{201C}foo\u{201D}"))->toBe('"foo"');
        expect(jsonrepair("\u{0060}foo\u{00B4}"))->toBe('"foo"');
        expect(jsonrepair("\u{0060}foo'"))->toBe('"foo"');
    });

    test('should remove block comments', function () {
        expect(jsonrepair('/* foo */ {}'))->toBe(' {}');
        expect(jsonrepair('{} /* foo */ '))->toBe('{}  ');
        expect(jsonrepair('{} /* foo '))->toBe('{} ');
        expect(jsonrepair("\n/* foo */\n{}"))->toBe("\n\n{}");
        expect(jsonrepair('{"a":"foo",/*hello*/"b":"bar"}'))->toBe('{"a":"foo","b":"bar"}');
        expect(jsonrepair('{"flag":/*boolean*/true}'))->toBe('{"flag":true}');
    });

    test('should remove line comments', function () {
        expect(jsonrepair('{} // comment'))->toBe('{} ');
        expect(jsonrepair("{\n\"a\":\"foo\",//hello\n\"b\":\"bar\"\n}"))->toBe("{\n\"a\":\"foo\",\n\"b\":\"bar\"\n}");
    });

    test('should not remove comments inside a string', function () {
        expect(jsonrepair('"/* foo */"'))->toBe('"/* foo */"');
    });

    test('should remove comments after a string containing a delimiter', function () {
        expect(jsonrepair('["a"/* foo */]'))->toBe('["a"]');
        expect(jsonrepair('["(a)"/* foo */]'))->toBe('["(a)"]');
        expect(jsonrepair('["a]"/* foo */]'))->toBe('["a]"]');
        expect(jsonrepair('{"a":"b"/* foo */}'))->toBe('{"a":"b"}');
        expect(jsonrepair('{"a":"(b)"/* foo */}'))->toBe('{"a":"(b)"}');
    });

    test('should strip JSONP notation', function () {
        expect(jsonrepair('callback_123({});'))->toBe('{}');
        expect(jsonrepair('callback_123([]);'))->toBe('[]');
        expect(jsonrepair('callback_123(2);'))->toBe('2');
        expect(jsonrepair('callback_123("foo");'))->toBe('"foo"');
        expect(jsonrepair('callback_123(null);'))->toBe('null');
        expect(jsonrepair('callback_123(true);'))->toBe('true');
        expect(jsonrepair('callback_123(false);'))->toBe('false');
        expect(jsonrepair('callback({}'))->toBe('{}');
        expect(jsonrepair('/* foo bar */ callback_123 ({})'))->toBe(' {}');
        expect(jsonrepair("/* foo bar */\ncallback_123({})"))->toBe("\n{}");
        expect(jsonrepair('/* foo bar */ callback_123 (  {}  )'))->toBe('   {}  ');
        expect(jsonrepair('  /* foo bar */   callback_123({});  '))->toBe('     {}  ');
        expect(jsonrepair("\n/* foo\nbar */\ncallback_123 ({});\n\n"))->toBe("\n\n{}\n\n");
    });

    test('should strip markdown fenced code blocks', function () {
        expect(jsonrepair("```\n{\"a\":\"b\"}\n```"))->toBe("\n{\"a\":\"b\"}\n");
        expect(jsonrepair("```json\n{\"a\":\"b\"}\n```"))->toBe("\n{\"a\":\"b\"}\n");
        expect(jsonrepair("```\n{\"a\":\"b\"}\n"))->toBe("\n{\"a\":\"b\"}\n");
        expect(jsonrepair("\n{\"a\":\"b\"}\n```"))->toBe("\n{\"a\":\"b\"}\n");
        expect(jsonrepair('```{"a":"b"}```'))->toBe('{"a":"b"}');
        expect(jsonrepair("```\n[1,2,3]\n```"))->toBe("\n[1,2,3]\n");
        expect(jsonrepair("```python\n{\"a\":\"b\"}\n```"))->toBe("\n{\"a\":\"b\"}\n");
        expect(jsonrepair("\n ```json\n{\"a\":\"b\"}\n```\n  "))->toBe("\n \n{\"a\":\"b\"}\n\n  ");
    });

    test('should strip invalid markdown fenced code blocks', function () {
        expect(jsonrepair("[```\n{\"a\":\"b\"}\n```]"))->toBe("\n{\"a\":\"b\"}\n");
        expect(jsonrepair("[```json\n{\"a\":\"b\"}\n```]"))->toBe("\n{\"a\":\"b\"}\n");
        expect(jsonrepair("{```\n{\"a\":\"b\"}\n```}"))->toBe("\n{\"a\":\"b\"}\n");
        expect(jsonrepair("{```json\n{\"a\":\"b\"}\n```}"))->toBe("\n{\"a\":\"b\"}\n");
    });

    test('should repair escaped string contents', function () {
        expect(jsonrepair('\\"hello world\\"'))->toBe('"hello world"');
        expect(jsonrepair('\\"hello world\\'))->toBe('"hello world"');
        expect(jsonrepair('\\"hello \\\\"world\\\\"\\"'))->toBe('"hello \\"world\\""');
        expect(jsonrepair('[\\"hello \\\\"world\\\\"\\"]'))->toBe('["hello \\"world\\""]');
        expect(jsonrepair('{\\"stringified\\": \\"hello \\\\"world\\\\"\\"}'  ))->toBe('{"stringified": "hello \\"world\\""}');
        expect(jsonrepair('\\"hello"'))->toBe('"hello"');
    });

    test('should strip a leading comma from an array', function () {
        expect(jsonrepair('[,1,2,3]'))->toBe('[1,2,3]');
        expect(jsonrepair('[/* a */,/* b */1,2,3]'))->toBe('[1,2,3]');
        expect(jsonrepair('[, 1,2,3]'))->toBe('[ 1,2,3]');
        expect(jsonrepair('[ , 1,2,3]'))->toBe('[  1,2,3]');
    });

    test('should strip a leading comma from an object', function () {
        expect(jsonrepair('{,"message": "hi"}'))->toBe('{"message": "hi"}');
        expect(jsonrepair('{/* a */,/* b */"message": "hi"}'))->toBe('{"message": "hi"}');
        expect(jsonrepair('{ ,"message": "hi"}'))->toBe('{ "message": "hi"}');
        expect(jsonrepair('{, "message": "hi"}'))->toBe('{ "message": "hi"}');
    });

    test('should strip trailing commas from an array', function () {
        expect(jsonrepair('[1,2,3,]'))->toBe('[1,2,3]');
        expect(jsonrepair("[1,2,3,\n]"))->toBe("[1,2,3\n]");
        expect(jsonrepair("[1,2,3,  \n  ]"))->toBe("[1,2,3  \n  ]");
        expect(jsonrepair('[1,2,3,/*foo*/]'))->toBe('[1,2,3]');
        expect(jsonrepair('{"array":[1,2,3,]}'))->toBe('{"array":[1,2,3]}');
        expect(jsonrepair('"[1,2,3,]"'))->toBe('"[1,2,3,]"');
    });

    test('should strip trailing commas from an object', function () {
        expect(jsonrepair('{"a":2,}'))->toBe('{"a":2}');
        expect(jsonrepair('{"a":2  ,  }'))->toBe('{"a":2    }');
        expect(jsonrepair("{\"a\":2  , \n }"))->toBe("{\"a\":2   \n }");
        expect(jsonrepair('{"a":2/*foo*/,/*foo*/}'))->toBe('{"a":2}');
        expect(jsonrepair('{},'))->toBe('{}');
        expect(jsonrepair('"{a:2,}"'))->toBe('"{a:2,}"');
    });

    test('should strip trailing comma at the end', function () {
        expect(jsonrepair('4,'))->toBe('4');
        expect(jsonrepair('4 ,'))->toBe('4 ');
        expect(jsonrepair('4 , '))->toBe('4  ');
        expect(jsonrepair('{"a":2},'))->toBe('{"a":2}');
        expect(jsonrepair('[1,2,3],'))->toBe('[1,2,3]');
    });

    test('should add a missing closing brace for an object', function () {
        expect(jsonrepair('{'))->toBe('{}');
        expect(jsonrepair('{"a":2'))->toBe('{"a":2}');
        expect(jsonrepair('{"a":2,'))->toBe('{"a":2}');
        expect(jsonrepair('{"a":{"b":2}'))->toBe('{"a":{"b":2}}');
        expect(jsonrepair("{\n  \"a\":{\"b\":2\n}"))->toBe("{\n  \"a\":{\"b\":2\n}}");
        expect(jsonrepair('[{"b":2]'))->toBe('[{"b":2}]');
        expect(jsonrepair("[{\"b\":2\n]"))->toBe("[{\"b\":2}\n]");
        expect(jsonrepair('[{"i":1{"i":2}]'))->toBe('[{"i":1},{"i":2}]');
        expect(jsonrepair('[{"i":1,{"i":2}]'))->toBe('[{"i":1},{"i":2}]');
    });

    test('should remove a redundant closing bracket for an object', function () {
        expect(jsonrepair('{"a": 1}}'))->toBe('{"a": 1}');
        expect(jsonrepair('{"a": 1}}]}'))->toBe('{"a": 1}');
        expect(jsonrepair('{"a": 1 }  }  ]  }  '))->toBe('{"a": 1 }        ');
        expect(jsonrepair('{"a":2]'))->toBe('{"a":2}');
        expect(jsonrepair('{"a":2,]'))->toBe('{"a":2}');
        expect(jsonrepair('{}}}'))->toBe('{}');
        expect(jsonrepair('[2,}'))->toBe('[2]');
        expect(jsonrepair('[}'))->toBe('[]');
        expect(jsonrepair('{]'))->toBe('{}');
    });

    test('should add a missing closing bracket for an array', function () {
        expect(jsonrepair('['))->toBe('[]');
        expect(jsonrepair('[1,2,3'))->toBe('[1,2,3]');
        expect(jsonrepair('[1,2,3,'))->toBe('[1,2,3]');
        expect(jsonrepair('[[1,2,3,'))->toBe('[[1,2,3]]');
        expect(jsonrepair("{\n\"values\":[1,2,3\n}"))->toBe("{\n\"values\":[1,2,3]\n}");
        expect(jsonrepair("{\n\"values\":[1,2,3\n"))->toBe("{\n\"values\":[1,2,3]}\n");
    });

    test('should strip MongoDB data types', function () {
        expect(jsonrepair('NumberLong("2")'))->toBe('"2"');
        expect(jsonrepair('{"_id":ObjectId("123")}'))->toBe('{"_id":"123"}');

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

        expect(jsonrepair($mongoDocument))->toBe($expectedJson);
    });

    test('should parse an unquoted string', function () {
        expect(jsonrepair('hello world'))->toBe('"hello world"');
        expect(jsonrepair('She said: no way'))->toBe('"She said: no way"');
        expect(jsonrepair('["This is C(2)", "This is F(3)]'))->toBe('["This is C(2)", "This is F(3)"]');
        expect(jsonrepair('["This is C(2)", This is F(3)]'))->toBe('["This is C(2)", "This is F(3)"]');
    });

    test('should replace Python constants None, True, False', function () {
        expect(jsonrepair('True'))->toBe('true');
        expect(jsonrepair('False'))->toBe('false');
        expect(jsonrepair('None'))->toBe('null');
    });

    test('should turn unknown symbols into a string', function () {
        expect(jsonrepair('foo'))->toBe('"foo"');
        expect(jsonrepair('[1,foo,4]'))->toBe('[1,"foo",4]');
        expect(jsonrepair('{foo: bar}'))->toBe('{"foo": "bar"}');
        expect(jsonrepair('foo 2 bar'))->toBe('"foo 2 bar"');
        expect(jsonrepair('{greeting: hello world}'))->toBe('{"greeting": "hello world"}');
        expect(jsonrepair("{greeting: hello world\nnext: \"line\"}"))->toBe("{\"greeting\": \"hello world\",\n\"next\": \"line\"}");
        expect(jsonrepair('{greeting: hello world!}'))->toBe('{"greeting": "hello world!"}');
    });

    test('should turn invalid numbers into strings', function () {
        expect(jsonrepair('ES2020'))->toBe('"ES2020"');
        expect(jsonrepair('0.0.1'))->toBe('"0.0.1"');
        expect(jsonrepair('746de9ad-d4ff-4c66-97d7-00a92ad46967'))->toBe('"746de9ad-d4ff-4c66-97d7-00a92ad46967"');
        expect(jsonrepair('234..5'))->toBe('"234..5"');
        expect(jsonrepair('[0.0.1,2]'))->toBe('["0.0.1",2]');
        expect(jsonrepair('[2 0.0.1 2]'))->toBe('[2, "0.0.1 2"]');
        expect(jsonrepair('2e3.4'))->toBe('"2e3.4"');
    });

    test('should repair regular expressions', function () {
        expect(jsonrepair('{regex: /standalone-styles.css/}'))->toBe('{"regex": "/standalone-styles.css/"}');
        expect(jsonrepair('{regex: /with escape char \\/ [a-z]_/}'))->toBe('{"regex": "/with escape char \\/ [a-z]_/"}');
    });

    test('should concatenate strings', function () {
        expect(jsonrepair('"hello" + " world"'))->toBe('"hello world"');
        expect(jsonrepair("\"hello\" +\n \" world\""))->toBe('"hello world"');
        expect(jsonrepair('"a"+"b"+"c"'))->toBe('"abc"');
        expect(jsonrepair('"hello" + /*comment*/ " world"'))->toBe('"hello world"');
        expect(jsonrepair("{\n  \"greeting\": 'hello' +\n 'world'\n}"))->toBe("{\n  \"greeting\": \"helloworld\"\n}");
        expect(jsonrepair("\"hello +\n \" world\""))->toBe('"hello world"');
        expect(jsonrepair('"hello +'))->toBe('"hello"');
        expect(jsonrepair('["hello +]'))->toBe('["hello"]');
    });

    test('should repair missing comma between array items', function () {
        expect(jsonrepair('{"array": [{}{}]}'))->toBe('{"array": [{},{}]}');
        expect(jsonrepair("{\"array\": [{}\n{}]}"))->toBe("{\"array\": [{},\n{}]}");
        expect(jsonrepair("{\"array\": [\n{}\n{}\n]}"))->toBe("{\"array\": [\n{},\n{}\n]}");
        expect(jsonrepair("{\"array\": [\n1\n2\n]}"))->toBe("{\"array\": [\n1,\n2\n]}");
        expect(jsonrepair("{\"array\": [\n\"a\"\n\"b\"\n]}"))->toBe("{\"array\": [\n\"a\",\n\"b\"\n]}");
        expect(jsonrepair("[\n{},\n{}\n]"))->toBe("[\n{},\n{}\n]");
    });

    test('should repair missing comma between object properties', function () {
        expect(jsonrepair("{\"a\":2\n\"b\":3\n}"))->toBe("{\"a\":2,\n\"b\":3\n}");
        expect(jsonrepair("{\"a\":2\n\"b\":3\nc:4}"))->toBe("{\"a\":2,\n\"b\":3,\n\"c\":4}");
        expect(jsonrepair("{\n  \"firstName\": \"John\"\n  lastName: Smith"))->toBe("{\n  \"firstName\": \"John\",\n  \"lastName\": \"Smith\"}");
        expect(jsonrepair("{\n  \"firstName\": \"John\" /* comment */ \n  lastName: Smith"))->toBe("{\n  \"firstName\": \"John\",  \n  \"lastName\": \"Smith\"}");
        expect(jsonrepair("{\n  \"firstName\": \"John\"\n  ,  lastName: Smith"))->toBe("{\n  \"firstName\": \"John\"\n  ,  \"lastName\": \"Smith\"}");
    });

    test('should repair numbers at the end', function () {
        expect(jsonrepair('{"a":2.'))->toBe('{"a":2.0}');
        expect(jsonrepair('{"a":2e'))->toBe('{"a":2e0}');
        expect(jsonrepair('{"a":2e-'))->toBe('{"a":2e-0}');
        expect(jsonrepair('{"a":-'))->toBe('{"a":-0}');
        expect(jsonrepair('[2e,'))->toBe('[2e0]');
        expect(jsonrepair('[2e '))->toBe('[2e0] ');
        expect(jsonrepair('[-,'))->toBe('[-0]');
    });

    test('should repair missing colon between object key and value', function () {
        expect(jsonrepair('{"a" "b"}'))->toBe('{"a": "b"}');
        expect(jsonrepair('{"a" 2}'))->toBe('{"a": 2}');
        expect(jsonrepair('{"a" true}'))->toBe('{"a": true}');
        expect(jsonrepair('{"a" false}'))->toBe('{"a": false}');
        expect(jsonrepair('{"a" null}'))->toBe('{"a": null}');
        expect(jsonrepair('{"a"2}'))->toBe('{"a":2}');
        expect(jsonrepair("{\n\"a\" \"b\"\n}"))->toBe("{\n\"a\": \"b\"\n}");
        expect(jsonrepair('{"a" \'b\'}'))->toBe('{"a": "b"}');
        expect(jsonrepair("{'a' 'b'}"))->toBe('{"a": "b"}');
        expect(jsonrepair('{"a" "b"}'))->toBe('{"a": "b"}');
        expect(jsonrepair("{a 'b'}"))->toBe('{"a": "b"}');
        expect(jsonrepair('{a "b"}'))->toBe('{"a": "b"}');
    });

    test('should repair missing a combination of comma, quotes and brackets', function () {
        expect(jsonrepair("{\"array\": [\na\nb\n]}"))->toBe("{\"array\": [\n\"a\",\n\"b\"\n]}");
        expect(jsonrepair("1\n2"))->toBe("[\n1,\n2\n]");
        expect(jsonrepair("[a,b\nc]"))->toBe("[\"a\",\"b\",\n\"c\"]");
    });

    test('should repair newline separated json (for example from MongoDB)', function () {
        $text = "/* 1 */\n{}\n\n/* 2 */\n{}\n\n/* 3 */\n{}\n";
        $expected = "[\n\n{},\n\n\n{},\n\n\n{}\n\n]";
        expect(jsonrepair($text))->toBe($expected);
    });

    test('should repair newline separated json having commas', function () {
        $text = "/* 1 */\n{},\n\n/* 2 */\n{},\n\n/* 3 */\n{}\n";
        $expected = "[\n\n{},\n\n\n{},\n\n\n{}\n\n]";
        expect(jsonrepair($text))->toBe($expected);
    });

    test('should repair newline separated json having commas and trailing comma', function () {
        $text = "/* 1 */\n{},\n\n/* 2 */\n{},\n\n/* 3 */\n{},\n";
        $expected = "[\n\n{},\n\n\n{},\n\n\n{}\n\n]";
        expect(jsonrepair($text))->toBe($expected);
    });

    test('should repair a comma separated list with value', function () {
        expect(jsonrepair('1,2,3'))->toBe("[\n1,2,3\n]");
        expect(jsonrepair('1,2,3,'))->toBe("[\n1,2,3\n]");
        expect(jsonrepair("1\n2\n3"))->toBe("[\n1,\n2,\n3\n]");
        expect(jsonrepair("a\nb"))->toBe("[\n\"a\",\n\"b\"\n]");
        expect(jsonrepair('a,b'))->toBe("[\n\"a\",\"b\"\n]");
    });

    test('should repair a number with leading zero', function () {
        expect(jsonrepair('0789'))->toBe('"0789"');
        expect(jsonrepair('000789'))->toBe('"000789"');
        expect(jsonrepair('001.2'))->toBe('"001.2"');
        expect(jsonrepair('002e3'))->toBe('"002e3"');
        expect(jsonrepair('[0789]'))->toBe('["0789"]');
        expect(jsonrepair('{value:0789}'))->toBe('{"value":"0789"}');
    });
});

test('should throw an exception in case of non-repairable issues', function () {
    expect(fn() => jsonrepair(''))->toThrow(JSONRepairError::class);
    expect(fn() => jsonrepair('{"a",'))->toThrow(JSONRepairError::class);
    expect(fn() => jsonrepair('{:2}'))->toThrow(JSONRepairError::class);
    expect(fn() => jsonrepair('{"a":2}{}'))->toThrow(JSONRepairError::class);
    expect(fn() => jsonrepair('{"a" ]'))->toThrow(JSONRepairError::class);
    expect(fn() => jsonrepair('{"a":2}foo'))->toThrow(JSONRepairError::class);
    expect(fn() => jsonrepair('foo ['))->toThrow(JSONRepairError::class);
    expect(fn() => jsonrepair('"\\u26"'))->toThrow(JSONRepairError::class);
    expect(fn() => jsonrepair('"\\uZ000"'))->toThrow(JSONRepairError::class);
});
