<?php

class Controller extends MainController {
	
	public function index() {
		$objectRepository = $this->getEntityManager()->getRepository('{$model}');
		$objects = $objectRepository->findAll();
		
		$template = new Template();
		$template->title('{$model}');
		$template->assign('objects', $objects);
		$template->display('{$module}/{$action}.tpl');
	}
	
	public function add(&$objResponse, $parameters) {
		$objResponse->script('$("#dlgAdd").dialog("close");');
		
		$em = $this->getEntityManager();
		${$model|lower} = new {$model}();
		{foreach from=$properties item="property" key="column"}
		${$model|lower}->set{$column|ucfirst}($parameters['{$column|lower}']);
		{/foreach}
		
		$em->persist(${$model|lower});
		$em->flush();
		
		$portion = new Template();
		$portion->assign('object', ${$model|lower});
		$portion->assign('nodisplay', true);
		$row = $portion->fetch('{$module}/_{$action}_{$model|lower}.tpl');
		
		$objResponse->append('objectcontainer', $row);
		$objResponse->script('$("#{$model|lower}_' . ${$model|lower}->getId() . '").fadeIn();');
	}
	
	public function edit(&$objResponse, $parameters) {
		$objResponse->script('$("#dlgEdit").dialog("close");');
		
		$em = $this->getEntityManager();
		${$model|lower} = $em->getRepository('{$model}')->findOneBy(array('id' => $parameters['id']));
		if (!${$model|lower}) throw new CustomException('No se encontró el {$model|lower}');
		
		{foreach from=$properties item="property" key="column"}
		${$model|lower}->set{$column|ucfirst}($parameters['{$column|lower}']);
		{/foreach}
		
		$em->persist(${$model|lower});
		$em->flush();
		
		{foreach from=$properties item="property" key="column"}
		$objResponse->script('$("#{$model|lower}_' . ${$model|lower}->getId() . ' .object{$column|lower}").html("' . ${$model|lower}->get{$column|ucfirst}() . '");');
		$objResponse->script('$("#{$model|lower}_' . ${$model|lower}->getId() . ' .objectn{$column|lower}").attr("data-val","' . ${$model|lower}->get{$column|ucfirst}() . '");');
		{/foreach}
		$objResponse->script('$("#{$model|lower}_' . ${$model|lower}->getId() . '").effect("highlight", 700);');
	}
	
	public function delete(&$objResponse, $parameters) {
		$objResponse->script('$("#dlgDel").dialog("close");');
		
		$em = $this->getEntityManager();
		${$model|lower} = $em->getRepository('{$model}')->findOneBy(array('id' => $parameters['id']));
		if (!${$model|lower}) throw new CustomException('No se encontró el {$model|lower}');
		
		$em->remove(${$model|lower});
		$em->flush();
		
		$objResponse->script('$("#{$model|lower}_' . $parameters['id'] . '").fadeOut(400, function() { $(this).remove(); });');
	}
	
}
