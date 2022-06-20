<?php
use CRM_Inlayctt_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Inlayctt_Upgrader extends CRM_Inlayctt_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   *
   */
  public function install() {
    $this->ensureDataStructures();
  }

  /**
   * Looks for the given thing or creates it.
   *
   * Returns the ID or an array containing at least the id and values of the idParams
   */
  public function createOrUpdate(string $entity, array $idParams, array $defaultParams, array $forcedParams = [], bool $returnID = TRUE) {
    $wheres = [];
    $selects = ['id'];
    foreach ($idParams as $field => $value) {
      $wheres[] = [$field, '=', $value];
      $selects[] = $field;
    }
    $selects = array_merge($selects, array_keys($forcedParams));

    $existingResult = civicrm_api4($entity, 'get', [
      'checkPermissions' => FALSE,
      'select' => $selects,
      'where' => $wheres,
    ])->first();

    if ($existingResult) {
      // Already in db. Check forcedParams
      $updates = [];
      foreach ($forcedParams as $field => $value) {
        if (!isset($existingResult[$field]) || $value !== $existingResult[$field]) {
          $updates[$field] = $value;
        }
      }
      if ($updates && !empty($existingResult['id'])) {
        civicrm_api4($entity, 'save', [
          'checkPermissions' => FALSE,
          'records' => [
            [ 'id' => $existingResult['id'] ] + $updates
          ]
        ]);
      }
      $result = $existingResult;
    }
    else {
      // Required entity does not exist, create it now.
      $result = civicrm_api4($entity, 'create', [
        'checkPermissions' => FALSE,
        'values' => $idParams + $forcedParams + $defaultParams
        ])->first();
    }

    return $returnID ? ($result['id'] ?? NULL) : $result;
  }

  /**
   *
   */
  public function ensureDataStructures() {

    // Create option group.
    $optionGroupID = $this->createOrUpdate('OptionGroup',
      ['name' => 'click_to_tweet_field'],
      [
        'title' => "Click to Tweet field",
        'data_type' => 'String',
        'is_reserved' => FALSE,
        'is_active' => TRUE,
      ]
    );

    // We need a custom dataset to record comments and their privacy on contributions.
    $contribCustomGroupID = $this->createOrUpdate('CustomGroup',
      ['name' => 'inlayctt_content'],
      [
        'title' => 'Click to Tweet campaign',
        'extends'=> 'Contact',
        'style' => 'Tab with table',
        'collapse_display' => FALSE,
        'is_active' => TRUE,
        'table_name' => 'civicrm_value_inlayctt_content',
        'is_multiple' => TRUE,
        'collapse_adv_display' => TRUE,
        'icon' => 'fa-twitter-square',
      ]
    );

    $this->createOrUpdate('CustomField',
      ['name' => 'inlayctt_field'],
      [
        'label' => 'Click to tweet content field',
        'custom_group_id' => $contribCustomGroupID,
        'data_type' => 'String',
        'html_type' => 'Select',
        'default_value' => NULL,
        'is_active' => TRUE,
        'is_required' => TRUE,
        'is_searchable' => TRUE,
        'text_length' => 64,
        'option_group_id' => $optionGroupID,
        'column_name' => 'fieldname',
      ]
    );

    // Create the comment/message field.
    $this->createOrUpdate('CustomField',
      ['name' => 'inlayctt_content'],
      [
        'custom_group_id' => $contribCustomGroupID,
        'name' => 'content',
        'column_name' => 'content',
        'label' => 'Content',
        'data_type' => 'String',
        'html_type' => 'Text',
        'default_value' => NULL,
        'is_active' => TRUE,
      ]
    );

  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   */
  // public function postInstall() {
  //  $customFieldId = civicrm_api3('CustomField', 'getvalue', array(
  //    'return' => array("id"),
  //    'name' => "customFieldCreatedViaManagedHook",
  //  ));
  //  civicrm_api3('Setting', 'create', array(
  //    'myWeirdFieldSetting' => array('id' => $customFieldId, 'weirdness' => 1),
  //  ));
  // }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   */
  // public function uninstall() {
  //  $this->executeSqlFile('sql/myuninstall.sql');
  // }

  /**
   * Example: Run a simple query when a module is enabled.
   */
  // public function enable() {
  //  CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  // }

  /**
   * Example: Run a simple query when a module is disabled.
   */
  // public function disable() {
  //   CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  // }

  /**
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0001(): bool {
    $this->ctx->log->info('Applying update 0001');
    $this->ensureDataStructures();
    return TRUE;
  }


  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4201(): bool {
  //   $this->ctx->log->info('Applying update 4201');
  //   // this path is relative to the extension base dir
  //   $this->executeSqlFile('sql/upgrade_4201.sql');
  //   return TRUE;
  // }


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4202(): bool {
  //   $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

  //   $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
  //   $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
  //   $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
  //   return TRUE;
  // }
  // public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  // public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  // public function processPart3($arg5) { sleep(10); return TRUE; }

  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
   */
  // public function upgrade_4203(): bool {
  //   $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

  //   $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
  //   $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
  //   for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
  //     $endId = $startId + self::BATCH_SIZE - 1;
  //     $title = E::ts('Upgrade Batch (%1 => %2)', array(
  //       1 => $startId,
  //       2 => $endId,
  //     ));
  //     $sql = '
  //       UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
  //       WHERE id BETWEEN %1 and %2
  //     ';
  //     $params = array(
  //       1 => array($startId, 'Integer'),
  //       2 => array($endId, 'Integer'),
  //     );
  //     $this->addTask($title, 'executeSql', $sql, $params);
  //   }
  //   return TRUE;
  // }

}
