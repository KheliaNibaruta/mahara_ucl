{include file="header.tpl" headertype="outcomeoverview"}

{if $outcomes}

  <div class="card outcomeoverview">
    <div class="card-body">
      <p id="quota_message">
          {$quotamessage|safe}
      </p>
      <div id="quotawrap" class="progress">
          <div id="quota_fill" class="progress-bar {if $completedactionspercentage < 11}small-progress{/if}" role="progressbar" aria-valuenow="{if $completedactionspercentage }{$completedactionspercentage}{else}0{/if}" aria-valuemin="0" aria-valuemax="100" style="width: {$completedactionspercentage}%;">
              <span>{$completedactionspercentage}%</span>
          </div>
      </div>
    </div>
  </div>

  {foreach $outcomes item=outcome}
    <div class="form-group collapsible-group">
      <fieldset class="pieform-fieldset collapsible">
        <legend>
          <button type="button" data-bs-target="#dropdown{$outcome->id}" data-bs-toggle="collapse" aria-expanded="false" aria-controls="dropdown{$outcome->id}" class="collapsed" >
            {$outcome->short_title|safe}
            <div class="d-flex right float-end">
              {if $actionsallowed}
                {if $outcome->complete}
                  <a href="#" class="outcome-state outcome-complete " data-outcome={$outcome->id} title="{str tag='completeoutcomeaction' section='collection' arg1=$outcome->short_title|safe}" >
                    <span class="icon icon-check-circle completed mt-1 px-4" role="presentation" ></span>
                  </a>
                {else}
                    <a href="#" class="outcome-state outcome-incomplete secondary-link" data-outcome={$outcome->id} title="{str tag='incompleteoutcomeaction' section='collection' arg1=$outcome->short_title|safe}" >
                      <span class="icon-circle action icon-regular mt-1 px-4" data-outcome={$outcome->id}></span>
                    </a>
                {/if}
              {else}
                {if $outcome->complete}
                  <a href="#" class="outcome-state" title="{str tag='completeoutcome' section='collection' arg1=$outcome->short_title|safe}" >
                    <span class="icon icon-check-circle completed mt-1 px-4 disabled "></span>
                  </a>
                {else}
                  <a href="#" class="outcome-state" title="{str tag='incompleteoutcome' section='collection' arg1=$outcome->short_title|safe}" >
                    <span class="icon icon-circle dot icon-regular mt-1 px-4 disabled "></span>
                  </a>
                {/if}
              {/if}
              <span class="icon icon-chevron-down collapse-indicator "> </span>
            </div>
          </button>
        </legend>
        <div class="fieldset-body collapse" id="dropdown{$outcome->id}">

          <div class="form-group form-group-no-border">{$outcome->full_title|safe}</div>

          {if $outcome->outcome_type}
            <div class="form-group form-group-no-border" id="outcome{$outcome->id}_type_container">
              <label for="outcometype-{$outcome->id}">{str tag="outcometype" section="collection"}</label>
              <div id="outcometype-{$outcome->id}" class="outcome-type">
                <span class="badge rounded-pill text-bg-{$outcometypes[$outcome->outcome_type]->styleclass}">{$outcometypes[$outcome->outcome_type]->abbreviation}</span>
              </div>
              {contextualhelp
              plugintype='core'
              pluginname='collection'
              form="outcome$outcome->id"
              element='type'
              page='type'}
            </div>
          {/if}

          <br/>** Table goes here ** <br/><br/>

          <button class="btn btn-secondary btn-sm" >
            <span class="icon icon-plus left" role="presentation" aria-hidden="true"> </span>
            {str tag="addactivity" section="collection"}
          </button>

          {$supportform[$outcome->id]|safe}

          <form class="outcome-progress-form" id="progress{$outcome->id}">
            <div class="form-group form-group-no-border">
              <label class="pseudolabel" for="progress{$outcome->id}_textarea">{str tag="progress" section="collection"}</label>
              <div class="textarea-section">
              {if $outcome->complete || !$actionsallowed}
                <div class="text progress-detail">
                  {$outcome->progress|safe}
                </div>
                {if $outcome->lastauthorprogress}
                  <div class="text-small postedon"><a href="{profile_url($outcome->lastauthorprogress)}" class="progress-author">
                    {display_name($outcome->lastauthorprogress, null, true)}
                    </a>{str tag='ondate' section='collection' arg1=$outcome->lasteditprogress|strtotime|format_date:'strftimedatetime'}
                  </div>
                {/if}
              {else}
                <div>
                  <textarea id="progress{$outcome->id}_textarea" class="form-control resizable" tabindex="0" cols="180" rows="3" maxlength="255">{$outcome->progress|safe}</textarea>
                </div>
                <button type="submit" id="progress{$outcome->id}_save" name="save" tabindex="0" class="btn btn-primary btn-sm outcome-progress-save">{str tag='save'}</button>
              {/if}
              </div>
            </div>
            <input type="hidden" class="hidden autofocus" id="progressid_{$outcome->id}_id" name="id" value="{$outcome->id}">
          </form>
        </div>
      </fieldset>
    </div>
  {/foreach}
{else}
  {str tag="nooutcomesmessage" section="collection" }
{/if}
{* complete outcome modal form *}
<div tabindex="0" class="modal fade" id="complete-confirm-form">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{str tag=Close}"><span aria-hidden="true">&times;</span></button>
                <h1 class="modal-title">
                    {str tag=markcomplete section=collection}
                </h1>
            </div>
            <div class="modal-body">
                <div class="btn-group">
                    <button id="complete-yes-button" type="button" class="btn btn-secondary">{str tag="yes"}</button>
                    <button id="complete-back-button" type="button" class="btn btn-secondary">{str tag="no"}</button>
                </div>
            </div>
        </div>
    </div>
