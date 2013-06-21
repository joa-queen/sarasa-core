<?php

namespace Sarasa\Core;

class Validator {

	/**
	 * Valida una Contraseña para usuario
	 *
	 * @param string $password
	 */
	static public function password($password) {
		if (!ereg("^[a-zA-Z0-9]{4,15}$", $password)) throw new CustomException('The password is wrong.');
	}

	/**
	 * Valida un Email para usuario
	 *
	 * @param string $email
	 */
	static public function email($email) {
		global $db;
		
		if (!ereg("^[_a-zA-Z0-9-]+(.[_a-zA-Z0-9-]+)*@+([_a-zA-Z0-9-]+.)*[a-zA-Z0-9-]{2,200}.[a-zA-Z]{2,6}$", $email )) throw new CustomException('The e-mail address is wrong.');
		if ($db->GetOne('SELECT id FROM users WHERE email = ?',array('email' => $email))) throw new CustomException('The email is already in use.');
	}

	static public function birthday($dia,$mes,$anyo) {
		$birthday = mktime(0,0,0,$mes,$dia,$anyo);

		if ($dia < 1 || $dia > 31 || $mes < 1 || $mes > 12 || $anyo < 1890 || !is_numeric($dia) || !is_numeric($mes) || !is_numeric($anyo)) throw new CustomException('The date is wrong.');
		
		if (($mes == 2 || $mes == 4 || $mes == 6 || $mes == 9 || $mes == 11 ) && ($dia > 30)) throw new CustomException('The date is wrong.');
		if ($mes == 2 && $dia >= 29) {
			if ($dia > 29) throw new CustomException('The date is wrong.');
			elseif ($anyo != -1930) {
				if ((($anyo%4) == 0) && (($anyo%100) != 0) || (($anyo%400) == 0)) return true;
				else throw new CustomException('The date is wrong.');
			}
		}
	}
	
	static public function name($name) {
		if (!$name) throw new CustomException('You must enter a name.');
	}
}

?>
