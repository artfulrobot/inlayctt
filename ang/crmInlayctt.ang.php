<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// \https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules/n
return [
  'js' => [
    'ang/crmInlayctt.js',
    'ang/crmInlayctt/*.js',
    'ang/crmInlayctt/*/*.js',
  ],
  'css' => [
    'ang/crmInlayctt.css',
  ],
  'partials' => [
    'ang/crmInlayctt',
  ],
  'requires' => [
    'crmUi',
    'crmUtil',
    'ngRoute',
  ],
  'settings' => [],
];
