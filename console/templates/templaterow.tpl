		<tr id="{$model|lower}_{literal}{$object->getId()}"{if $nodisplay} style="display: none;"{/if}{/literal}>
			{foreach from=$properties item="property" key="column"}
			<td class="object{$column|lower}" data-val="{literal}{$object->get{/literal}{$column|ucfirst}{literal}()}">{$object->get{/literal}{$column|ucfirst}{literal}()}</td>{/literal}
			{/foreach}
			<td>
				{literal}
				<img src="/images/icons16x16/edit.png" class="editobject" data-id="{$object->getId()}" />
				<img src="/images/icons16x16/delete.png" class="delobject" data-id="{$object->getId()}" />
				{/literal}
			</td>
		</tr>