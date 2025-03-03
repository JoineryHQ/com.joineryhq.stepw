{if !$omitProgressbar}
  <div class="stepwise-progress-wrapper" style="display:none;">
    <div class="stepwise-progress-title">Step {$stepOrdinal} of {$stepTotalCount}</div>
    <div class="stepwise-progress-bar">
      <div class="stepwise-progress-complete" style="width: {$percentage}%">
        <div class="stepwise-progress-complete-label">
          {$percentage}%
        </div>
      </div>
    </div>
  </div>
{/if}