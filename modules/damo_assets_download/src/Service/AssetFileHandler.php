<?php

namespace Drupal\damo_assets_download\Service;

use Drupal\Core\File\FileSystemInterface;
use Drupal\media\MediaInterface;
use Drupal\damo_assets_download\Model\FileArchivingData;

/**
 * Class AssetFileHandler.
 *
 * @package Drupal\damo_assets_download\Service
 */
class AssetFileHandler {

  private $fileSystem;

  /**
   * AssetFileHandler constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   */
  public function __construct(
    FileSystemInterface $fileSystem
  ) {
    $this->fileSystem = $fileSystem;
  }

  /**
   * Fields containing downloadable media files.
   *
   * @var string[]
   *
   * @todo: Maybe get these dynamically.
   */
  private static $mediaFields = [
    'field_image',
    'field_images',
    'field_file',
    'field_files',
    'field_template_file',
    'field_video_file',
  ];

  /**
   * Returns files belonging to the media.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media.
   *
   * @return \Drupal\file\FileInterface[]
   *   Array of files.
   */
  public function mediaFiles(MediaInterface $media): array {
    $files = [];

    foreach (static::$mediaFields as $fieldName) {
      if (!$media->hasField($fieldName)) {
        continue;
      }

      foreach ($media->get($fieldName) as $item) {
        /** @var \Drupal\file\FileInterface|null $file */
        $file = $item->entity;

        if ($file === NULL) {
          continue;
        }

        $files[] = $file;
      }
    }

    return $files;
  }

  /**
   * Returns media files as FileArchivingData instances.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return \Drupal\damo_assets_download\Model\FileArchivingData[]
   *   Files data array.
   */
  public function mediaFilesData(MediaInterface $media): array {
    $filesData = [];

    foreach ($this->mediaFiles($media) as $file) {
      $filePath = $this->fileSystem->realpath($file->getFileUri());

      if ($filePath === FALSE) {
        continue;
      }

      $filesData[] = new FileArchivingData([
        'file' => $file,
        'systemPath' => $filePath,
        'archiveTargetPath' => "/{$media->bundle()}/{$file->getFilename()}",
      ]);
    }

    return $filesData;
  }

}
