<?php

namespace Drupal\damo_assets_download\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\media\MediaInterface;
use Drupal\damo_assets_download\Service\AssetDownloadHandler;
use Drupal\damo_assets_download\Service\FileResponseBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class DownloadController.
 *
 * @package Drupal\damo_assets_download\Controller
 */
class DownloadController extends ControllerBase {

  private $downloadHandler;

  private $fileResponseBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('damo_assets_download.asset_download_handler'),
      $container->get('damo_assets_download.file_response_builder')
    );
  }

  /**
   * DownloadController constructor.
   *
   * @param \Drupal\damo_assets_download\Service\AssetDownloadHandler $downloadHandler
   *   Download handler.
   * @param \Drupal\damo_assets_download\Service\FileResponseBuilder $fileResponseBuilder
   *   The file response builder.
   */
  public function __construct(
    AssetDownloadHandler $downloadHandler,
    FileResponseBuilder $fileResponseBuilder
  ) {
    $this->downloadHandler = $downloadHandler;
    $this->fileResponseBuilder = $fileResponseBuilder;
  }

  /**
   * Download handler for media entities.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   *
   * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
   *   Response.
   */
  public function download(MediaInterface $media): Response {
    $downloadableFile = $this->downloadHandler->generateDownloadableFile($media);

    if ($downloadableFile === NULL) {
      throw new HttpException(500, 'Generating a downloadable file failed.');
    }

    return $this->fileResponseBuilder->build($downloadableFile);
  }

}
