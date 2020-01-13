<?php

namespace Drupal\media_collection\Service;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystem;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use SplFileInfo;
use function file_exists;
use function is_dir;

/**
 * Class ExtendedFileSystem.
 *
 * Overwrites some functions from the default drupal filesystem implementation.
 *
 * @package Drupal\media_collection\Service
 */
class ExtendedFileSystem extends FileSystem {

  /**
   * Safely and recursively create a directory.
   *
   * {@inheritdoc}
   */
  public function mkdir($uri, $mode = NULL, $recursive = TRUE, $context = NULL): bool {
    $uriInfo = new SplFileInfo($uri);
    $path = $uri;

    if ($uriInfo->getExtension()) {
      $path = $uriInfo->getPath();
    }

    return !(!is_dir($path) && !parent::mkdir($path, $mode, $recursive, $context) && !is_dir($path));
  }

  /**
   * Move an archive.
   *
   * @param string $source
   *   Source archive.
   * @param string $destination
   *   Destination.
   *
   * @return string|null
   *   The path to the destination or NULL on failure.
   */
  public function moveArchive(string $source, string $destination): ?string {
    if (!$this->mkdir($destination)) {
      return NULL;
    }

    return $this->move($source, $destination);
  }

  /**
   * Remove an archive.
   *
   * @param string $path
   *   Path to the archive.
   */
  public function deleteArchive(string $path): void {
    if (file_exists($path)) {
      $this->unlink($path);
    }
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
  public static function determineUploadLocation(FieldItemListInterface $field): string {
    return (new FileItem($field->getItemDefinition()))->getUploadLocation();
  }

}
