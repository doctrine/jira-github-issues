<?php

require_once __DIR__ . '/../jira_markdown.php';

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

    public function testRegression()
    {
        $markdown = toMarkdown(<<<'JIRA'
We need better control for setting the hash/key for resultset cache entries. Then provide a way to clear these cache entries.

http://trac.doctrine-project.org/ticket/2042

{code}
$temp = Doctrine_Query::create()
->from('Profile p')
->where('p.id=?', $o)
->setHydrationMode(Doctrine::HYDRATE_ARRAY)
->useResultCache(true, 3600, 'product_cache') // custom tag
->execute();

$temp = Doctrine_Query::create()
->from('Model m')
->setHydrationMode(Doctrine::HYDRATE_ARRAY)
->useResultCache(true, 3600, 'product_cache') // custom tag
->execute();

$temp = Doctrine_Query::create()
->from('News n')
->setHydrationMode(Doctrine::HYDRATE_ARRAY)
->useResultCache(true, 3600, 'news_cache') // custom tag
->execute();
{code}

and now

{code}
$conn  = Doctrine_Manager::getConnection('sqlite_cache_connection');
$cacheDriver = new Doctrine_Cache_Db(array('connection' => $conn,
'tableName' => 'cache'));

$cacheDriver->deleteByTag('product_cache');
{code}
JIRA
        );

        $this->assertEquals($markdown, <<<'MARKDOWN'
We need better control for setting the hash/key for resultset cache entries. Then provide a way to clear these cache entries.

http://trac.doctrine-project.org/ticket/2042

```
$temp = Doctrine_Query::create()
->from('Profile p')
->where('p.id=?', $o)
->setHydrationMode(Doctrine::HYDRATE_ARRAY)
->useResultCache(true, 3600, 'product_cache') // custom tag
->execute();

$temp = Doctrine_Query::create()
->from('Model m')
->setHydrationMode(Doctrine::HYDRATE_ARRAY)
->useResultCache(true, 3600, 'product_cache') // custom tag
->execute();

$temp = Doctrine_Query::create()
->from('News n')
->setHydrationMode(Doctrine::HYDRATE_ARRAY)
->useResultCache(true, 3600, 'news_cache') // custom tag
->execute();
```

and now

```
$conn  = Doctrine*Manager::getConnection('sqlite_cache*connection');
$cacheDriver = new Doctrine*Cache*Db(array('connection' => $conn,
'tableName' => 'cache'));

$cacheDriver->deleteByTag('product_cache');
```
MARKDOWN
        );
    }
}
