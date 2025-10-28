<?php
/**
 * Copied/forked from CRM_Report_Form_Activity, CiviCRM 5.81.0
 */

use CRM_Stepw_ExtensionUtil as E;

class CRM_Stepw_Form_Report_WorkflowInstances extends CRM_Report_Form {

  protected $_customGroupExtends = [
    'Activity',
    'Individual',
    'Organization',
    'Contact',
  ];

  /**
   * Comment copied from CRM_Report_Form_Activity:
   *
   * This report has not been optimised for group filtering.
   *
   * The functionality for group filtering has been improved but not
   * all reports have been adjusted to take care of it. This report has not
   * and will run an inefficient query until fixed.
   *
   * @var bool
   * @see https://issues.civicrm.org/jira/browse/CRM-19170
   */
  protected $groupFilterNotOptimised = TRUE;

  /**
   * Class constructor.
   */
  public function __construct() {
    $workflowOptions = [];
    $stepwWorkflows = \Civi\Api4\StepwWorkflow::get(TRUE)
      ->addSelect('id', 'title')
      ->addWhere('is_active', '=', TRUE)
      ->execute();
    foreach ($stepwWorkflows as $stepwWorkflow) {
      $workflowOptions[$stepwWorkflow['id']] = $stepwWorkflow['title'];
    }

    $this->_columns = [
      'civicrm_stepw_workflow' => [
        'fields' => [
          'title' => [
            'title' => E::ts('Workflow Title'),
          ],
        ],
        'filters' => [
          'id' => [
            'title' => E::ts('Workflow'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $workflowOptions,
          ],
        ],
        'grouping' => 'workflow-instance-fields',
      ],
      'civicrm_stepw_workflow_instance' => [
        'fields' => [
          'wi_created' => [
            'name' => 'created',
            'title' => E::ts('Submission Date/Time'),
            'default' => TRUE,
          ],
        ],
        'filters' => [
          'wi_created' => [
            'title' => E::ts('Submission Completed Date/Time'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
        'grouping' => 'workflow-instance-fields',
      ],
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'sort_name' => [
            'title' => E::ts('Contact Name (sortable)'),
            'no_repeat' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'default' => TRUE,
            'required' => TRUE,
          ],
          'employer_id' => [
            'no_display' => TRUE,
            'default' => TRUE,
            'required' => TRUE,
          ],
          'gender_id' => [
            'title' => E::ts('Gender'),
          ],
          'birth_date' => [
            'title' => E::ts('Birth Date'),
          ],
          'age' => [
            'title' => E::ts('Age at Submission Date/Time'),
            'dbAlias' => 'TIMESTAMPDIFF(YEAR, contact_civireport.birth_date, stepw_workflow_instance_civireport.created)',
          ],
        ],
        'filters' => [
          'sort_name' => [
            'title' => E::ts('Contact Name'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
          'age' => [
            'title' => E::ts('Age at Submission Date/Time'),
            'dbAlias' => 'TIMESTAMPDIFF(YEAR, contact_civireport.birth_date, stepw_workflow_instance_civireport.created)',
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_INT,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'email' => [
            'title' => E::ts('Email'),
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => [
          'phone' => [
            'title' => E::ts('Phone'),
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_address' => [
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => [
          'postal_code' => [
            'title' => E::ts('Postal Code'),
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_employer' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'employer_display_name' => [
            'name' => 'display_name',
            'title' => E::ts('Employer'),
          ],
          'employer_conact_id' => [
            'name' => 'id',
            'no_display' => TRUE,
            'default' => TRUE,
            'required' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      // Hack to get $this->_alias populated for the table.
      'civicrm_activity' => [
        'dao' => 'CRM_Activity_DAO_Activity',
        'fields' => [],
      ],
      // Hack to get $this->_alias populated for the table.
      'civicrm_activity_contact' => [
        'dao' => 'CRM_Activity_DAO_ActivityContact',
        'fields' => [],
      ],
    ];

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    $this->_tagFilterTable = 'civicrm_activity';

    parent::__construct();
    $a = 1;

    $smarty = CRM_Core_Smarty::singleton();
    $settings = [
      'selectButtonHtml' => $smarty->fetch('string:{crmButton title="Select All" icon="fa-check-square-o" href="#" class="stepw-set-select-all" data-select=1}Select All{/crmButton}'),
      'deselectButtonHtml' => $smarty->fetch('string:{crmButton title="Deselect All" icon="fa-square-o" href="#" class="stepw-set-select-all" data-select=0}Deselect All{/crmButton}'),
    ];
    CRM_Core_Resources::singleton()->addVars(E::LONG_NAME, $settings);
    CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, 'js/' . __CLASS__ . '.js');
  }

  /**
   * Build from clause.
   * @todo remove this function & declare the 3 contact tables separately
   */
  public function from() {

    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $sourceRecordTypeID = CRM_Utils_Array::key('Activity Source', $activityContacts);

    $this->_from = "
      FROM civicrm_activity {$this->_aliases['civicrm_activity']}
           INNER JOIN civicrm_activity_contact  {$this->_aliases['civicrm_activity_contact']}
                  ON {$this->_aliases['civicrm_activity']}.id = {$this->_aliases['civicrm_activity_contact']}.activity_id AND
                     {$this->_aliases['civicrm_activity_contact']}.record_type_id = {$sourceRecordTypeID}
           INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                  ON {$this->_aliases['civicrm_activity_contact']}.contact_id = {$this->_aliases['civicrm_contact']}.id
                    AND NOT {$this->_aliases['civicrm_contact']}.is_deleted
           {$this->_aclFrom}
          INNER JOIN civicrm_stepw_workflow_instance_step wis
            ON wis.activity_id = {$this->_aliases['civicrm_activity']}.id
          INNER JOIN civicrm_stepw_workflow_instance AS {$this->_aliases['civicrm_stepw_workflow_instance']}
            ON {$this->_aliases['civicrm_stepw_workflow_instance']}.id = wis.workflow_instance_id
              AND {$this->_aliases['civicrm_stepw_workflow_instance']}.closed IS NOT NULL
          INNER JOIN civicrm_stepw_workflow AS {$this->_aliases['civicrm_stepw_workflow']}
            ON {$this->_aliases['civicrm_stepw_workflow_instance']}.workflow_id = {$this->_aliases['civicrm_stepw_workflow']}.id
        ";

    if ($this->isTableSelected('civicrm_email')) {
      $this->_from .= "
          LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
                 ON {$this->_aliases['civicrm_activity_contact']}.contact_id = {$this->_aliases['civicrm_email']}.contact_id AND
                    {$this->_aliases['civicrm_email']}.is_primary = 1";
    }

    if ($this->isTableSelected('civicrm_phone')) {
      $this->_from .= "
          LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
                 ON {$this->_aliases['civicrm_activity_contact']}.contact_id = {$this->_aliases['civicrm_phone']}.contact_id AND
                    {$this->_aliases['civicrm_phone']}.is_primary = 1 ";
    }

    if ($this->isTableSelected('civicrm_address')) {
      $this->_from .= "
          LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                 ON {$this->_aliases['civicrm_activity_contact']}.contact_id = {$this->_aliases['civicrm_address']}.contact_id AND
                    {$this->_aliases['civicrm_address']}.is_primary = 1 ";
    }

    if ($this->isTableSelected('civicrm_employer')) {
      $this->_from .= "
          LEFT JOIN civicrm_contact {$this->_aliases['civicrm_employer']}
                 ON {$this->_aliases['civicrm_contact']}.employer_id = {$this->_aliases['civicrm_employer']}.id";
    }

  }

  /**
   * Re-work SELECT clause to account for GROUP BY workflow_instance.id. Because
   * most of the fields in this report are attached to one of several activities
   * in the workflow instance, merely grouping by worfklow_instance.id has the
   * effect of displaying NULL values for all but one of those activity's fields.
   * To remedy this, we wrap all fields in max(), so as to remove NULL values.
   * As for fields NOT attached to activities, those should all be the same (e.g.
   * submitting Contact ID), so max() should not have detrimental effects there.
   */
  public function select() {
    parent::select();

    $select = [];
    foreach ($this->_selectClauses as $selectClause) {
      $select[] = preg_replace('/(.+) (as .+?)/', 'max($1) $2', $selectClause);
    }
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * Override group by function.
   */
  public function groupBy() {
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_stepw_workflow_instance']}.id";
  }

  /**
   * Build order by clause.
   */
  public function orderBy() {
    $this->_orderBy = "ORDER BY {$this->_aliases['civicrm_stepw_workflow_instance']}.created";
  }

  /**
   * @param int $groupID
   *
   * @throws Exception
   */
  public function _add2group($groupID) {}

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   *
   */
  public function alterDisplay(&$rows) {
    $entryFound = FALSE;
    $genders = CRM_Contact_DAO_Contact::buildOptions('gender_id');
    $viewLinks = FALSE;

    if (CRM_Core_Permission::check('access CiviCRM')) {
      $viewLinks = TRUE;
      $onHover = E::ts('View Contact Summary for this Contact');
    }
    foreach ($rows as $rowNum => $row) {
      if (array_key_exists('civicrm_contact_sort_name', $row)) {
        if ($value = $row['civicrm_contact_id']) {
          if ($viewLinks) {
            $url = CRM_Utils_System::url('civicrm/contact/view',
              'reset=1&cid=' . $value,
              $this->_absoluteUrl
            );
            $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
            $rows[$rowNum]['civicrm_contact_sort_name_hover'] = $onHover;
          }
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_employer_employer_display_name', $row)) {
        if ($value = $row['civicrm_employer_employer_contact_id']) {
          if ($viewLinks) {
            $url = CRM_Utils_System::url('civicrm/contact/view',
              'reset=1&cid=' . $value,
              $this->_absoluteUrl
            );
            $rows[$rowNum]['civicrm_employer_employer_display_name_link'] = $url;
            $rows[$rowNum]['civicrm_employer_employer_display_name_hover'] = $onHover;
          }
          $entryFound = TRUE;
        }
      }

      if (array_key_exists('civicrm_contact_gender_id', $row)) {
        if ($value = $row['civicrm_contact_gender_id']) {
          $rows[$rowNum]['civicrm_contact_gender_id'] = $genders[$value];
          $entryFound = TRUE;
        }
      }

      if (!$entryFound) {
        break;
      }
    }
  }

}
