<?php

namespace Tests\Unit;

use App\Support\Markdown;
use App\Support\Slug;
use Tests\TestCase;

class SlugAndMarkdownTest extends TestCase
{
    public function test_slugs_keep_arabic_letters(): void
    {
        $this->assertSame('تنظيف-الأسنان', Slug::make('تنظيف الأسنان'));
        $this->assertSame('root-canal-treatment', Slug::make('  Root canal: treatment! '));
        $this->assertSame('', Slug::make('!!!'));
    }

    public function test_slug_validation(): void
    {
        $this->assertTrue(Slug::isValid('teeth-whitening'));
        $this->assertTrue(Slug::isValid('زراعة-الأسنان'));
        $this->assertFalse(Slug::isValid('bad slug'));
        $this->assertFalse(Slug::isValid('-leading'));
        $this->assertFalse(Slug::isValid('a/b'));
    }

    public function test_markdown_strips_html(): void
    {
        $html = Markdown::toHtml("# Title\n\n<img src=x onerror=alert(1)>\n\n[x](javascript:alert(1))");

        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }
}
