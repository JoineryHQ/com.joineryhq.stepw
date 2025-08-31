{* HEADER *}
{* Display top submit button only if there are more than three elements on the page *}
{if ($settingElementNames|@count) gt 3}
  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="top"}
  </div>
{/if}

{* FIELDS (AUTOMATIC LAYOUT) *}

{foreach from=$settingElementNames item=settingElementName}
  <div class="crm-section">
    <div class="label">{$form.$settingElementName.label}</div>
    <div class="content">{$form.$settingElementName.html}<div class="description">{$descriptions.$settingElementName}</div></div>
    <div class="clear"></div>
  </div>
{/foreach}

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
