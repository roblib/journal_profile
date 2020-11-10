<?php

namespace Drupal\journal_references\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach (['bibcite_import.populate', 'bibcite_import.import'] as $routename) {
      if ($route = $collection->get($routename)) {
        $route->setRequirement('_permission', 'create bibcite_reference');
      }


    }
  }
}