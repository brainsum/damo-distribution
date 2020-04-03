<?php

namespace Drupal\damo_assets_download\Service;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\damo\Service\DamoFileSystemInterface;
use Drupal\damo_assets_download\Model\FileArchivingData;
use RuntimeException;
use ZipArchive;
use function file_get_contents;

/**
 * Class AssetArchiver.
 *
 * @package Drupal\damo_assets_download\Service
 */
class AssetArchiver {

  /**
   * The FS.
   *
   * @var \Drupal\damo\Service\DamoFileSystemInterface
   */
  protected $fileSystem;

  /**
   * UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * AssetArchiver constructor.
   *
   * @param \Drupal\damo\Service\DamoFileSystemInterface $fileSystem
   *   The fs.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The uuid service.
   */
  public function __construct(
    DamoFileSystemInterface $fileSystem,
    UuidInterface $uuid
  ) {
    $this->fileSystem = $fileSystem;
    $this->uuid = $uuid;
  }

  /**
   * Create an archive from the file data.
   *
   * @param \Drupal\damo_assets_download\Model\FileArchivingData[] $fileData
   *   Array of processed entity data.
   * @param string $fileName
   *   (Optional) Filename of the archive (with .zip extension).
   * @param string|null $directory
   *   (Optional) Absolute path to the target directory.
   *
   * @return string|null
   *   Location of the archive, or NULL on failure.
   */
  public function createFileArchive(array $fileData, string $fileName = 'archive.zip', ?string $directory = NULL): ?string {
    if ($directory === NULL) {
      $directory = 'temporary://demo-archive/' . $this->uuid->generate();
    }

    if (!$this->fileSystem->safeMkdir($directory)) {
      // @todo: Throw exception.
      NULL;
    }

    $location = $this->fileSystem->realpath($directory . '/' . $fileName);

    $archive = $this->addFilesToArchive(
      $this->startArchive($location),
      $fileData
    );

    $fileCount = $archive->numFiles;
    $archive->close();
    return $fileCount > 0 ? $location : NULL;
  }

  /**
   * Starts a new archive for the given path.
   *
   * @param string $path
   *   Path to the new archive.
   *
   * @return \ZipArchive
   *   The new archive.
   */
  private function startArchive(string $path): ZipArchive {
    // Although Drupal has a Zip service masking this class,
    // it's not good enough.
    $archive = new ZipArchive();

    if (
      ($archiveOpened = $archive->open($path, ZipArchive::OVERWRITE | ZipArchive::CREATE))
      && $archiveOpened !== TRUE
    ) {
      throw new RuntimeException(new TranslatableMarkup('Cannot open %file_path [Error code: %code]', [
        '%file_path' => $path,
        '%code' => $archiveOpened,
      ]));
    }

    return $archive;
  }

  /**
   * Add files to the archive.
   *
   * @param \ZipArchive $archive
   *   The archive to populate.
   * @param array $fileData
   *   The data of the files to be added.
   *
   * @return \ZipArchive
   *   The new archive.
   */
  private function addFilesToArchive(ZipArchive $archive, array $fileData): ZipArchive {
    foreach ($fileData as $data) {
      $archive = $this->addFileToArchive($archive, $data);
    }

    return $archive;
  }

  /**
   * Add a file to the archive.
   *
   * @param \ZipArchive $archive
   *   The archive to populate.
   * @param \Drupal\damo_assets_download\Model\FileArchivingData $fileData
   *   The data of the file to be added.
   *
   * @return \ZipArchive
   *   The new archive.
   */
  private function addFileToArchive(ZipArchive $archive, FileArchivingData $fileData): ZipArchive {
    if ($archive->locateName($fileData->archiveTargetPath) === FALSE) {
      // Left trim required for windows compatibility.
      $targetPath = ltrim($fileData->archiveTargetPath, '/');
      // @todo: Error handling.
      $archive->addFromString($targetPath, file_get_contents($fileData->systemPath));
      // $archive->addFile($fileData->systemPath, $targetPath);
    }

    return $archive;
  }

}
