<?php

/**
 * @file
 * Enables modules and site configuration for the Journal profile.
 */

// Add any custom code here, like hook implementations.
function journal_install_tasks() {
  include_once(DRUPAL_ROOT . '/' . drupal_get_path('profile', 'lightning') . '/lightning.profile');

  $tasks = [];

  $tasks['lightning_set_front_page'] = [];
  $tasks['lightning_disallow_free_registration'] = [];
  $tasks['lightning_grant_shortcut_access'] = [];
  $tasks['lightning_set_default_theme'] = [];
  $tasks['lightning_set_logo'] = [];
  $tasks['lightning_alter_frontpage_view'] = [];

  return $tasks;

}
