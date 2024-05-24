<div class="crm-block crm-form-block crm-webinar-setting-form-block">
  <div class="crm-accordion-wrapper crm-accordion_webinar_setting-accordion crm-accordion-open">
    <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div>
      {ts}API Setting{/ts}      
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      <div class="crm-webinar-information-api-key-block help">
              <h2>{ts}Logmeininc Connected{/ts}</h2>
              <table class="form-layout-compressed">
              <tr class="crm-webinar-setting-client-id-block">
                <td class="label">{$form.gotowebinar_client_id.label}</td>
                <td>{$form.gotowebinar_client_id.html}<br/>
                  <span class="description">{ts}Client ID (https://developer.logmeininc.com/clients){/ts}
                  </span>
                </td>
              </tr>           
              <tr class="crm-webinar-setting-client-secret-block">
                <td class="label">{$form.gotowebinar_client_secret.label}</td>
                <td>{$form.gotowebinar_client_secret.html}<br/>
                  <span class="description">{ts}Client Secret (https://developer.logmeininc.com/clients){/ts}
                  </span>
                </td>
              </tr>
              <tr class="crm-webinar-setting-organizer-key-block">
              <td class="label">{$form.gotowebinar_organizer_key.label}</td>
              <td>{$form.gotowebinar_organizer_key.html}<br/>
                <span class="description">{ts}Organizer Key{/ts}
                </span>
              </td>
            </tr> 
            <tr class="crm-webinar-setting-access-token-block">
              <td class="label">{$form.gotowebinar_access_token.label}</td>
              <td>{$form.gotowebinar_access_token.html}<br/>
                <span class="description">{ts}Access Token{/ts}
                </span>
              </td>
            </tr> 
            <tr class="crm-webinar-setting-refresh-token-block">
              <td class="label">{$form.gotowebinar_refresh_token.label}</td>
              <td>{$form.gotowebinar_refresh_token.html}<br/>
                <span class="description">{ts}Refresh Token{/ts}
                </span>
              </td>
            </tr>
              </table>
       </div>
       
       <div>
         <h2>{ts}Participant Status To Be Considered {/ts}</h2>
        <div class="listing-box" style="height: 120px">
            {foreach from=$form.participant_status_id item="participant_status_val"}
                <div class="{cycle values="odd-row,even-row"}">
                    {$participant_status_val.html}
                </div>
            {/foreach}
        </div>
      </div>
      {if $upcomingWebinars}
    <h2>{ts}Available Webinars{/ts}</h2>
      <table id="gotowebinar_settings">
        <thead >
          <tr>
            <th>{ts}Webinar Key{/ts}</th>
            <th>{ts}Subject{/ts}</th>
            <th>{ts}Description{/ts}</th>
            <th>{ts}Start Time{/ts}</th>
            <th>{ts}End Time{/ts}</th>
         </tr>
         <tbody>
            {foreach from=$upcomingWebinars item=webinar}
              <tr>
              <td>{$webinar.webinarKey}</td>
              
                <td>
                  {$webinar.subject}
                  <p style="color: red;">{$webinar.warning}</p>
                </td>
                <td>{$webinar.description}</td>
                {assign var=times value=$webinar.times}
                <td>{$times[0].startTime|crmDate}</td>
                <td>{$times[0].endTime|crmDate}</td>
              </tr>
            {/foreach}
          </tbody>
        </table>
        {literal}
          <script>
            (function(cj){              
              var webinarSettingsTableSelector = '#gotowebinar_settings';
              cj(document).ready(function() {
                cj(webinarSettingsTableSelector).dataTable();
              });
            })(cj);
          </script>
          {/literal}
      {/if}

      {if $clienterror}
        <table class="form-layout-compressed">
          <tr class="crm-webinar-information-erro-api-key-block">
          <td class="label" style="color:red">{ts} Info:{/ts}</td>
          <td class="label" style="color:red">{ts}{$clienterror}{/ts}</td>
          </tr>
        </table>
      {/if}
      {if $error}
        <table class="form-layout-compressed">
          <tr class="crm-webinar-information-erro-api-key-block">
          <td class="label" style="color:red">{ts} Info:{/ts}</td>
          <td class="label" style="color:red">{ts}{$error}{/ts}</td>
          </tr>
        </table>
      {/if}
    </div>
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl"}
    </div>
  </div>
</div>
