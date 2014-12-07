<?php

namespace Sarasa\Core;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class FrontController
{
    public static $key = '';
    public static $bundle = '';
    public static $controller = '';
    public static $action = '';
    public static $parameters = array();
    public static $mtime;
    public static $debugpath;
    public static $method;
    public $parenthash = null;

    private static $config;
    private static $em;
    private static $debug;
    private static $doctrinestack;

    public function __construct()
    {
        //set_error_handler(array('\Sarasa\Core\FrontController','errorHandler'));

        if (!self::config('production')) {
            self::$debugpath = bin2hex(openssl_random_pseudo_bytes(1)) . '/' . bin2hex(openssl_random_pseudo_bytes(1));
            self::$mtime = (int) (microtime(true) * 1000);
        }

        extract($_SERVER);
        setlocale(LC_ALL, "es_ES.UTF8");
        date_default_timezone_set("America/Argentina/Buenos_Aires");

        if (!isset($_SESSION['lang']) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            $_SESSION['lang'] = $lang;
        }

        if (self::config('production')) {
            ob_start(array('\Sarasa\Models\Template', "sanitizeOutput"));
        }

        if (self::config('maintenance')) {
            throw new CustomException('El sitio se encuentra en mantenimento', 500);
        }

        self::createEntityManager();
    }

    public function __destruct()
    {
        if (!self::config('production') && (!isset($_SERVER['HTTP_AJAX_FUNCTION']) || $_SERVER['HTTP_AJAX_FUNCTION'] != 'debugbar')) {
            $this->debug('_server', $_SERVER);
            $this->debug('time', (((int) (microtime(true) * 1000)) - self::$mtime));
            $this->debug('memory', number_format((memory_get_peak_usage() / 1024 / 1024), 2));
            $this->debug('warnings', array());
            $this->debug('key', self::$key);
            $this->debug('bundle', self::$bundle);
            $this->debug('controller', self::$controller);
            $this->debug('action', self::$action);
            $this->debug('queries', self::$doctrinestack->queries);
            $this->debug('querycount', count(self::$doctrinestack->queries));

            $qt = 0;
            foreach (self::$doctrinestack->queries as $q) {
                $qt += $q['executionMS'];
            }

            $this->debug('querytime', number_format($qt, 3));
            $this->debug('parenthash', $this->parenthash);

            $dir = $_SERVER['DOCUMENT_ROOT'] . '/../logs/dev/' . self::$debugpath;
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            $file = $dir . '/' . self::$mtime . '.json';
            file_put_contents($file, json_encode(self::$debug));
        }
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline, array $errcontext)
    {
        $message = 'Error of level ';
        switch ($errno) {
            case E_USER_ERROR:
                $message .= 'E_USER_ERROR';
                break;
            case E_USER_WARNING:
                $message .= 'E_USER_WARNING';
                break;
            case E_USER_NOTICE:
                $message .= 'E_USER_NOTICE';
                break;
            case E_STRICT:
                $message .= 'E_STRICT';
                break;
            case E_RECOVERABLE_ERROR:
                $message .= 'E_RECOVERABLE_ERROR';
                break;
            case E_DEPRECATED:
                $message .= 'E_DEPRECATED';
                break;
            case E_USER_DEPRECATED:
                $message .= 'E_USER_DEPRECATED';
                break;
            case E_NOTICE:
                $message .= 'E_NOTICE';
                break;
            case E_WARNING:
                $message .= 'E_WARNING';
                break;
            default:
                $message .= sprintf('Unknown error level, code of %d passed', $errno);
        }
        $message .= sprintf(
            '. Error message was "%s" in file %s at line %d.',
            $errstr,
            $errfile,
            $errline
        );
        self::debug('warnings', array(array('message' => $message, 'context' => $errcontext)));
    }

    final public static function debug($key, $value)
    {
        if (!isset(self::$debug)) {
            self::$debug = array();
        }

        if (isset(self::$debug[$key]) && is_array(self::$debug[$key]) && is_array($value)) {
            self::$debug[$key] = array_merge(self::$debug[$key], $value);
        } else {
            self::$debug[$key] = $value;
        }
    }

