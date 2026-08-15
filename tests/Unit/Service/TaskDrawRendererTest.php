<?php

namespace App\Tests\Unit\Service;

use App\Entity\TaskDraw;
use App\Service\TaskDrawRenderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class TaskDrawRendererTest extends TestCase
{
    private string $drawingsDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->drawingsDir = sys_get_temp_dir().'/hwc-drawings-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->drawingsDir);
    }

    public function testItWritesAPngAndReturnsTheFilename(): void
    {
        $taskDraw = new TaskDraw();
        $taskDraw->setBase64Data($this->pngDataUrl());

        $filename = $this->renderer()->render($taskDraw);

        self::assertStringEndsWith('.png', $filename);
        self::assertFileExists($this->drawingsDir.'/'.$filename);
        self::assertSame(IMAGETYPE_PNG, exif_imagetype($this->drawingsDir.'/'.$filename));
    }

    public function testEachRenderGetsItsOwnFilename(): void
    {
        $renderer = $this->renderer();

        $first = new TaskDraw();
        $first->setBase64Data($this->pngDataUrl());
        $second = new TaskDraw();
        $second->setBase64Data($this->pngDataUrl());

        self::assertNotSame($renderer->render($first), $renderer->render($second));
    }

    public function testEmptyPayloadIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->renderer()->render(new TaskDraw());
    }

    public function testNonDataUrlPayloadIsRejected(): void
    {
        $taskDraw = new TaskDraw();
        $taskDraw->setBase64Data('definitely-not-a-data-url');

        $this->expectException(\InvalidArgumentException::class);

        $this->renderer()->render($taskDraw);
    }

    public function testGarbagePayloadIsRejected(): void
    {
        $taskDraw = new TaskDraw();
        $taskDraw->setBase64Data('data:image/png;base64,'.base64_encode('not an image'));

        $this->expectException(\InvalidArgumentException::class);

        $this->renderer()->render($taskDraw);
    }

    private function renderer(): TaskDrawRenderer
    {
        return new TaskDrawRenderer($this->filesystem, $this->drawingsDir);
    }

    /**
     * A 2x2 PNG, in the shape the browser canvas produces.
     */
    private function pngDataUrl(): string
    {
        $image = imagecreatetruecolor(2, 2);

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();

        return 'data:image/png;base64,'.base64_encode($binary);
    }
}
