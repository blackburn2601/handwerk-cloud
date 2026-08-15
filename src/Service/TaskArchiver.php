<?php

namespace App\Service;

use App\Entity\Task;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Packs every photo and drawing belonging to a task into a zip download.
 */
class TaskArchiver
{
    public function __construct(
        private readonly string $taskImagesDir,
        private readonly string $taskDrawingsDir,
    ) {
    }

    public function archive(Task $task): BinaryFileResponse
    {
        $archivePath = tempnam(sys_get_temp_dir(), 'auftrag_');

        if (false === $archivePath) {
            throw new \RuntimeException('Temporäre Archivdatei konnte nicht angelegt werden.');
        }

        $zip = new \ZipArchive();

        if (true !== $zip->open($archivePath, \ZipArchive::OVERWRITE)) {
            throw new \RuntimeException('Archiv konnte nicht geöffnet werden.');
        }

        foreach ($task->getTaskImages() as $image) {
            $this->addFile($zip, $this->taskImagesDir.'/'.$image->getPath(), 'bilder/'.$image->getPath());
        }

        foreach ($task->getTaskDraws() as $draw) {
            if (null !== $draw->getPath()) {
                $this->addFile($zip, $this->taskDrawingsDir.'/'.$draw->getPath(), 'zeichnungen/'.$draw->getPath());
            }
        }

        $zip->close();

        $response = new BinaryFileResponse($archivePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            sprintf('auftrag-%d.zip', $task->getId()),
        );
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * Missing files are skipped rather than aborting the whole download —
     * uploads and the database can drift apart over a long-lived install.
     */
    private function addFile(\ZipArchive $zip, string $absolutePath, string $entryName): void
    {
        if (is_file($absolutePath) && is_readable($absolutePath)) {
            $zip->addFile($absolutePath, $entryName);
        }
    }
}
