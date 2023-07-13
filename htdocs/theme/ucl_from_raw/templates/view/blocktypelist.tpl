{if $blocktypes}
    {if $javascript}
    <div class='btn-group-vertical'>
    {/if}
        {foreach from=$blocktypes item=blocktype}{strip}
            <div class="{if !$accessible} not-accessible{/if} blocktype-drag grid-stack-item hide-title-collapsed"
                href="#" title="{$blocktype.title}" gs-w="{$GS_DRAG_WIDTH}" gs-h="{$GS_DRAG_HEIGHT}" gs-max-w="{$GS_DRAG_WIDTH}">
              <div class="grid-stack-item-content btn btn-primary" tabindex="0">
                <span class="icon icon-{$blocktype.cssicon} {$blocktype.cssicontype} icon-lg" title="{$blocktype.title}" role="presentation" aria-hidden="true"></span>
                <span class="labelspan hidden">{$blocktype.title}</span>
                {if $blocktype.name != 'placeholder'}
                    <span class="visually-hidden">({$blocktype.description})</span>
                {/if}
              </div>
            </div>{/strip}
        {/foreach}
    {if $javascript}
    </div>
    {/if}
{/if}
