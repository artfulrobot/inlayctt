<?php

require_once 'inlayctt.civix.php';
// phpcs:disable
use CRM_Inlayctt_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function inlayctt_civicrm_config(&$config) {
  _inlayctt_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_container().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_container/
 */
function inlayctt_civicrm_container($container) {
  $container->findDefinition('dispatcher')
    ->addMethodCall('addListener', ['hook_inlay_registerType', [Civi\Inlay\ClickToTweet::class, 'register']])
  ;
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function inlayctt_civicrm_install() {
  _inlayctt_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function inlayctt_civicrm_enable() {
  _inlayctt_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function inlayctt_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function inlayctt_civicrm_navigationMenu(&$menu) {
//  _inlayctt_civix_insert_navigation_menu($menu, 'Mailings', [
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ]);
//  _inlayctt_civix_navigationMenu($menu);
//}
