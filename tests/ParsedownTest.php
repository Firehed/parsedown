<?php

use PHPUnit\Framework\TestCase;

/**
 * @covers ParseDown
 */
class ParsedownTest extends TestCase
{
    protected Parsedown $Parsedown;

    public function setUp(): void
    {
        $this->Parsedown = new TestParsedown();
    }

    /**
     * @dataProvider data
     * @param $test
     * @param $dir
     */
    public function testTextRender($test, $dir)
    {
        $markdown = file_get_contents($dir . $test . '.md');

        $expectedMarkup = file_get_contents($dir . $test . '.html');

        $expectedMarkup = str_replace("\r\n", "\n", $expectedMarkup);
        $expectedMarkup = str_replace("\r", "\n", $expectedMarkup);

        $this->Parsedown->setSafeMode(substr($test, 0, 3) === 'xss');
        $this->Parsedown->setStrictMode(substr($test, 0, 6) === 'strict');

        $actualMarkup = $this->Parsedown->text($markdown);

        $this->assertEquals($expectedMarkup, $actualMarkup);
    }

    public function testRawHtml()
    {
        $markdown = "```php\nfoobar\n```";
        $expectedMarkup = '<pre><code class="language-php"><p>foobar</p></code></pre>';
        $expectedSafeMarkup = '<pre><code class="language-php">&lt;p&gt;foobar&lt;/p&gt;</code></pre>';

        $unsafeExtension = new UnsafeExtension();
        $actualMarkup = $unsafeExtension->text($markdown);

        $this->assertEquals($expectedMarkup, $actualMarkup);

        $unsafeExtension->setSafeMode(true);
        $actualSafeMarkup = $unsafeExtension->text($markdown);

        $this->assertEquals($expectedSafeMarkup, $actualSafeMarkup);
    }

    public function testTrustDelegatedRawHtml()
    {
        $markdown = "```php\nfoobar\n```";
        $expectedMarkup = '<pre><code class="language-php"><p>foobar</p></code></pre>';
        $expectedSafeMarkup = $expectedMarkup;

        $unsafeExtension = new TrustDelegatedExtension();
        $actualMarkup = $unsafeExtension->text($markdown);

        $this->assertEquals($expectedMarkup, $actualMarkup);

        $unsafeExtension->setSafeMode(true);
        $actualSafeMarkup = $unsafeExtension->text($markdown);

        $this->assertEquals($expectedSafeMarkup, $actualSafeMarkup);
    }

    public static function data()
    {
        $data = array();

        $dirs = [__DIR__ . '/data/'];
        foreach ($dirs as $dir) {
            $Folder = new DirectoryIterator($dir);

            foreach ($Folder as $File) {
                /** @var $File DirectoryIterator */

                if (! $File->isFile()) {
                    continue;
                }

                $filename = $File->getFilename();

                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                if ($extension !== 'md') {
                    continue;
                }

                $basename = $File->getBasename('.md');

                if (file_exists($dir . $basename . '.html')) {
                    $data [] = array($basename, $dir);
                }
            }
        }

        return $data;
    }

    public function testNoMarkup()
    {
        $markdownWithHtml = <<<MARKDOWN_WITH_MARKUP
<div>_content_</div>

sparse:

<div>
<div class="inner">
_content_
</div>
</div>

paragraph

<style type="text/css">
    p {
        color: red;
    }
</style>

comment

<!-- html comment -->
MARKDOWN_WITH_MARKUP;

        $expectedHtml = <<<EXPECTED_HTML
<p>&lt;div&gt;<em>content</em>&lt;/div&gt;</p>
<p>sparse:</p>
<p>&lt;div&gt;
&lt;div class="inner"&gt;
<em>content</em>
&lt;/div&gt;
&lt;/div&gt;</p>
<p>paragraph</p>
<p>&lt;style type="text/css"&gt;
p {
color: red;
}
&lt;/style&gt;</p>
<p>comment</p>
<p>&lt;!-- html comment --&gt;</p>
EXPECTED_HTML;

        $parsedownWithNoMarkup = new TestParsedown();
        $parsedownWithNoMarkup->setMarkupEscaped(true);
        $this->assertEquals($expectedHtml, $parsedownWithNoMarkup->text($markdownWithHtml));
    }
}
