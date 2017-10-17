<div id="pivot-report-config">
  <form>
    <div class="form-item">
      Configuration:
    </div>
    <div class="form-item">
      <select name="id" class="report-config-select">
        <option value="">{ts}-- Select Configuration --{/ts}</option>
        {html_options options=$configList}
      </select>
    </div>
    <br/>
    <div class="form-item">
      Chart Type:
    </div>
    <div id="pivot-report-type" class="form-item"></div>
    {if ($canManagePivotReportConfig)}
      <div class="form-item">
        <input type="button" class="report-config-save-btn btn btn-primary hidden" value="{ts}Save Report{/ts}">
      </div>
      <div class="form-item">
        <input type="button" class="report-config-save-new-btn btn btn-primary" value="{ts}Save As New{/ts}">
      </div>
      <div class="form-item">
        <input type="button" class="report-config-delete-btn btn btn-danger" value="{ts}Delete{/ts}">
      </div>
    {/if}
  </form>
</div>
<div id="">
</div>
