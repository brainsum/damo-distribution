<?php

namespace Drupal\damo_assets\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\damo_assets\Form\BulkMediaUploadForm;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscribe to media bulk upload paths.
 *
 * @package Drupal\damo_assets\Routing
 */
class MediaUploadRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // @todo: This requires the media_upload module.
    if ($route = $collection->get('media_upload.bulk_media_upload')) {
      $route->setDefault('_form', BulkMediaUploadForm::class);
    }
  }

}
