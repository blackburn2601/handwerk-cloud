<?php

namespace App\Service;

use App\Entity\TaskDraw;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Turns the base64 data URL produced by the browser canvas into a PNG on disk.
 */
class TaskDrawRenderer
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly string $taskDrawingsDir,
    ) {
    }

    /**
     * @return string the generated filename, relative to the drawings directory
     *
     * @throws \InvalidArgumentException when the payload is not a usable image data URL
     */
    public function render(TaskDraw $taskDraw): string
    {
        $image = $this->decode($taskDraw->getBase64Data());

        $this->filesystem->mkdir($this->taskDrawingsDir);

        $filename = sprintf('%s-%s.png', $taskDraw->getOffer()?->getId() ?? 'draft', bin2hex(random_bytes(8)));

        if (!imagepng($image, $this->taskDrawingsDir.'/'.$filename, 6)) {
            throw new \RuntimeException('Die Zeichnung konnte nicht gespeichert werden.');
        }

        return $filename;
    }

    /**
     * @return \GdImage
     */
    private function decode(?string $dataUrl)
    {
        if (null === $dataUrl || '' === $dataUrl) {
            throw new \InvalidArgumentException('Die Zeichnung ist leer.');
        }

        // Expected shape: data:image/png;base64,<payload>
        if (!preg_match('#^data:image/\w+;base64,(?<payload>.+)$#s', $dataUrl, $matches)) {
            throw new \InvalidArgumentException('Die Zeichnung hat ein unerwartetes Format.');
        }

        $binary = base64_decode($matches['payload'], true);

        if (false === $binary) {
            throw new \InvalidArgumentException('Die Zeichnung ist nicht korrekt kodiert.');
        }

        $image = @imagecreatefromstring($binary);

        if (false === $image) {
            throw new \InvalidArgumentException('Die Zeichnung konnte nicht gelesen werden.');
        }

        return $image;
    }
}
