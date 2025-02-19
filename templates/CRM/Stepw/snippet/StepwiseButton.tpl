<div class="stepwise-button-wrapper">
  <a class="stepwise-button {if $buttonDisabled}stepwise-button-disabled{/if}" href="{$buttonHref}">
    <span class="stepwise-button-label">
      {$buttonText}
    </span>
  </a>
</div>
      
{* $buttonHref64 is a base64-encoded url, to be used as the href for a.stepwise-button after video enforcer
 * JS has verified that video was fully watched.
 *}
{if $buttonHref64}<span id="stepwise-data" data-data="{$buttonHref64}" style="display:none;"></span>{/if}
