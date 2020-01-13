<?php

namespace Drupal\media_collection\Service\FileHandler;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\media_collection\Entity\MediaCollectionItemInterface;
use Drupal\media_collection\Service\EntityProcessor\CollectionItemProcessor;
use Drupal\damo_assets_download\Service\FileManager;
use Drupal\damo_assets_download\Service\AssetArchiver;
use SplFileInfo;
use function count;
use function reset;

/**
 * Class itemFileHandler.
 *
 * @package Drupal\media_collection\Service
 */
final class ItemFileHandler {

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * Collection item processor.
   *
   * @var \Drupal\media_collection\Service\EntityProcessor\CollectionItemProcessor
   */
  private $itemProcessor;

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
   * ItemFileHandler constructor.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\media_collection\Service\EntityProcessor\CollectionItemProcessor $itemProcessor
   *   Processor for collection items.
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
    CollectionItemProcessor $itemProcessor,
    AssetArchiver $archiver,
    TimeInterface $time,
    DateFormatterInterface $dateFormatter,
    FileManager $fileManager
  ) {
    $this->fileSystem = $fileSystem;
    $this->itemProcessor = $itemProcessor;
    $this->archiver = $archiver;
    $this->fileManager = $fileManager;
    // @todo: Maybe move to a custom service, these 2 need to be together.
    $this->time = $time;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * Generate a downloadable file for an item.
   *
   * @param \Drupal\media_collection\Entity\MediaCollectionItemInterface $item
   *   The item.
   *
   * @return \Drupal\file\FileInterface|null
   *   The downloadable file or NULL.
   */
  public function generateDownloadableFile(MediaCollectionItemInterface $item): ?FileInterface {
    $fileData = $this->itemProcessor->process($item);
    $fileCount = count($fileData);

    if ($fileCount <= 0) {
      return NULL;
    }

    if ($fileCount === 1) {
      return reset($fileData)->file;
    }

    $archiveLocation = $this->archiver->createFileArchive($this->archiveTargetPath($item), $fileData);

    if ($archiveLocation === NULL) {
      return NULL;
    }

    return $this->fileManager->createArchiveEntity($item->getOwner(), new SplFileInfo($archiveLocation));
  }

  /**
   * Generate a temporary archive file entity for a given item.
   *
   * @param \Drupal\media_collection\Entity\MediaCollectionItemInterface $item
   *   The item.
   *
   * @return \Drupal\file\FileInterface|null
   *   The archive file or NULL on failure.
   */
  public function generateArchiveEntity(MediaCollectionItemInterface $item): ?FileInterface {
    $archiveLocation = $this->archiver->createFileArchive(
      $this->archiveTargetPath($item),
      $this->itemProcessor->process($item)
    );

    if ($archiveLocation === NULL) {
      return NULL;
    }

    /* @todo:
     * Maybe if a "SharedCollectionItem" entity is a added with a storage field
     * move archive to that?
     */
    return $this->fileManager->createArchiveEntity($item->getOwner(), new SplFileInfo($archiveLocation));
  }

  /**
   * Generate path to the temporary archive file.
   *
   * @param \Drupal\media_collection\Entity\MediaCollectionItemInterface $item
   *   The item.
   *
   * @return string|null
   *   The absolute path or NULL on failure.
   */
  private function archiveTargetPath(MediaCollectionItemInterface $item): ?string {
    $basePath = $this->fileSystem->realpath('private://');
    $fileDir = "{$basePath}/tmp/item/{$item->uuid()}";

    if (!$this->mkdir($fileDir)) {
      return NULL;
    }

    $fileName = $this->archiveTargetName($item);

    return "{$fileDir}/{$fileName}";
  }

  /**
   * Generate archive name for an item.
   *
   * @param \Drupal\media_collection\Entity\MediaCollectionItemInterface $item
   *   Item.
   *
   * @return string
   *   The archive name.
   */
  private function archiveTargetName(MediaCollectionItemInterface $item): string {
    $archiveTargetName = $item->media()->getName();

    if ($style = $item->style()) {
      $archiveTargetName .= "_{$style->getName()}";
    }

    $archiveTargetName .= "_{$this->currentDate()}";

    return "{$archiveTargetName}.zip";
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
