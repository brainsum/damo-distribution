<?php

namespace Drupal\damo_assets_api;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\damo_assets_api\Authentication\Provider\BasicAuthWithExclude;

/**
 * Service alter.
 */
class DamoAssetsApiServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Change basic auth class to our custom one.
    if ($container->hasDefinition('basic_auth.authentication.basic_auth')) {
      $container
        ->getDefinition('basic_auth.authentication.basic_auth')
        ->setClass(BasicAuthWithExclude::class);
    }
  }

}
