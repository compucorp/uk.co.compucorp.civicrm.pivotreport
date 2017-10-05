<div id="pivot-report-config">
  <form>
    <div class="form-item">
      Configuration:
    </div>
    <div class="form-item">
      <select name="id" class="report-config-select">
        <option value="">{ts}-- select configuration --{/ts}</option>
        {html_options options=$configList}
      </select>
    </div>
    {if ($canManagePivotReportConfig)}
      <div class="form-item">
        <input type="button" class="report-config-save-btn btn btn-primary" value="{ts}Save Report{/ts}">
      </div>
      <div class="form-item">
        <input type="text" name="report-config-new-label" placeholder="{ts}Enter new label{/ts}">
        <input type="button" class="report-config-save-new-btn btn btn-primary" value="{ts}Save As New{/ts}">
      </div>
      <div class="form-item">
        <input type="button" class="report-config-delete-btn btn btn-danger" value="{ts}Delete{/ts}">
      </div>
    {/if}
  </form>
</div>
