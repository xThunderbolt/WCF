{include file='header' pageTitle='wcf.acp.bbcode.mediaProvider.list'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}wcf.acp.bbcode.mediaProvider.list{/lang}{if $items} <span class="badge badgeInverse">{#$items}</span>{/if}</h1>
	</div>
	
	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='BBCodeMediaProviderAdd'}{/link}" class="button">{icon name='plus'} <span>{lang}wcf.acp.bbcode.mediaProvider.add{/lang}</span></a></li>
			
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{hascontent}
	<div class="paginationTop">
		{content}{pages print=true assign=pagesLinks controller="BBCodeMediaProviderList" link="pageNo=%d&sortField=$sortField&sortOrder=$sortOrder"}{/content}
	</div>
{/hascontent}

{if $objects|count}
	<div class="section tabularBox">
		<table class="table jsObjectActionContainer" data-object-action-class-name="wcf\data\bbcode\media\provider\BBCodeMediaProviderAction">
			<thead>
				<tr>
					<th class="columnID columnMediaProviderID{if $sortField == 'providerID'} active {@$sortOrder}{/if}" colspan="2"><a href="{link controller='BBCodeMediaProviderList'}pageNo={@$pageNo}&sortField=providerID&sortOrder={if $sortField == 'providerID' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{/link}">{lang}wcf.global.objectID{/lang}</a></th>
					<th class="columnTitle columnMediaProviderTitle{if $sortField == 'title'} active {@$sortOrder}{/if}"><a href="{link controller='BBCodeMediaProviderList'}pageNo={@$pageNo}&sortField=title&sortOrder={if $sortField == 'title' && $sortOrder == 'ASC'}DESC{else}ASC{/if}{/link}">{lang}wcf.acp.bbcode.mediaProvider.title{/lang}</a></th>
					
					{event name='columnHeads'}
				</tr>
			</thead>
			
			<tbody class="jsReloadPageWhenEmpty">
				{foreach from=$objects item='mediaProvider'}
					<tr class="jsMediaProviderRow jsObjectActionObject" data-object-id="{@$mediaProvider->getObjectID()}">
						<td class="columnIcon">
							{objectAction action="toggle" isDisabled=$mediaProvider->isDisabled}
							<a href="{link controller='BBCodeMediaProviderEdit' object=$mediaProvider}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
							{objectAction action="delete" objectTitle=$mediaProvider->getTitle()}
							
							{event name='rowButtons'}
						</td>
						<td class="columnID">{@$mediaProvider->providerID}</td>
						<td class="columnTitle columnMediaProviderTitle"><a href="{link controller='BBCodeMediaProviderEdit' object=$mediaProvider}{/link}">{$mediaProvider->title}</a></td>
						
						{event name='columns'}
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
	
	<footer class="contentFooter">
		{hascontent}
			<div class="paginationBottom">
				{content}{@$pagesLinks}{/content}
			</div>
		{/hascontent}
		
		<nav class="contentFooterNavigation">
			<ul>
				<li><a href="{link controller='BBCodeMediaProviderAdd'}{/link}" class="button">{icon name='plus'} <span>{lang}wcf.acp.bbcode.mediaProvider.add{/lang}</span></a></li>
				
				{event name='contentFooterNavigation'}
			</ul>
		</nav>
	</footer>
{else}
	<p class="info">{lang}wcf.global.noItems{/lang}</p>
{/if}

{include file='footer'}
