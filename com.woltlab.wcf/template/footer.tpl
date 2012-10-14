			{if $skipBreadcrumbs|empty}{include file='breadcrumbs' __microdata=false}{/if}
			
		</section>
	</div>
</div>

<footer id="pageFooter" class="layoutFluid footer">
	<nav id="footerNavigation" class="navigation navigationFooter clearfix">
		{include file='footerMenu'}
		
		<ul class="navigationIcons">
			<li id="toTopLink" class="toTopLink"><a href="{$__wcf->getAnchor('top')}" title="{lang}wcf.global.scrollUp{/lang}" class="jsTooltip"><img src="{icon}circleArrowUpColored{/icon}" alt="" class="icon16" /> <span class="invisible">{lang}wcf.global.scrollUp{/lang}</span></a></li>
			{if SHOW_CLOCK}
				<li class="separator" title="{lang}wcf.date.timezone.{@'/'|str_replace:'.':$__wcf->getUser()->getTimeZone()->getName()|strtolower}{/lang}"><p><img src="{icon}clockColored{/icon}" alt="" class="icon16" /> <span>{@TIME_NOW|plainTime}</span></p></li>
			{/if}
		</ul>
	</nav>
	
	<div class="footerContent">
		{if ENABLE_BENCHMARK}{include file='benchmark'}{/if}
	
		{event name='copyright'}
	</div>
</footer>

<a id="bottom"></a>
