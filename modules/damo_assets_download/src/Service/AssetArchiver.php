<?php

namespace Drupal\damo_assets_download\Service;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\damo_assets_download\Model\FileArchivingData;
use RuntimeException;
use ZipArchive;

/**
 * Class AssetArchiver.
 *
 * @package Drupal\damo_assets_download\Service
 */
class AssetArchiver {

  /**
   * Create an archive from the file data.
   *
   * @param string $location
   *   Location of the archive.
   * @param \Drupal\damo_assets_download\Model\FileArchivingData[] $fileData
   *   Array of processed entity data.
   *
   * @return string|null
   *   Location of the archive, or NULL on failure.
   */
  public function createFileArchive(string $location, array $fileData): ?string {
    $archive = $this->addFilesToArchive(
      $this->startArchive($location),
      $fileData
    );

    if ($archive->numFiles <= 0) {
      $archive->close();
      return NULL;
    }

    $archive->close();

    return $location;
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
      $archive->addFile($fileData->systemPath, ltrim($fileData->archiveTargetPath, '/'));
    }

    return $archive;
  }

}
