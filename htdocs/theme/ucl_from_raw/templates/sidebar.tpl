 {foreach from=$blocks item=sideblock}{strip}
    {counter name="sidebar" assign=sequence}
    {/strip}<div{if $sideblock.id} id="{$sideblock.id}"{/if} class="sideblock-{$sequence} {if $sideblock.class}{$sideblock.class}{/if}">
    {if $sideblock.template}
    {include file=$sideblock.template sbdata=$sideblock.data}
    {else}
    {include file="sideblocks/generic.tpl" sbdata=$sideblock}
    {/if}
</div>
{/foreach} 

<ul id="nav" class="nav navbar-nav">
        {strip}
            {foreach from=$MAINNAV item=item name=menu}
            <li class="{if $item.path}{$item.path}{else}dashboard{/if}{if $item.selected} active{/if}">
                {if !$item.submenu}{* Create a link to the main page *}
                <a href="{$WWWROOT}{$item.url}" class="{if $item.path}{$item.path}{else}dashboard{/if}">
                {else}{* Otherwise, create list items as buttons to expand submenus *}
                <button class="{if $item.path}{$item.path}{else}dashboard{/if} menu-dropdown-toggle navbar-toggle{if !$item.selected} collapsed{/if}" data-bs-toggle="collapse" data-bs-parent="#nav" data-bs-target="#childmenu-{$dwoo.foreach.menu.index}" aria-expanded="false">
                {/if}
                {if $item.iconclass}
                    <span class="icon icon-{$item.iconclass}" role="presentation" aria-hidden="true"></span>
                {/if}
                {if $item.accessibletitle}
                    <span role="presentation" aria-hidden="true">
                {/if}
                {$item.title}
                {if $item.accessibletitle}
                    </span>
                    <span class="accessible-hidden visually-hidden">
                        ({$item.accessibletitle})
                    </span>
                {/if}
                {if !$item.submenu}{* Close the link tag *}
                </a>
                {else}{* Close the button tag *}
                    <span class="icon icon-chevron-down navbar-showchildren" role="presentation" aria-hidden="true"></span>
                </button>
                {/if}
                {if $item.submenu}
                <ul id="childmenu-{$dwoo.foreach.menu.index}" class="{if $item.selected} show{/if} collapse child-nav">
                {strip}
                    {foreach from=$item.submenu item=subitem}
                    <li class="{if $subitem.selected}active {/if}{if $subitem.submenu}has-sub {/if}">
                        <a href="{$WWWROOT}{$subitem.url}">
                            {$subitem.title}
                        </a>
                        {if $subitem.submenu}
                        <ul class="dropdown-tertiary">
                            {foreach from=$subitem.submenu item=tertiaryitem}
                            <li{if $tertiaryitem.selected} class="selected"{/if}>
                                <a href="{$WWWROOT}{$tertiaryitem.url}">
                                    {$tertiaryitem.title}
                                </a>
                            </li>
                            {/foreach}
                        </ul>
                        {/if}
                    </li>
                    {/foreach}
                {/strip}
                </ul>
                {/if}
            </li>
            {/foreach}
        {/strip}
    </ul>

