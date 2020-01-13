<?php

namespace Drupal\media_collection\Service\FileHandler;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\media_collection\Entity\MediaCollectionInterface;
use Drupal\media_collection\Service\EntityProcessor\CollectionProcessor;
use Drupal\damo_assets_download\Service\FileManager;
use Drupal\damo_assets_download\Service\AssetArchiver;
use SplFileInfo;

/**
 * Class CollectionFileHandler.
 *
 * @package Drupal\media_collection\Service\DownloadHandler
 *
 * @todo: Maybe rename to file handler?
 * @todo: Add real download handler (binary file response factory basically).
 */
final class CollectionFileHandler {

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * Collection processor.
   *
   * @var \Drupal\media_collection\Service\EntityProcessor\CollectionProcessor
   */
  private $collectionProcessor;

  /**
   * Archive manager.
   *
   * @var \Drupal\damo_assets_download\Service\AssetArchiver
   */
  private $archiver;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  private $time;

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private $dateFormatter;

  /**
   * File manager service.
   *
   * @var \Drupal\damo_assets_download\Service\FileManager
   */
  private $fileManager;

  /**
   * CollectionFileHandler constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\media_collection\Service\EntityProcessor\CollectionProcessor $collectionProcessor
   *   Processor for collections.
   * @param \Drupal\damo_assets_download\Service\AssetArchiver $archiver
   *   Archive manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   Time service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   Date formatter.
   * @param \Drupal\damo_assets_download\Service\FileManager $fileManager
   *   File manager service.
   */
  public function __construct(
    FileSystemInterface $fileSystem,
    CollectionProcessor $collectionProcessor,
    AssetArchiver $archiver,
    TimeInterface $time,
    DateFormatterInterface $dateFormatter,
    FileManager $fileManager
  ) {
    $this->collectionProcessor = $collectionProcessor;
    $this->archiver = $archiver;

    $this->fileSystem = $fileSystem;
    $this->fileManager = $fileManager;
    // @todo: Maybe move to a custom service, these 2 need to be together.
    $this->time = $time;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * Generate a downloadable file for a collection.
   *
   * @param \Drupal\media_collection\Entity\MediaCollectionInterface $collection
   *   The collection.
   *
   * @return \Drupal\file\FileInterface|null
   *   The downloadable file or NULL.
   */
  public function generateDownloadableFile(MediaCollectionInterface $collection): ?FileInterface {
    $fileData = $this->collectionProcessor->process($collection);
    $fileCount = count($fileData);

    if ($fileCount <= 0) {
      return NULL;
    }

    if ($fileCount === 1) {
      return reset($fileData)->file;
    }

    $archiveLocation = $this->archiver->createFileArchive($this->archiveTargetPath($collection), $fileData);

    if ($archiveLocation === NULL) {
      return NULL;
    }

    return $this->fileManager->createArchiveEntity($collection->getOwner(), new SplFileInfo($archiveLocation));
  }

  /**
   * Generate a temporary archive file entity for a given collection.
   *
   * @param \Drupal\media_collection\Entity\MediaCollectionInterface $collection
   *   The collection.
   *
   * @return \Drupal\file\FileInterface|null
   *   The archive file or NULL on failure.
   */
  public function generateArchiveEntity(MediaCollectionInterface $collection): ?FileInterface {
    $archiveLocation = $this->archiver->createFileArchive(
      $this->archiveTargetPath($collection),
      $this->collectionProcessor->process($collection)
    );

    if ($archiveLocation === NULL) {
      return NULL;
    }

    /* @todo:
     * Use determineUploadLocation() for saving it to the collection.
     */
    return $this->fileManager->createArchiveEntity($collection->getOwner(), new SplFileInfo($archiveLocation));
  }

  /**
   * Generate path to the temporary archive file.
   *
   * @param \Drupal\media_collection\Entity\MediaCollectionInterface $collection
   *   The media collection.
   *
   * @return string|null
   *   The absolute path or NULL on failure.
   *
   * @see collectionArchivePath
   */
  private function archiveTargetPath(MediaCollectionInterface $collection): ?string {
    $basePath = $this->fileSystem->realpath('private://');
    $fileDir = "{$basePath}/tmp/collection/{$collection->uuid()}";

    if (!$this->mkdir($fileDir)) {
      return NULL;
    }

    return "{$fileDir}/{$this->archiveTargetName()}";
  }

  /**
   * Generate archive name for a collection.
   *
   * @return string
   *   The archive name.
   *
   * @see collectionArchiveName
   */
  private function archiveTargetName(): string {
    return "Media library collection_{$this->currentDate()}.zip";
  }

  /**
   * Return the upload location for a file field.
   *
   * Returns e.g "private://my-location/folder".
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   File field.
   *
   * @return string
   *   Upload location for the given file field.
   */
  private function determineUploadLocation(FieldItemListInterface $field): string {
    return (new FileItem($field->getItemDefinition()))->getUploadLocation();
  }

  /**
   * Returns the current date in the desired format.
   *
   * @return string
   *   The properly formatted current date.
   *
   * @todo: Move to service?
   */
  private function currentDate(): string {
    return $this->dateFormatter->format($this->time->getCurrentTime(), 'custom', 'Y-m-d');
  }

  /**
   * Safely and recursively create a directory.
   *
   * @param string $uri
   *   Directory path or URI.
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   *
   * @todo: Move to service.
   */
  private function mkdir($uri): bool {
    $uriInfo = new SplFileInfo($uri);
    $path = $uri;

    if ($uriInfo->getExtension()) {
      $path = $uriInfo->getPath();
    }

    return !(!is_dir($path) && !$this->fileSystem->mkdir($path, NULL, TRUE) && !is_dir($path));
  }

}
