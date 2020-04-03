<?php

namespace Drupal\damo\Service;

use Drupal\Core\File\FileSystemInterface;

/**
 * Class DamoFileSystem.
 *
 * @package Drupal\damo\Service
 */
interface DamoFileSystemInterface extends FileSystemInterface {

  /**
   * Safely and recursively create a directory.
   *
   * @param string $uri
   *   Directory path or URI.
   *
   * @return bool
   *   TRUE on success, FALSE on error.
   */
  public function safeMkdir(string $uri): bool;

}
