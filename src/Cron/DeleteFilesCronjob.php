<?php

declare(strict_types=1);

/*
 * (c) INSPIRED MINDS
 */

namespace InspiredMinds\ContaoAutoDeleteFiles\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Lock\LockFactory;

#[AsCronJob('hourly')]
class DeleteFilesCronjob
{
    public function __construct(
        private readonly Connection $db,
        private readonly LockFactory $lockFactory,
        private readonly VirtualFilesystemInterface $filesStorage,
        private readonly LoggerInterface $contaoCronLogger,
        private readonly string $projectDir,
        private readonly string $uploadPath,
    ) {
    }

    public function __invoke(): void
    {
        $this->deleteByTime();
        $this->deleteByCount();
    }

    private function deleteByTime(): void
    {
        $folders = $this->db->fetchAllAssociative("SELECT * FROM tl_files WHERE type = 'folder' AND autoDeleteFilesTime != ''");

        foreach ($folders as $folder) {
            $setting = StringUtil::deserialize($folder['autoDeleteFilesTime'], true);

            if ('' === ($setting['value'] ?? '') || !($setting['unit'] ?? null)) {
                continue;
            }

            $lock = $this->lockFactory->createLock('delete-files-'.md5(Path::join($this->projectDir, $folder['path'])));

            // Another process is already deleting files in this directory, so skip for now
            if (!$lock->acquire()) {
                continue;
            }

            try {
                $threshold = strtotime('-'.$setting['value'].' '.$setting['unit']);

                $filesToDelete = $this->filesStorage
                    ->listContents(Path::makeRelative($folder['path'], $this->uploadPath), accessFlags: VirtualFilesystemInterface::BYPASS_DBAFS)
                    ->files()
                    ->filter(
                        static function (FilesystemItem $item) use ($threshold): bool {
                            // Ignore Contao's '.public' file
                            if ('.public' === $item->getName()) {
                                return false;
                            }

                            return $item->getLastModified() <= $threshold;
                        },
                    )
                ;

                foreach ($filesToDelete as $file) {
                    $this->filesStorage->delete($file->getPath());
                    $this->contaoCronLogger->info(\sprintf('Automatically deleted "%s" due to time retention rule.', Path::join($this->uploadPath, $file->getPath())));
                }
            } finally {
                $lock->release();
            }
        }
    }

    private function deleteByCount(): void
    {
        $folders = $this->db->fetchAllAssociative("SELECT * FROM tl_files WHERE type = 'folder' AND autoDeleteFilesCount != ''");

        foreach ($folders as $folder) {
            $lock = $this->lockFactory->createLock('delete-files-'.md5(Path::join($this->projectDir, $folder['path'])));

            // Another process is already deleting files in this directory, so skip for now
            if (!$lock->acquire()) {
                continue;
            }

            try {
                $retention = (int) $folder['autoDeleteFilesCount'];

                $files = $this->filesStorage
                    ->listContents(Path::makeRelative($folder['path'], $this->uploadPath), accessFlags: VirtualFilesystemInterface::BYPASS_DBAFS)
                    ->files()
                    ->filter(static fn (FilesystemItem $item): bool => '.public' !== $item->getName())
                    ->getIterator()
                ;

                /** @var list<FilesystemItem> $files */
                $files = array_values(iterator_to_array($files));

                usort($files, static fn (FilesystemItem $a, FilesystemItem $b): int => $b->getLastModified() - $a->getLastModified());

                for ($i = $retention; $i < \count($files); ++$i) {
                    $this->filesStorage->delete($files[$i]->getPath());
                    $this->contaoCronLogger->info(\sprintf('Automatically deleted "%s" due to count retention rule.', Path::join($this->uploadPath, $files[$i]->getPath())));
                }
            } finally {
                $lock->release();
            }
        }
    }
}
