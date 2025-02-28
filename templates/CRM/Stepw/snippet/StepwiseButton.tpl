{foreach from=$buttons item=button key=key}
  <div class="stepwise-button-wrapper" id="stepwise-button-wrapper-{$key}">
    <a 
      class="stepwise-button {if $button.disabled}stepwise-button-disabled{/if}" 
      href="{$button.href}" 
      id="stepwise-button-{$key}" 
      data-data="{$button.href64}"
    >
      <span class="stepwise-button-label" id="stepwise-button-label-{$key}">
        {$button.text}
      </span>
    </a>
  </div>

  {* $buttonHref64 is a base64-encoded url, to be used as the href for a.stepwise-button after onpage enforcer
   * JS has verified that use has completed on-page actions.
   *}
{/foreach}