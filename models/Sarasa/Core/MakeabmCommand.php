<?php

namespace Sarasa\Core;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

class MakeabmCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('makeabm')
            ->setDescription('Crea un ABM básico a partir de un modelo')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	try {
			system('clear');
		
			$dialog = $this->getHelperSet()->get('dialog');
			$formatter = $this->getHelperSet()->get('formatter');
		
			$errorMessages = array('Sarasa Framework', 'MAQUETADOR BÁSICO DE ABM');
			$formattedBlock = $formatter->formatBlock($errorMessages, 'question', true);
			$output->writeln($formattedBlock);
			$output->writeln("\n");
			
			$modelo = $dialog->ask(
					$output,
					'<comment>Ingrese el nombre del modelo: </comment>',
					'0'
			);
			
			if (file_exists('models/' . $modelo . '.php')) {
				$output->writeln("\n");
				$bundle = $dialog->ask(
						$output,
						'<comment>Ingrese el nombre del módulo (bundle): </comment>',
						'0'
				);
				$bundle = strtolower($bundle);
				if (!is_dir('controllers/' . $bundle)) {
					$output->writeln("\n");
					$bundlecreate = $dialog->askConfirmation(
							$output,
							'<comment>El bundle no existe. ¿Desea crearlo? (yes/no): </comment>',
							true
					);
					if ($bundlecreate) {
						mkdir('controllers/' . $bundle);
						mkdir('templates/default/' . $bundle);
						mkdir('public_html/templates/default/' . $bundle);
						mkdir('public_html/templates/default/' . $bundle . '/js');
						mkdir('public_html/templates/default/' . $bundle . '/css');
					}
					else {
						$output->writeln("\n");
						die();
					}
				}
				
				$output->writeln("\n");
				$accion = $dialog->ask(
						$output,
						'<comment>Elija un nombre para la acción: </comment>',
						'0'
				);
				$accion = strtolower($accion);
					
				if (file_exists('controllers/' . $bundle . '/' . $accion . '.php')) throw new CustomException('El archivo del controlador ya ha sido creado');
				if (file_exists('templates/default/' . $bundle . '/' . $accion . '.tpl')) throw new CustomException('El archivo del template ya ha sido creado');
				if (file_exists('public_html/templates/default/' . $bundle . '/css/' . $accion . '.css')) throw new CustomException('El archivo de la hoja de estilo ya ha sido creado');
				if (file_exists('public_html/templates/default/' . $bundle . '/js/' . $accion . '.js')) throw new CustomException('El archivo del javascript ya ha sido creado');
				
				AnnotationRegistry::registerFile("vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php");
				AnnotationReader::addGlobalIgnoredName('GeneratedValue');
				
				$reader = new AnnotationReader();
				$refClass = new ReflectionClass('Product');
				
				$template = new Smarty();
				$template->setTemplateDir('vendor/sarasa/core/console/templates');
				$template->setCompileDir('vendor/sarasa/core/console/templates_c');
				$template
					->assign('model', $modelo)
					->assign('module', $bundle)
					->assign('action', $accion)
				;
				$properties = array();
				foreach ($refClass->getProperties() as $property) {
					if ($reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Id')) continue;
					$properties[$property->name] = $reader->getPropertyAnnotation($property, 'Doctrine\ORM\Mapping\Column');
				}
				$template->assign('properties', $properties);
				$content = $template->fetch('controller.tpl');
				file_put_contents('controllers/' . $bundle . '/' . $accion . '.php', $content);
				
				$content = $template->fetch('template.tpl');
				file_put_contents('templates/default/' . $bundle . '/' . $accion . '.tpl', $content);
				
				$content = $template->fetch('templaterow.tpl');
				file_put_contents('templates/default/' . $bundle . '/_' . $accion . '_' .  strtolower($modelo) . '.tpl', $content);
				
				$content = $template->fetch('js.tpl');
				file_put_contents('public_html/templates/default/' . $bundle . '/js/' . $accion . '.js', $content);
			}
			else throw new CustomException('No se encontró el modelo ingresado');
    	} catch (Exception $e) {
    		$output->writeln("\n");
    		$errorMessages = array('ERROR!', $e->getMessage());
    		$formattedBlock = $formatter->formatBlock($errorMessages, 'error', true);
    		$output->writeln($formattedBlock);
    		$output->writeln("\n");
    	}
		
    }
}
