{literal}{extends file="layout.tpl"}

{block name=container}

<button id="addobject" class="greenbutton">Crear {/literal}{$model|lower}{literal}</button>
<h1>{/literal}{$model}{literal}s</h1>

<table>
	<thead>
		<tr>
			{/literal}
			{foreach from=$properties item="property" key="column"}
			<th>{$column|ucfirst}</th>
			{/foreach}
			{literal}
			<th>Opciones</th>
		</tr>
	</thead>
	<tbody id="objectcontainer">
	{foreach from=$objects item="object"}
	{include file="{/literal}{$module}/_{$action}_{$model|lower}{literal}.tpl"}
	{/foreach}
	</tbody>
</table>



<div class="dialogscontainer">

	<div id="dlgAdd" title="Add {/literal}{$model}{literal}">
	
		{/literal}
		{foreach from=$properties item="property" key="column"}
		<label for="dlgAdd_{$column|lower}">{$column|ucfirst}:</label><br />
		<input type="text" id="dlgAdd_{$column|lower}" />
		
		<br /><br />
		
		{/foreach}
		{literal}
	
	</div>

	<div id="dlgEdit" title="Edit {/literal}{$model}{literal}">
		<input type="hidden" id="dlgEdit_id" />
		
		{/literal}
		{foreach from=$properties item="property" key="column"}
		<label for="dlgEdit_{$column|lower}">{$column|ucfirst}:</label><br />
		<input type="text" id="dlgEdit_{$column|lower}" />
		
		<br /><br />
		
		{/foreach}
		{literal}
	
	</div>
	
	<div id="dlgDel" title="Delete {/literal}{$model}{literal}">
		<input type="hidden" id="dlgDel_id" />
		¿Está seguro que desea eliminar este {/literal}{$model|lower}{literal}?
	</div>

</div>

{/block}{/literal}