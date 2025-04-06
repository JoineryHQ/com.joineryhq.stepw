/* 
 * JavaScript for CRM_Stepw_Form_Report_WorkflowInstance report.
 */
CRM.$(function($) {
  console.log(CRM.vars);
  
  var setSelectAll = function setSelectAll(e) {
    e.preventDefault();
    var checkboxes;
    
    if($(this).data('isTopColumns')) {
      checkboxes = $('div#stepw-top-columns input[type="checkbox"]');      
    }
    else {
      checkboxes = $(this).closest('table').find('input[type="checkbox"]');
    }
    checkboxes.prop('checked', $(this).data('select'));
  }
  
  
  CRM.$('div.crm-report-criteria div#report-tab-col-groups').prepend('<div id="stepw-top-columns"></div>');
  CRM.$('div#stepw-top-columns').append(CRM.$('div.crm-report-criteria div#report-tab-col-groups > table.criteria-group'));
  CRM.$('div#stepw-top-columns table.criteria-group').first()
    .prepend('<tr><td colspan="4">'
       + CRM.vars['com.joineryhq.stepw'].selectButtonHtml 
       + CRM.vars['com.joineryhq.stepw'].deselectButtonHtml 
       + '</td></tr>'
    );
  CRM.$('div#stepw-top-columns a.button.stepw-set-select-all').data('isTopColumns', true);

  CRM.$('div.crm-report-criteria div#report-tab-col-groups div.crm-accordion-body table tbody')
    .prepend('<tr><td colspan="4">'
       + CRM.vars['com.joineryhq.stepw'].selectButtonHtml 
       + CRM.vars['com.joineryhq.stepw'].deselectButtonHtml 
       + '</td></tr>'
    );
  CRM.$('a.button.stepw-set-select-all').click(setSelectAll);
});
