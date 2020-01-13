<?php

namespace Drupal\damo_assets_download\Service;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\file\FileInterface;
use SplFileInfo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FileResponseBuilder.
 *
 * @package Drupal\damo_assets_download\Response
 */
class FileResponseBuilder {

  /**
   * Transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  private $transliteration;

  /**
   * FileResponseBuilder constructor.
   *
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   Transliteration service.
   */
  public function __construct(
    TransliterationInterface $transliteration
  ) {
    $this->transliteration = $transliteration;
  }

  /**
   * Builds a BinaryFileResponse from a file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file.
   * @param string $description
   *   Optional description.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The file response.
   */
  public function build(
    FileInterface $file,
    string $description = 'Media Library assets download'
  ): BinaryFileResponse {
    $fileInfo = new SplFileInfo($file->getFileUri());
    $response = new BinaryFileResponse(
      $fileInfo,
      Response::HTTP_OK,
      [
        'Content-Description' => $description,
      ]
    );

    $response->setContentDisposition(
      'attachment',
      $this->transliteration->transliterate($file->getFilename()),
      $this->transliteration->transliterate($fileInfo->getFilename())
    );

    return $response;
  }

}
