<?php

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class StepwWorkflowGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {

    // URL field
    $field = new FieldSpec('url', $spec->getEntity(), 'String');
    $field->setLabel(ts('Url'))
      ->setTitle(ts('Url'))
      ->setColumnName('public_id')
      ->setInputType('String')
      ->setDescription(ts('URL to start this workflow'))
      ->setType('Extra')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'calculateUrl']);
    $spec->addFieldSpec($field);

  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    return $entity === 'StepwWorkflow' && $action === 'get';
  }

  /**
   * Generate SQL for url field
   * @param array $field
   * @param Civi\Api4\Query\Api4SelectQuery $query
   * @return string
   */
  public static function calculateUrl(array $field, $query): string {
    $baseUrl = \CRM_Utils_System::url('civicrm/stepwise/step', 'stepw_wid=', TRUE, NULL, FALSE, TRUE);
    return "CONCAT('$baseUrl', " . $field['sql_name'] . ")";
  }

}
