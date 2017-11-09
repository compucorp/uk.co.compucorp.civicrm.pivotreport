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
    </div>
    <br/>
    <div class="form-item">
      Chart Type:
    </div>
    <div id="pivot-report-type" class="form-item"></div>
    {if $canManagePivotReportConfig}
      <div class="form-item">
        <input type="button" class="report-config-save-btn btn btn-primary" value="{ts}Save Report{/ts}" style="display: none;">
      </div>
      <div class="form-item">
        <input type="button" class="report-config-save-new-btn btn btn-primary" value="{ts}Save As New{/ts}">
      </div>
      <div class="form-item">
        <input type="button" class="report-config-delete-btn btn btn-danger" value="{ts}Delete{/ts}">
      </div>
    {/if}
    <div class="form-item right-align">
      <input id="exportTSV" type="button" class="btn btn-primary" value="{ts}Export TSV{/ts}">
    </div>
    <div class="form-item right-align">
      <input id="exportCSV" type="button" class="btn btn-primary" value="{ts}Export CSV{/ts}">
    </div>
  </form>
</div>
<div id="">
</div>
