<?php

function test($name, $pattern, $tests)
{
    echo "<h3>$name</h3>";
    foreach ($tests as $t) {
        $result = preg_match($pattern, $t) ? "✅ OK" : "❌ FAIL";
        echo "$t → $result<br>";
    }
    echo "<hr>";
}

// 1
test(
    "1. Exact string",
    '/^abcdefghijklmnopqrstuv18340$/',
    [
        "abcdefghijklmnopqrstuv18340",
        "abcdefghijklmnoasdfasdpqrstuv18340"
    ]
);

// 2 GUID
test(
    "2. GUID",
    '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
    [
        "e02fd0e4-00fd-090A-ca30-0d00a0038ba0",
        "02fa0e4-01ad-090A-c130-0d05a0008ba0}"
    ]
);

// 3 MAC
test(
    "3. MAC",
    '/^([0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}$/',
    [
        "01:32:54:67:89:AB",
        "01:33:47:65:89:ab:cd"
    ]
);

// 4 URL
test(
    "4. URL",
    '/^(https?:\/\/)?([a-zA-Z0-9]+(-[a-zA-Z0-9]+)*\.)+[a-zA-Z]{2,}(:\d+)?(\/[^\s]*)?(\?[^\s#]*)?(#[^\s]*)?$/',
    [
        "http://zcontest.ru",
        "http://a.com",
        "Just Text."
    ]
);

// 5 HEX
test(
    "5. HEX color",
    '/^#[0-9a-fA-F]{6}$/',
    [
        "#FFFFFF",
        "#fd2",
        "232323"
    ]
);

// 6 Date
test(
    "6. Date",
    '/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/(1[6-9]\d{2}|[2-9]\d{3})$/',
    [
        "29/02/2000",
        "30-04-2003",
        "1/1/1899"
    ]
);

// 7 Email
test(
    "7. Email",
    '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
    [
        "mail@mail.ru",
        "bug@@@com.ru",
        "val@val"
    ]
);

// 8 IP
test(
    "8. IP",
    '/^((25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)\.){3}(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)$/',
    [
        "127.0.0.1",
        "1300.6.7.8"
    ]
);

// 9 Password
test(
    "9. Password",
    '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d_]{8,}$/',
    [
        "C00l_Pass",
        "Cool_pass",
        "C00l"
    ]
);

// 10 six digits
test(
    "10. Six digits",
    '/^[1-9]\d{5}$/',
    [
        "123456",
        "012345",
        "12345"
    ]
);

// 11 formatted number
test(
    "11. Number format",
    '/^\d{1,3}([ ,]\d{3})+([.,]\d+)?$/',
    [
        "10,000,000.45",
        "10 000 000,45",
        "123.456.789"
    ]
);

// 12 italic
echo "<h3>12. Italic</h3>";
$text = "This is *italic* and **bold**";
$text = preg_replace('/(?<!\*)\*(?!\*)(.*?)\*(?!\*)/', '<em>$1</em>', $text);
echo $text . "<hr>";

// 13 repeat word
echo "<h3>13. Repeat word</h3>";
$text = "this is is a test";
$text = preg_replace('/\b(\w+)\s+\1\b/i', '$1 <strong>$1</strong>', $text);
echo $text . "<hr>";

// 14 H1
echo "<h3>14. H1</h3>";
$text = "# Заголовок";
echo preg_replace('/^# (.+)$/', '<h1>$1</h1>', $text) . "<hr>";

// 15 bbCode
echo "<h3>15. bbCode</h3>";
$text = "[url]https://site.com[/url] [img]img.png[/img]";
$text = preg_replace('/\[url\](.*?)\[\/url\]/', '<a href="$1">$1</a>', $text);
$text = preg_replace('/\[img\](.*?)\[\/img\]/', '<img src="$1">', $text);
echo $text . "<hr>";

// 16 words not starting with vowel
echo "<h3>16. Words not starting with vowel</h3>";
$text = "apple banana orange grape";
preg_match_all('/\b[^aeiouAEIOU\s]\w*/', $text, $m);
print_r($m[0]);
echo "<hr>";

// 17 numbers
echo "<h3>17. Numbers</h3>";
$text = "Price 10 and 10.5 and 200";
preg_match_all('/\b\d+(\.\d+)?\b/', $text, $m);
print_r($m[0]);

?>
