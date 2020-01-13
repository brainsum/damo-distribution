<?php

namespace Drupal\damo_assets\Plugin\Derivative;

use Drupal;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use function strtolower;

/**
 * Class AssetLocalActions.
 *
 * @package Drupal\damo_assets\Plugin\Derivative
 */
class AssetLocalActions extends DeriverBase {

  use StringTranslationTrait;

  /**
   * Media type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaTypeStorage;

  /**
   * AssetLocalActions constructor.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct() {
    // @todo: Dep. inj.
    $this->mediaTypeStorage = Drupal::entityTypeManager()->getStorage('media_type');
  }

  /**
   * A list of routes where the derivatives should show.
   *
   * @return array
   *   The list.
   */
  protected function derivativeLocations(): array {
    return [
      'view.asset_search.asset_search',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->getMediaTypes() as $typeId => $definition) {
      $actionId = "add.$typeId";

      $this->derivatives[$actionId] = $base_plugin_definition;
      $this->derivatives[$actionId]['parent_id'] = "damo_assets.asset_local_actions:$actionId";
      $this->derivatives[$actionId]['title'] = $this->t('Add @type', [
        '@type' => strtolower($definition->label()),
      ]);
      $this->derivatives[$actionId]['route_name'] = 'entity.media.add_form';
      $this->derivatives[$actionId]['route_parameters']['media_bundle'] = $typeId;
      $this->derivatives[$actionId]['weight'] = 0;
      $this->derivatives[$actionId]['appears_on'] = $this->derivativeLocations();

      $baseClass = 'media-asset-action';
      $typeClass = "{$baseClass}-{$typeId}";

      if (!isset($this->derivatives[$actionId]['options']['attributes']['class'])) {
        $this->derivatives[$actionId]['options']['attributes']['class'] = [
          $baseClass,
          $typeClass,
        ];
      }
      else {
        $this->derivatives[$actionId]['options']['attributes']['class'][] = $baseClass;
        $this->derivatives[$actionId]['options']['attributes']['class'][] = $typeClass;
      }
    }

    return $this->derivatives;
  }

  /**
   * Get the media bundles.
   *
   * @return \Drupal\media\MediaTypeInterface[]
   *   The bundles keyed by bundle ID.
   */
  protected function getMediaTypes(): array {
    return $this->mediaTypeStorage->loadMultiple();
  }

}
