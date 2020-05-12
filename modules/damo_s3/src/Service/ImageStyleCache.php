<?php

namespace Drupal\damo_s3\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use function explode;
use function strpos;

/**
 * Class ImageStyleCache.
 *
 * @package Drupal\damo_s3\Service
 */
class ImageStyleCache {

  /**
   * The cache max age.
   *
   * @var int
   */
  private $maxAge;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * ImageStyleCache constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   */
  public function __construct(
    ModuleHandlerInterface $moduleHandler,
    ConfigFactoryInterface $configFactory
  ) {
    $this->moduleHandler = $moduleHandler;
    $this->configFactory = $configFactory;
  }

  /**
   * Calculates the cache max age for image styles.
   *
   * @return int
   */
  protected function calculateMaxAge(): int {
    if ($this->moduleHandler->moduleExists('damo_s3')) {
      $presignedUrls = explode("\r\n", $this->configFactory->get('s3fs.settings')->get('presigned_urls'));

      foreach ($presignedUrls as $url) {
        if (strpos($url, 'style') !== FALSE) {
          return (int) explode('|', $url)[0];
        }
      }
    }

    return (int) $this->configFactory->get('system.performance')->get('cache.page.max_age');
  }

  /**
   * Returns the cache max age for image styles.
   *
   * @return int
   *   The max age.
   */
  public function maxAge(): int {
    if ($this->maxAge === NULL) {
      $this->maxAge = $this->calculateMaxAge();
    }

    return $this->maxAge;
  }

}
