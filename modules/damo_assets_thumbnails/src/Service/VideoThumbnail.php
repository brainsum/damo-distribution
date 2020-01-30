<?php

namespace Drupal\damo_assets_thumbnails\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use InvalidArgumentException;
use RuntimeException;
use SplFileInfo;
use function str_replace;

/**
 * Class VideoThumbnail.
 *
 * @package Drupal\damo_assets_thumbnails\Service
 */
class VideoThumbnail {

  public const BASE_FOLDER = 'private://video-thumbnail';
  public const EXTENSION = 'jpeg';

  /**
   * FFMpeg.
   *
   * @var \FFMpeg\FFMpeg
   */
  protected $ffMpeg;

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * File entity storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * VideoThumbnail constructor.
   *
   * @param \FFMpeg\FFMpeg $ffMpeg
   *   FFMpeg service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   FileSystem.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    FFMpeg $ffMpeg,
    FileSystemInterface $fileSystem,
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser
  ) {
    $this->ffMpeg = $ffMpeg;
    $this->fileSystem = $fileSystem;
    $this->fileStorage = $entityTypeManager->getStorage('file');
    $this->currentUser = $currentUser;
  }

  /**
   * Extracts the thumbnail file from the video.
   *
   * @param string $videoUri
   *   Absolute URI to the video file.
   * @param string $thumbnailFileName
   *   Desired filename of the extracted thumbnail.
   *
   * @note: This function extracts the thumbnail to the filesystem.
   */
  protected function extractThumbnailFromVideo($videoUri, $thumbnailFileName): void {
    $thumbnailRealPath = $this->fileSystem->realpath(static::BASE_FOLDER) . '/' . $thumbnailFileName;

    /** @var \FFMpeg\Media\Video $video */
    $video = $this->ffMpeg->open($videoUri);
    $frame = $video->frame(TimeCode::fromSeconds(0));
    $frame->save($thumbnailRealPath, TRUE);
    // @todo: Handle exceptions, errors, double-check that the thumbnail was created.
  }

  /**
   * Generates the filename for the thumbnail.
   *
   * @param string $videoUri
   *   The absolute uri to the video.
   *
   * @return string
   *   The thumbnail filename.
   */
  protected function generateThumbnailFileName(string $videoUri): string {
    $videoInfo = new SplFileInfo($videoUri);
    $thumbnailName = str_replace(".{$videoInfo->getExtension()}", '', $videoInfo->getFilename());
    // @todo: Extract and add datestamp? E.g 2020-01, ...
    return $thumbnailName . '.' . static::EXTENSION;
  }

  /**
   * Generates a thumbnail from a video file.
   *
   * @param \Drupal\file\FileInterface $video
   *  The video file entity.
   *
   * @return \Drupal\file\FileInterface
   *   The Thumbnail file entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function generateFileThumbnail(FileInterface $video): FileInterface {
    // @todo: Error handling.
    $videoAbsoluteUri = $this->fileSystem->realpath($video->getFileUri());
    $thumbnailFileName = $this->generateThumbnailFileName($videoAbsoluteUri);
    $this->extractThumbnailFromVideo($videoAbsoluteUri, $thumbnailFileName);
    // For the actual file entity we have to use the wrapped path.
    // Realpath is going to break things.
    $thumbnailInfo = new SplFileInfo(static::BASE_FOLDER . '/' . $thumbnailFileName);
    /** @var \Drupal\file\FileInterface $thumbnailFile */
    $thumbnailFile = $this->fileStorage->create([
      'uri' => $thumbnailInfo->getPathname(),
      'filename' => $thumbnailFileName,
      'filesize' => $thumbnailInfo->getSize(),
      'uid' => $this->currentUser->id(),
    ]);
    $thumbnailFile->save();
    return $thumbnailFile;
  }

  /**
   * Generates a thumbnail for a video asset.
   *
   * @param \Drupal\media\MediaInterface $media
   *  The video media entity.
   *
   * @return \Drupal\file\FileInterface
   *   The Thumbnail file entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function generateAssetThumbnail(MediaInterface $media): FileInterface {
    if ($media->bundle() !== 'video_file') {
      throw new InvalidArgumentException("The asset '{$media->getName()}' ({$media->id()}) is not supported for automatic thumbnail generation.");
    }

    /** @var \Drupal\file\FileInterface|null $videoFile */
    $videoFile = $media->get('field_video_file')->entity;

    if ($videoFile === NULL) {
      throw new RuntimeException("The asset '{$media->getName()}' ({$media->id()}) does not contain a valid video file.");
    }

    return $this->generateFileThumbnail($videoFile);
  }

}
