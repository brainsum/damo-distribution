<?php

namespace Drupal\damo_assets\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class AssetListLocalTasks.
 *
 * @package Drupal\damo_assets\Plugin\Derivative
 */
class AssetListLocalTasks extends DeriverBase {

  use StringTranslationTrait;

  public const ROUTE_NAME = 'view.asset_search.asset_search';
  public const BASE_ID = 'damo_assets.asset_list';
  public const TARGET_BASE_ROUTES = [
    'frontpage' => 'view.asset_search.asset_search',
  ];

  /**
   * Get type data.
   *
   * @return array
   *   The data.
   */
  protected function getTypeMapping(): array {
    return [
      'image' => [
        'title' => $this->t('Images'),
        'route_param' => 'image',
      ],
      'video' => [
        'title' => $this->t('Video'),
        'route_param' => 'video',
      ],
      'video_file' => [
        'title' => $this->t('Video files'),
        'route_param' => 'video_file',
      ],
      'template' => [
        'title' => $this->t('Templates'),
        'route_param' => 'template',
      ],
      'logo' => [
        'title' => $this->t('Logo'),
        'route_param' => 'logo',
      ],
      'guideline' => [
        'title' => $this->t('Guidelines'),
        'route_param' => 'guideline',
      ],
      'icon' => [
        'title' => $this->t('Icons'),
        'route_param' => 'icon',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $types = $this->getTypeMapping();
    foreach (static::TARGET_BASE_ROUTES as $baseRouteId => $baseRouteName) {
      $weight = 0;

      foreach ($types as $typeId => $type) {
        // The actual tab name will be static::BASE_ID . $taskId.
        $taskId = "$baseRouteId:type.$typeId";

        $this->derivatives[$taskId] = $base_plugin_definition;
        $this->derivatives[$taskId]['title'] = $type['title'];
        $this->derivatives[$taskId]['route_name'] = static::ROUTE_NAME;
        $this->derivatives[$taskId]['route_parameters']['type'] = $type['route_param'];
        $this->derivatives[$taskId]['base_route'] = $baseRouteName;
        $this->derivatives[$taskId]['weight'] = $weight;

        $baseClass = 'media-asset-task';
        $typeClass = $baseClass . "-$typeId";
        if (!isset($this->derivatives[$taskId]['options']['attributes']['class'])) {
          $this->derivatives[$taskId]['options']['attributes']['class'] = [
            $baseClass,
            $typeClass,
          ];
        }
        else {
          $this->derivatives[$taskId]['options']['attributes']['class'][] = $baseClass;
          $this->derivatives[$taskId]['options']['attributes']['class'][] = $typeClass;
        }

        ++$weight;
      }
    }

    return $this->derivatives;
  }

}
