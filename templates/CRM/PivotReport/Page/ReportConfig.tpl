<div id="pivot-report-config" class="hidden">
  <form>
    <div class="form-item">
      Configuration:
    </div>
    <div class="form-item">
      <select name="id" class="report-config-select">
        <option value="">{ts}-- Select Configuration --{/ts}</option>
        {html_options options=$configList}
      </select>
      {if ($canManagePivotReportConfig)}
        <div class="form-item">
          <input type="button" class="report-config-save-btn btn btn-primary hidden" value="{ts}Save Report{/ts}">
        </div>
        <div class="form-item">
          <input type="button" class="report-config-save-new-btn btn btn-primary" value="{ts}Save As New{/ts}">
        </div>
        <div class="form-item">
          <input type="button" class="report-config-delete-btn btn btn-danger hidden" value="{ts}Delete{/ts}">
        </div>
      {/if}
    </div>
    <div class="pivot-report-output-config">
      <div class="form-item">
        Chart Type:
      </div>
      <div id="pivot-report-type" class="form-item"></div>
      <div class="form-item right-align pivot-report-export-button">
          <button id="exportTSV"><i class="fa fa-file-excel-o" aria-hidden="true"></i> {ts}Export TSV{/ts}</button>
      </div>
      <div class="form-item right-align pivot-report-export-button">
        <button id="exportCSV"><i class="fa fa-file-excel-o" aria-hidden="true"></i> {ts}Export CSV{/ts}</button>
      </div>
    </div>
  </form>
</div>