    final private static function createEntityManager()
    {
        $paths = array(__DIR__ . '/../../../../../../app');
        $isDevMode = self::config('production') ? false : true;
        $proxyDir = __DIR__ . '/../../../../compile/proxies';

        $dbParams = array(
                'driver'   => 'pdo_mysql',
                'host'     => self::config('dbhost'),
                'user'     => self::config('dbuser'),
                'password' => self::config('dbpass'),
                'dbname'   => self::config('dbname'),
        );

        $config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode, $proxyDir);
        self::$em = EntityManager::create($dbParams, $config);
        if (!self::config('production')) {
            self::$doctrinestack = new \Doctrine\DBAL\Logging\DebugStack();
            self::$em->getConfiguration()->setSQLLogger(self::$doctrinestack);
        }
    }

    final protected function getEntityManager()
    {
        return self::entityManager();
    }

    final public static function entityManager()
    {
        if (!isset(self::$em)) {
            self::createEntityManager();
        }

        return self::$em;
    }

    final public static function config($value)
    {
        if (!isset(self::$config)) {
            $string = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/../config.json");
            self::$config = json_decode($string, true);
        }

        return (isset(self::$config[$value]) ? self::$config[$value] : '');
    }

    /**
     * @author Joaquín Miguez
     */
    final public static function route()
    {
        /** Chequeos previos **/
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['AJAX_FUNCTION'])) {
                $_SERVER['HTTP_AJAX_FUNCTION'] = $headers['AJAX_FUNCTION'];
            }
            if (isset($headers['AJAX_URL'])) {
                $_SERVER['HTTP_AJAX_URL'] = $headers['AJAX_URL'];
            }
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_REAL_IP'];
        }
        /** **/

        $security = self::config('security');

        if (isset($security['allowedips']) && count($security['allowedips'])) {
            $allowed = false;
            foreach ($security['allowedips'] as $ip) {
                if ($ip == $_SERVER['REMOTE_ADDR']) {
                    $allowed = true;
                    break;
                }
            }

            if (!$allowed) {
                header('HTTP/1.1 403 Forbidden');
                die();
            }
        }
        /** **/

        $aux = substr($_SERVER['REQUEST_URI'], strlen(self::config('preurl')));
        while (substr($aux, -1) == '/') {
            $aux = substr($aux, 0, -1);
        }

        /** Looking for redirects **/
        $file = $_SERVER['DOCUMENT_ROOT'] . "/../redirects.json";

        if (is_file($file)) {
            $string = file_get_contents($file);
            $redirects = json_decode($string, true);

            if (is_array($redirects)) {
                foreach ($redirects as $redirect) {
                    $redirectfail = false;
                    
                    $origin = explode('/', $redirect['origin']);
                    $destination = explode('/', $redirect['destination']);
                    $auxparts = explode('/', $aux);
                    $search = array();
                    $replace = array();

                    foreach ($origin as $key => $value) {
                        if (substr($value, 0, 1) == ':') {
                            foreach ($destination as $dk => $dv) {
                                if ($dv == $value) {
                                    $search[] = $value;
                                    $replace[] = $auxparts[$key];

                                    break;
                                }
                            }
                        } elseif ($value != $auxparts[$key]) {
                            $redirectfail = true;
                            break;
                        }
                    }

                    if (!$redirectfail && $aux == str_replace($search, $replace, $redirect['origin'])) {
                        $url = self::config('preurl') . str_replace($search, $replace, $redirect['destination']);
                        header("HTTP/1.1 301 Moved Permanently");
                        header('Location: ' . $url);
                        die();
                    }
                }
            }
        }
        /** **/

        /** Pasando variables via GET tradicional a la variable $_VARIABLES **/
        $auxget = explode('?', $aux);
        $aux = $auxget[0];
        if (isset($auxget[1])) {
            $auxvars = explode('&', $auxget[1]);
            foreach ($auxvars as $auxvar) {
                $auxvartemp = explode('=', $auxvar);
                if (isset($auxvartemp[1])) {
                    $_VARIABLES[$auxvartemp[0]] = $auxvartemp[1];
                }
            }
        }
        /** Fin del pasaje GET -> $_VARIABLES **/

        //Separando por el separador #
        $variables = explode('#', $aux);
        $seccion = (isset($variables[1]) ? $variables[1] : '');
        $variables = $variables[0];

        //Limpio todo lo que haya después de '?'
        $variables = explode('?', $variables);
        $variables = $variables[0];

        $variables = explode('/', $aux);
        if ($variables[0] == 'index.php') {
            $variables = array_slice($variables, 1);
        }

        $extension = '.php';

        $file = $_SERVER['DOCUMENT_ROOT'] . "/../routing.json";
        if (!is_file($file)) {
            throw new CustomException('No se encontró el archivo de ruteo');
        }

        $string = file_get_contents($file);
        $routing_var = json_decode($string, true);

        if (!is_array($routing_var)) {
            throw new CustomException('El archivo de ruteo está corrupto o vacío');
        }

        //Mapeando con variable de configuarción $routing
        $keys = array_keys($routing_var);
        while ($ruta = array_shift($routing_var)) {
            $key = array_shift($keys);

            if (isset($ruta['controller'])) {
                $ruta['controller'] = $ruta['controller'] . 'Controller'; //Adaptando a la convención
            }

            $j = 0;
            $map = true;
            $ruta_method = isset($ruta['method']) ? $ruta['method'] : 'get';

            if (!isset($_SERVER['HTTP_AJAX_FUNCTION']) && isset($ruta['url']) &&  strtolower($ruta_method) != strtolower($_SERVER['REQUEST_METHOD'])) {
                continue;
            }
            if (isset($ruta['security']) && !\Sarasa\Models\MainController::security($ruta['security'])) {
                continue;
            }

            //Llama a otro routing.json interno
            if (!isset($ruta['url'])) {
                $router = (isset($ruta['router']) ? $ruta['router'] : 'routing') . '.json';
                $file = $_SERVER['DOCUMENT_ROOT'] . "/../app/" . $ruta['bundle'] . "/" . $router;
                $prefix = $ruta['prefix'] ? $ruta['prefix'] : '';
                while (substr($prefix, -1) == '/') {
                    $prefix = substr($prefix, 0, -1);
                }
                while (substr($prefix, 0, 1) == '/') {
                    $prefix = substr($prefix, 1);
                }

                if ($prefix == substr($aux, 0, strlen($prefix)) && file_exists($file)) {
                    $string = file_get_contents($file);
                    $newrout = json_decode($string, true);

                    if (is_array($newrout)) {
                        array_walk($newrout, 'self::addPrefix', $prefix);
                        $routing_var = array_merge($newrout, $routing_var);
                    }
                }

                $bundle = $ruta['bundle'];

                continue;
            }
            //

            if (!isset($ruta['bundle']) && isset($bundle)) {
                $ruta['bundle'] = $bundle;
            }

            while (substr($ruta['url'], -1) == '/') {
                $ruta['url'] = substr($ruta['url'], 0, -1);
            }
            while (substr($ruta['url'], 0, 1) == '/') {
                $ruta['url'] = substr($ruta['url'], 1);
            }

            $url_partes = explode('/', $ruta['url']);
            $num_partes = count($url_partes);

            $num_variables = count($variables);

            if (!$ruta['url']) {
                $num_variables = 1;
            }

            if (!strpos($ruta['url'], '*')) {
                if ($num_partes < $num_variables) {
                    $j ++;
                    continue;
                } elseif ($num_partes > $num_variables) {
                    $check = $url_partes [$num_variables];
                    if ($check{0} != ':') {
                        $j ++;
                        continue;
                    }
                }
            }

            foreach ($url_partes as $parte) {
                if ($parte == '*') {
                    break;
                }

                if (strlen($parte) && ($parte{0} == ":" && isset($variables[$j]))) {
                    self::$parameters[substr($parte, 1)] = $variables[$j];
                } elseif (!isset($variables[$j]) || $parte != $variables[$j]) {
                    $map = false;
                    break;
                }
                
                $j ++;
            }

            if ($map) {
                $url_final = 'app/' . $ruta['bundle'] . '/Controllers/' . $ruta['controller'] . $extension;
                $ruta_action = isset($ruta['action']) ? $ruta['action'] : 'index';

                //Backwards compatibility
                foreach (self::$parameters as $variable => $valor) {
                    $_GET[$variable] = $valor;
                }
                //
                
                break;
            }
        }
        //Fin del mapeo

        if (isset($url_final)) {
            $url_final = '../'.$url_final;
        }

        if (isset($url_final) && is_file($url_final)) {
            self::$key = (isset($key) ? $key : '');
            self::$bundle = $ruta['bundle'];
            self::$controller = $ruta['controller'];
            self::$action = $ruta_action;

            include $url_final;

            return self::$action;
        } elseif (self::config('forceroute') && isset($variables[0]) && isset($variables[1]) && !isset($variables[2]) && is_file('../app/' . $variables[0] . '/Controllers/' . $variables[1] . 'Controller.php')) {
            self::$key = '';
            self::$bundle = $variables[0];
            self::$controller = $variables[1] . 'Controller';
            self::$action = 'index';

            include '../app/' . $variables[0] . '/Controllers/' . $variables[1] . 'Controller.php';

            return self::$action;
        } else {
            \Sarasa\Models\Template::error404();
        }
    }

    private static function addPrefix(&$item, $key, $prefix)
    {
        $item['url'] = $prefix . '/' . $item['url'];
    }

    public static function handlePageException($e)
    {
        $template = new Template();

        if ($e->getCode() == 404) {
            header('HTTP/1.1 404 Not found');
            echo \Sarasa\Models\Template::error404();
            exit();
        } elseif ($e->getCode() == 222) {
            echo \Sarasa\Models\Template::error222($e);
        } else {
            header('HTTP/1.1 500');
            echo \Sarasa\Models\Template::error500($e);
            die();
        }
    }

    public static function security($flag)
    {
        if (isset($_SESSION[$flag])) {
            return true;
        } else {
            return false;
        }
    }

    /*** AJAX FUNCTIONS ***/
    public function debugbar(&$objResponse, $parameters)
    {
        $file = $_SERVER['DOCUMENT_ROOT'] . '/../logs/dev/' . $parameters['hash'] . '.json';
        if (!self::config('production') && is_file($file)) {
            $string = file_get_contents($file);
            $info = json_decode($string, true);

            $template = new Template(false);
            $template->assign('info', $info);
            $ret = $template->fetch('../vendor/sarasa/core/views/_debugbar.tpl');

            $objResponse->assign('debugcontainer', $ret);
        }
    }
}
