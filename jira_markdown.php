<?php

class JiraMarkdownTest extends \PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        $markdown = toMarkdown(<<<JIRA
h1. Biggest heading

h2. Bigger heading

h1. Biggest heading
h2. Bigger heading
h3. Big heading
h4. Normal heading
h5. Small heading
h6. Smallest heading

*strong*
_emphasis_
{{monospaced}}
??citation??
-deleted-
+inserted+
^superscript^
~subscript~

* foo
** bar

{code:javascript}
var hello = 'world';
{code}

{code}
<?php
lol();
{code}

[http://google.com]
[Google|http://google.com]

GitHub Flavor
-deleted-

{noformat}
  preformatted piece of text
  so *no* further _formatting_ is done here
{noformat}
JIRA
        );

        $this->assertEquals(<<<MARKDOWN
# Biggest heading

## Bigger heading

# Biggest heading
## Bigger heading
### Big heading
#### Normal heading
##### Small heading
###### Smallest heading

**strong**
*emphasis*
`monospaced`
<cite>citation</cite>
-deleted-
<ins>inserted</ins>
<sup>superscript</sup>
<sub>subscript</sub>

* foo
**** bar

```javascript
var hello = 'world';
```

```
<?php
lol();
```

<http://google.com>
[Google](http://google.com)

GitHub Flavor
-deleted-

```
  preformatted piece of text
  so **no** further *formatting* is done here
```
MARKDOWN
            , $markdown);
    }
}

function toMarkdown($text) {
    $converted = $text;
    $converted = preg_replace_callback('/^h([0-6])\.(.*)$/m', function ($matches) {
        return str_repeat('#', $matches[1]) . $matches[2];
    }, $converted);

    $converted = preg_replace_callback('/([*_])(.*)\1/', function ($matches) {
        list ($match, $wrapper, $content) = $matches;
        $to = ($wrapper === '*') ? '**' : '*';
        return $to . $content . $to;
      }, $converted);

    $converted = preg_replace('/\{\{([^}]+)\}\}/', '`$1`', $converted);
    $converted = preg_replace('/\?\?((?:.[^?]|[^?].)+)\?\?/', '<cite>$1</cite>', $converted);
    $converted = preg_replace('/\+([^+]*)\+/', '<ins>$1</ins>', $converted);
    $converted = preg_replace('/\^([^^]*)\^/', '<sup>$1</sup>', $converted);
    $converted = preg_replace('/~([^~]*)~/', '<sub>$1</sub>', $converted);
    $converted = preg_replace('/-([^-]*)-/', '-$1-', $converted);

    $converted = preg_replace('/{code(:([a-z]+))?}([^.]*?){code}/m', '```$2$3```', $converted);

    $converted = preg_replace('/\[(.+?)\|(.+)\]/', '[$1]($2)', $converted);
    $converted = preg_replace('/\[(.+?)\]([^\(]*)/', '<$1>$2', $converted);

    $converted = preg_replace('/{noformat}/', '```', $converted);

    return $converted;
}
