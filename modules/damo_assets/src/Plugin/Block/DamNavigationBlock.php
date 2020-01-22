<?php

namespace Drupal\damo_assets\Plugin\Block;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use function implode;
use function in_array;

/**
 * Class DamNavigationBlock.
 *
 * @Block(
 *   id = "dam_navigation_block",
 *   admin_label = @Translation("DAM Navigation"),
 *   category = @Translation("DAM")
 * )
 *
 * @package Drupal\damo_assets\Plugin\Block
 */
class DamNavigationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $user;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * DamNavigationBlock constructor.
   *
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RequestStack $requestStack,
    RouteMatchInterface $routeMatch,
    AccountProxyInterface $user,
    EntityTypeManagerInterface $entityTypeManager,
    ModuleHandlerInterface $moduleHandler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->request = $requestStack->getCurrentRequest();
    $this->routeMatch = $routeMatch;
    $this->user = $user;
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
  }

  public const FRONTPAGE_ROUTE = 'view.asset_search.asset_search';

  /**
   * Get type data.
   *
   * @return array
   *   The data.
   */
  protected function getTypeMapping(): array {
    $mapping = [
      'image' => [
        'type' => 'image',
        'title' => $this->t('Images'),
        'route_param' => ['image'],
      ],
      'video' => [
        'type' => 'video',
        'title' => $this->t('Video files & embeds'),
        'route_param' => ['video', 'video_file'],
      ],
      'template' => [
        'type' => 'template',
        'title' => $this->t('Templates'),
        'route_param' => ['template'],
      ],
      'logo' => [
        'type' => 'logo',
        'title' => $this->t('Logo'),
        'route_param' => ['logo'],
      ],
      'guideline' => [
        'type' => 'guideline',
        'title' => $this->t('Guidelines'),
        'route_param' => ['guideline'],
      ],
      'icon' => [
        'type' => 'icon',
        'title' => $this->t('Icons'),
        'route_param' => ['icon'],
      ],
    ];
    _damo_assets_order_bundles($mapping);
    return $mapping;
  }

  /**
   * Helper for returning the default type.
   *
   * @return array
   *   The default type data.
   */
  protected function defaultType(): array {
    return $this->getTypeMapping()['image'];
  }

  /**
   * Build tabs.
   *
   * @return array
   *   Render array.
   */
  protected function buildTabs(): array {
    $tabs = [];
    $activeTab = $this->determineActiveTab();
    foreach ($this->getTypeMapping() as $typeId => $typeData) {
      if (0 === $this->mediaCount($typeData['route_param'])) {
        continue;
      }

      $tabClasses = [
        'media-asset-task',
      ];
      $wrapperClasses = [
        'nav-item',
      ];
      $tabClasses[] = "media-asset-task-$typeId";
      if ($typeId === $activeTab) {
        $tabClasses[] = 'active';
        $wrapperClasses[] = 'active';
      }

      $link = Link::createFromRoute(
        $typeData['title'],
        static::FRONTPAGE_ROUTE,
        [
          'type' => implode(' ', $typeData['route_param']),
        ],
        [
          'attributes' => [
            'class' => $tabClasses,
          ],
        ]
      )->toString()->getGeneratedLink();

      $tabs[] = [
        '#markup' => $link,
        '#wrapper_attributes' => [
          'class' => $wrapperClasses,
        ],
        '#cache' => [
          'contexts' => [
            'url.query_args:type',
          ],
        ],
      ];
    }

    return $tabs;
  }

  /**
   * Return the count of media entities from the given types.
   *
   * @param array $types
   *   The types for which to query.
   *
   * @return int
   *   The entity count.
   */
  protected function mediaCount(array $types): int {
    $count = 0;

    try {
      $count = $this->entityTypeManager
        ->getStorage('media')
        ->getQuery()
        ->condition('bundle', $types, 'in')
        ->condition('status', 1)
        ->count()
        ->execute();
    }
    catch (Exception $exception) {
      // Pass.
    }

    return $count;
  }

  /**
   * Determines the active tab.
   *
   * @return string|null
   *   Active tab type or NULL.
   */
  protected function determineActiveTab(): ?string {
    $currentType = _damo_assets_request_to_type($this->request);
    $currentRoute = $this->routeMatch->getRouteName();

    if ($currentRoute === static::FRONTPAGE_ROUTE || $currentRoute === 'entity.media.canonical') {
      return $currentType ?? $this->defaultType()['type'];
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [
      '#type' => 'container',
      'tabs' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $this->buildTabs(),
        '#attributes' => [
          'class' => [
            'nav',
          ],
        ],
        '#prefix' => '<nav class="tabs-wrapper clearfix">',
        '#suffix' => '</nav>',
      ],
    ];
    $routeName = Drupal::routeMatch()->getRouteName();

    if (!in_array($routeName, ['entity.media.add_page', 'entity.media.add_form'], TRUE)) {
      $build['extension_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'dam-local-task-extension-wrapper',
          ],
        ],
      ];

      $build['extension_wrapper']['add_media'] = [
        '#type' => 'link',
        '#url' => Url::fromRoute('entity.media.add_page'),
        '#title' => t('Add new content'),
        '#attributes' => [
          'class' => [
            'dam-add-media-asset-link',
          ],
        ],
        '#access' => $this->user->hasPermission('create media'),
        '#cache' => [
          'contexts' => [
            'user.permissions',
          ],
        ],
      ];

      if ($this->moduleHandler->moduleExists('media_collection')) {
        /** @var \Drupal\media_collection\Service\HeaderMarkupGenerator $generator */
        $generator = Drupal::service('media_collection.generator.header_markup');

        $build['extension_wrapper']['media_collection__empty'] = $generator->emptyMediaCollectionLink();
        $build['extension_wrapper']['media_collection__with_items'] = $generator->withItemsMediaCollectionLink();
      }

      $build['back_button'] = [
        '#prefix' => '<div class="dam-local-task-back-button">',
        '#suffix' => '</div>',
        '#type' => 'markup',
        '#markup' => '<a href="#" onClick="history.go(-1);return true;">' . t('Back to the list') . '</a>',
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    // Protect block access with permission check.
    if ($account->hasPermission('access media asset navigation block')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(['media_list', 'media_collection_item_list'], parent::getCacheTags());
  }

}
