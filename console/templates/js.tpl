{literal}
$(document).on('ready', function() {
	$('#dlgAdd').dialog({
		modal: true,
		autoOpen: false,
		resizable: false,
		minHeight: 150,
		minWidth: 600,
		position: [ 'center' , 100 ],
		buttons: {
			'Crear': function() {
				var parameters = [];
				{/literal}
				{foreach from=$properties item="property" key="column"}
				parameters['{$column|lower}'] = $('#dlgAdd_{$column|lower}').val();
				{/foreach}
				{literal}
				
				f('add',parameters);
			},
			'Cancelar': function() {
				$(this).dialog('close');
			}
		}
	});
	
	$('#dlgEdit').dialog({
		modal: true,
		autoOpen: false,
		resizable: false,
		minHeight: 150,
		minWidth: 600,
		position: [ 'center' , 100 ],
		buttons: {
			'Editar': function() {
				var parameters = [];
				parameters['id'] = $('#dlgEdit_id').val();
				{/literal}
				{foreach from=$properties item="property" key="column"}
				parameters['{$column|lower}'] = $('#dlgEdit_{$column|lower}').val();
				{/foreach}
				{literal}
				
				f('edit',parameters);
			},
			'Cancelar': function() {
				$(this).dialog('close');
			}
		}
	});
	
	$('#dlgDel').dialog({
		modal: true,
		autoOpen: false,
		resizable: false,
		minHeight: 150,
		minWidth: 600,
		position: [ 'center' , 100 ],
		buttons: {
			'Eliminar': function() {
				var parameters = [];
				parameters['id'] = $('#dlgDel_id').val();
				
				f('delete',parameters);
			},
			'Cancelar': function() {
				$(this).dialog('close');
			}
		}
	});
	
	$('#addobject').on('click', function() {
		{/literal}
		{foreach from=$properties item="property" key="column"}
		$('#dlgAdd_{$column|lower}').val('');
		{/foreach}
		{literal}

		$('#dlgAdd').dialog('open');
	});
	
	$('.editobject').on('click', function() {
		$('#dlgEdit_id').val($(this).attr('data-id'));
		{/literal}
		{foreach from=$properties item="property" key="column"}
		$('#dlgEdit_{$column|lower}').val($(this).parent().parent().children('.object{$column|lower}').attr('data-val'));
		{/foreach}
		{literal}
			
		$('#dlgEdit').dialog('open');
	});
	
	$('.delobject').on('click', function() {
		$('#dlgDel_id').val($(this).attr('data-id'));
				
		$('#dlgDel').dialog('open');
	});
});
{/literal}