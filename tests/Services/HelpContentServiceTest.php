<?php

namespace Jidaikobo\Kontiki\Tests\Services;

use InvalidArgumentException;
use Jidaikobo\Kontiki\Services\HelpContentService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HelpContentServiceTest extends TestCase
{
    private string $localeDirectory;

    protected function setUp(): void
    {
        $this->localeDirectory = sys_get_temp_dir() . '/kontiki-help-' . bin2hex(random_bytes(8));
        mkdir($this->localeDirectory . '/ja/file', 0777, true);
        file_put_contents(
            $this->localeDirectory . '/ja/file/help.php',
            '<?php echo "rendered help";'
        );
        file_put_contents(
            $this->localeDirectory . '/ja/file/markdown-help.php',
            'markdown help'
        );
    }

    protected function tearDown(): void
    {
        $files = glob($this->localeDirectory . '/ja/file/*') ?: [];
        foreach ($files as $file) {
            unlink($file);
        }
        @rmdir($this->localeDirectory . '/ja/file');
        @rmdir($this->localeDirectory . '/ja');
        @rmdir($this->localeDirectory);
    }

    public function testItRendersPhpHelpContent(): void
    {
        $service = new HelpContentService($this->localeDirectory, 'ja');

        self::assertSame('rendered help', $service->renderHelp());
    }

    public function testItReadsMarkdownHelpContent(): void
    {
        $service = new HelpContentService($this->localeDirectory . '/', 'ja');

        self::assertSame('markdown help', $service->readMarkdownHelp());
    }

    public function testItRejectsPathTraversalAsLanguage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new HelpContentService($this->localeDirectory, '../ja');
    }

    public function testItRejectsAMissingHelpFile(): void
    {
        $this->expectException(RuntimeException::class);

        (new HelpContentService($this->localeDirectory, 'en'))->renderHelp();
    }
}
