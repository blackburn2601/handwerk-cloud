<?php

namespace App\Service;

use App\Entity\Offer;
use App\Entity\Task;
use App\Entity\TaskImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Stores uploaded photos and attaches them to an offer or a task.
 *
 * An offer and the task generated from it share their photos, so an image
 * attached to either side is linked to both whenever the counterpart exists.
 */
class TaskImageUploader
{
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/heic'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
        private readonly string $taskImagesDir,
    ) {
    }

    /**
     * @param iterable<UploadedFile|null> $files
     *
     * @return int number of images stored
     */
    public function upload(iterable $files, Offer|Task $owner): int
    {
        $stored = 0;

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $this->assertIsImage($file);

            $taskImage = new TaskImage();
            $taskImage->setPath($this->moveToImagesDir($file));

            $this->link($taskImage, $owner);

            $this->entityManager->persist($taskImage);
            ++$stored;
        }

        if ($stored > 0) {
            $this->entityManager->flush();
        }

        return $stored;
    }

    private function moveToImagesDir(UploadedFile $file): string
    {
        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->guessExtension() ?: 'bin';

        $filename = sprintf('%s-%s.%s', $this->slugger->slug($original)->lower(), bin2hex(random_bytes(6)), $extension);

        try {
            $file->move($this->taskImagesDir, $filename);
        } catch (FileException $e) {
            throw new \RuntimeException(sprintf('Datei "%s" konnte nicht gespeichert werden.', $file->getClientOriginalName()), 0, $e);
        }

        return $filename;
    }

    private function link(TaskImage $taskImage, Offer|Task $owner): void
    {
        if ($owner instanceof Offer) {
            $taskImage->setOffer($owner);
            $owner->addTaskImage($taskImage);

            if (null !== $task = $owner->getTask()) {
                $taskImage->setTask($task);
                $task->addTaskImage($taskImage);
            }

            return;
        }

        $taskImage->setTask($owner);
        $owner->addTaskImage($taskImage);

        if (null !== $offer = $owner->getOffer()) {
            $taskImage->setOffer($offer);
            $offer->addTaskImage($taskImage);
        }
    }

    private function assertIsImage(UploadedFile $file): void
    {
        if (!\in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('Datei "%s" ist kein unterstütztes Bildformat.', $file->getClientOriginalName()));
        }
    }
}
