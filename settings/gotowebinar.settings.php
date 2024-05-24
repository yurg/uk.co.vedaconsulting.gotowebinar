<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use CRM_Gotowebinar_ExtensionUtil as E;

/**
 * Settings metadata file
 */
return [
  'gotowebinar_client_id' => [
    'name' => 'gotowebinar_client_id',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_attributes' => [
      'size' => 64,
      'maxlength' => 64,
    ],
    'html_type' => 'text',
    'default' => 'd9ffc0c8-ec34-4333-9339-4e353dfe3550',
    'add' => '4.7',
    'title' => E::ts('Gotowebinar Client ID'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('See https://developer.logmeininc.com/clients'),
    'help_text' => E::ts('See https://developer.logmeininc.com/clients'),
    'group_name' => 'Gotowebinar Preferences',
    'group' => 'core',
   // 'settings_pages' => [],
  ],
//   'forceRecaptcha' => [
//     'add' => '4.7',
//     'help_text' => NULL,
//     'is_domain' => 1,
//     'is_contact' => 0,
//     'group_name' => 'CiviCRM Preferences',
//     'group' => 'core',
//     'name' => 'forceRecaptcha',
//     'type' => 'Boolean',
//     'quick_form_type' => 'YesNo',
//     'html_type' => '',
//     'default' => '0',
//     'title' => E::ts('Force reCAPTCHA on Contribution pages'),
//     'description' => E::ts('If enabled, reCAPTCHA will show on all contribution pages.'),
//     'settings_pages' => [
//       'recaptcha' => [
//         'weight' => 10,
//       ],
//     ],
//   ],
];
