<?php

namespace Sarasa\Core;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;

class SarasaCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('sarasa')
            ->setDescription('Lista de comandos')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		system('clear');
	
		$dialog = $this->getHelperSet()->get('dialog');
		$formatter = $this->getHelperSet()->get('formatter');
	
		$errorMessages = array('Sarasa Framework', 'ELIJA UNA OPCIÓN A EJECUTAR');
		$formattedBlock = $formatter->formatBlock($errorMessages, 'question', true);
		$output->writeln($formattedBlock);
		$output->writeln("\n");
		
		$output->writeln("<info>1:</info> Crear ABM a partir de un modelo");
		$output->writeln("<info>0:</info> Salir");
		
		$output->writeln("\n");
		
		$opcion = $dialog->ask(
				$output,
				'<comment>Elija una opción: </comment>',
				'0'
		);
		
		switch ($opcion) {
			case '1':
				$command = $this->getApplication()->find('makeabm');

				$arguments = array(
				);

				$input = new ArrayInput($arguments);
				$returnCode = $command->run($input, $output);
				
				break;
			default:
		}
		
		$output->writeln("\n");
    }
}