</div>

{* incomplete outcome modal form *}
<div tabindex="0" class="modal fade" id="incomplete-confirm-form">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{str tag=Close}"><span aria-hidden="true">&times;</span></button>
                <h1 class="modal-title">
                    {str tag=markincomplete section=collection}
                </h1>
            </div>
            <div class="modal-body">
                <div class="btn-group">
                    <button id="incomplete-yes-button" type="button" class="btn btn-secondary">{str tag="yes"}</button>
                    <button id="incomplete-back-button" type="button" class="btn btn-secondary">{str tag="no"}</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="application/javascript">
$(function() {

  if ({$actionsallowed}) {

    // Set complete
    $("a.outcome-incomplete").on('click', function(e) {
      e.stopPropagation();
      e.preventDefault();
      $("#complete-confirm-form").modal('show');
      $("#complete-confirm-form").attr('outcomeid', $(this).attr('data-outcome'));
    });

    // click 'No' button on modals
    $("#complete-back-button").on('click', function() {
        $("#complete-confirm-form").modal('hide');
    });

    $('#complete-yes-button').on('click', function(event) {
        event.preventDefault();
        event.stopPropagation();
        var outcomeid = $("#complete-confirm-form").attr('outcomeid');
        sendjsonrequest('{$WWWROOT}collection/updateoutcome.json.php', { 'update_type': 'markcomplete', 'outcomeid': outcomeid, 'collectionid': {$collection} }, 'POST', function (data) {
          if (data) {
            location.reload();
          }
        }, function(error) {
          console.log(error);
        });
    });

    // Set incomplete
    $("a.outcome-complete").on('click', function(e) {
      e.stopPropagation();
      e.preventDefault();
      $("#incomplete-confirm-form").modal('show');
      $("#incomplete-confirm-form").attr('outcomeid', $(this).attr('data-outcome'));
    });

    // click 'No' button on modals
    $("#incomplete-back-button").on('click', function() {
        $("#incomplete-confirm-form").modal('hide');
    });

    $('#incomplete-yes-button').on('click', function(event) {
        event.preventDefault();
        event.stopPropagation();
        var outcomeid = $("#incomplete-confirm-form").attr('outcomeid');
        sendjsonrequest('{$WWWROOT}collection/updateoutcome.json.php', { 'update_type': 'markincomplete', 'outcomeid': outcomeid, 'collectionid': {$collection} }, 'POST', function (data) {
          if (data) {
            location.reload();
          }
        }, function(error) {
          console.log(error);
        });
    });
  }

  $('form.supportform input.switchbox').on('change', function(e) {
    const id = $(e.target).parents('form').find('[name=id]').val();
    const support = $(e.target).prop('checked');
    const data = {
      'update_type': 'support',
      'outcomeid': id,
      'collectionid': {$collection},
      'support': support
    };
    sendjsonrequest(config.wwwroot + 'collection/updateoutcome.json.php', data, 'POST', function(data) {
      console.log(data);
    },
    function(error) {
      console.log(error);
    })
  });

  $(".outcome-progress-save").on('click', function(e) {
    e.preventDefault();
    const form = $(e.target).parents('.outcome-progress-form');
    const id = $(form).find('input[name="id"]').val();
    const text = $(form).find('textarea').val();
    if (text.length <= 255) {
      const data = {
        'update_type': 'progress',
        'outcomeid': id,
        'collectionid': {$collection},
        'progress': text
      };
      sendjsonrequest('{$WWWROOT}collection/updateoutcome.json.php', data, 'POST', function (data) {
            const formid = $(form).attr('id');
            formchangemanager.add(formid);
          }, function(error) {
            console.log(error);
      });
    }
  })

  $('form.outcome-progress-form').map((i, form) => {
    const formid = $(form).attr('id');
    formchangemanager.add(formid);
  })
});
</script>
{include file="footer.tpl"}