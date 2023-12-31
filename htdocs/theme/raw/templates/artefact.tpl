{if $tags}
<p class="tags">
    {str tag=tags}: {list_tags owner=$owner tags=$tags view=$view}
</p>
{/if}

{if $modal && !$artefacttype == 'html'}
<p>{$description|clean_html|safe}</p>
{/if}

{if isset($attachments)}
<div class="has-attachment card collapsible">
    <div class="card-header">
        <a href="#artefact-attach" class="text-start collapsed" aria-expanded="false" data-bs-toggle="collapse">
            <span class="icon icon-paperclip left" role="presentation" aria-hidden="true"></span>
            <span class="text-small">{str tag=attachedfiles section=artefact.blog}</span>
            <span class="metadata">({$attachments|count})</span>
            <span class="icon icon-chevron-down float-end collapse-indicator" role="presentation" aria-hidden="true"></span>
        </a>
    </div>
    <!-- Attachment list with view and download link -->
    <div id="artefact-attach" class="collapse">
        <ul class="list-unstyled list-group">
            {foreach from=$attachments item=item}
            <li class="list-group-item">
                <a class="modal_link file-icon-link" data-bs-toggle="modal-docked" data-bs-target="#configureblock" href="#" data-blockid="{$blockid}" data-artefactid="{$item->id}">
                {if $item->icon}
                    <img class="file-icon" src="{$item->iconpath}" alt="">
                {else}
                    <span class="icon icon-{$item->artefacttype} icon-lg text-default left file-icon" role="presentation" aria-hidden="true"></span>
                {/if}
                </a>
                <span class="title">
                    <a class="modal_link" data-bs-toggle="modal-docked" data-bs-target="#configureblock" href="#" data-blockid="{$blockid}" data-artefactid="{$item->id}">
                        <span class="text-small">{$item->title}</span>
                    </a>
                </span>
                <a href="{$item->downloadpath}" class="download-link">
                    <span class="visually-hidden">{str tag=downloadfilesize section=artefact.file arg1=$item->title arg2=$item->size|display_size}</span>
                    <span class="icon icon-download icon-lg float-end text-watermark icon-action" role="presentation" aria-hidden="true" data-bs-toggle="tooltip" title="{str tag=downloadfilesize section=artefact.file arg1=$item->title arg2=$item->size|display_size}"></span>
                </a>
            </li>
            {/foreach}
        </ul>
    </div>
</div>
{/if}

{if $license}
    <div class="license">
        {$license|safe}
    </div>
{/if}
