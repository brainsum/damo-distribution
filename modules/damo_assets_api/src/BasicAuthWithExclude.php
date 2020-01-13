<?php

namespace Drupal\damo_assets_api;

use Drupal;
use Drupal\basic_auth\Authentication\Provider\BasicAuth;
use Symfony\Component\HttpFoundation\Request;

/**
 * Exclude HTTP Basic authentication.
 */
class BasicAuthWithExclude extends BasicAuth {

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request) {
    $routeMatch = Drupal::routeMatch();
    // Enable Basic Auth only on the jsonapi routes.
    if ($routeMatch->getRouteObject() && !$routeMatch->getRouteObject()->getOption('_is_jsonapi')) {
      return FALSE;
    }

    return parent::applies($request);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request) {
    // Run authenticate only for non LDAP users.
    if (Drupal::moduleHandler()->moduleExists('ldap_user')) {
      $username = $request->headers->get('PHP_AUTH_USER');
      /** @var \Drupal\user\UserInterface[] $accounts */
      $accounts = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $username, 'status' => 1]);
      $account = reset($accounts);

      if ($account && $account->hasField('ldap_user_puid') && !$account->get('ldap_user_puid')->value) {
        return parent::authenticate($request);
      }

      return NULL;
    }

    return parent::authenticate($request);
  }

}
