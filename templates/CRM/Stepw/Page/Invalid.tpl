{crmScope extensionKey='com.joineryhq.stepw'}
{* Developer's note: I tried using CRM_Core_Session::setStatus() in the page run()
 * method, but there seems to be no way to make it print an 'error' message, only
 * 'info'.
 *}
<div class="messages status no-popup crm-error" data-options="null">
  <i aria-hidden="true" class="crm-i fa-exclamation-triangle"></i>    
  <span class="msg-title">{ts}Error.{/ts}</span>
  <span class="msg-text">{ts}Your request appears to be invalid. Please start again from the beginning.{/ts}</span>
</div>
{foreach from=$messages item=message}
  <div class="messages status no-popup crm-warning" data-options="null">
    <span class="msg-text">{$message}</span>
  </div>
{/foreach}

{/crmScope}

