<?php

namespace Sarasa\Core;

/**
 * Clase para manejar las vistas del sitio
 *
 */
class Template extends \Smarty
{
    protected $js = array();
    protected $css = array();
    private $_timestamp;
    protected static $sarasa;
    
    public function __construct($full = true)
    {
        parent::__construct();

        $this->setTemplateDir('../app');
        $this->setCompileDir('../vendor/sarasa/compile/templates');
        $this->setCacheDir('../vendor/sarasa/compile/cache');
        
        $this->registerPlugin("function", "url", array($this, 'url'));

        if ($full) {
            $this->css('main.css');
            $this->js('main.js');
            
            $css = FrontController::$bundle . '/' . strtolower(str_replace('Controller', '', FrontController::$controller)) . '.css';
            $js = FrontController::$bundle . '/' . strtolower(str_replace('Controller', '', FrontController::$controller)) . '.js';
            if (file_exists('../public/css/' . $css)) {
                $this->css($css);
            }
            if (file_exists('../public/js/' . $js)) {
                $this->js($js);
            }

            self::$sarasa = array(
                'sitename'    => FrontController::config('sitename'),
                'title'       => 'Welcome',
                'development' => FrontController::config('development'),
                );

            if (!FrontController::config('production')) {
                self::$sarasa['mtime'] = FrontController::$mtime;
                self::$sarasa['debugpath'] = FrontController::$debugpath;
                self::$sarasa['development'] = true;
            }
        }
    }

    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        self::$sarasa['js'] = $this->js;
        self::$sarasa['css'] = $this->css;

        $this->assign('sarasa', self::$sarasa);

        if (substr($template, 0, 1) != '/') {
            $template = FrontController::$bundle . '/Views/' . $template;
        } else {
            $template = 'Sarasa/Views/' . substr($template, 1);
        }
        
        parent::display($template, $cache_id, $compile_id, $parent);
    }

    public function isCached($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        if (substr($template, 0, 1) != '/') {
            $template = FrontController::$bundle . '/Views/' . $template;
        } else {
            $template = 'Sarasa/Views/' . substr($template, 1);
        }

        return parent::isCached($template, $cache_id, $compile_id, $parent);
    }

    public function js($file)
    {
        $this->js[] = $file . '?' . filemtime($_SERVER['DOCUMENT_ROOT'] . '/js/' . $file);
    }
    
    public function css($file)
    {
        $this->css[] = $file . '?' . filemtime($_SERVER['DOCUMENT_ROOT'] . '/css/' . $file);
    }

    public function redirect($name, $parameters)
    {
        if (isset($parameters['params']) && count($parameters['params']) > 0) {
            $parameters = $parameters['params'];
        }
        $url = self::getUrl($name, $parameters);
        header('Location: ' . $url);
        exit();
    }
    
    public function url($parameters, $smarty)
    {
        if (!isset($parameters['name'])) {
            return '#';
        }
        return self::getUrl($parameters['name'], $parameters);
    }

    public static function getUrl($module, $parameters = array(), $method = 'get')
    {
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

            $j = 0;
            $map = true;
            $ruta_variables = array();
            $ruta_method = isset($ruta['method']) ? $ruta['method'] : 'get';
            if (!isset($_SERVER['REQUEST_METHOD'])) {
                $_SERVER['REQUEST_METHOD'] = 'GET';
            }
            if (!isset($_SERVER['HTTP_AJAX_FUNCTION']) && strtolower($ruta_method) != strtolower($_SERVER['REQUEST_METHOD'])) {
                continue;
            }
            
            //Llama a otro routing.json interno
            if (!isset($ruta['url'])) {
                $file = $_SERVER['DOCUMENT_ROOT'] . "/../app/" . $ruta['bundle'] . "/routing.json";
                $prefix = $ruta['prefix'] ? $ruta['prefix'] : '';
                while (substr($prefix, -1) == '/') {
                    $prefix = substr($prefix, 0, -1);
                }
                while (substr($prefix, 0, 1) == '/') {
                    $prefix = substr($prefix, 1);
                }

                if (file_exists($file)) {
                    $string = file_get_contents($file);
                
                    $newrout = json_decode($string, true);

                    if (is_array($newrout)) {
                        array_walk($newrout, 'self::addPrefix', $prefix);
                        $routing_var = array_merge($newrout, $routing_var);
                        $keys = array_merge(array_keys($newrout), $keys);
                    }
                }

                continue;
            }

            if ($key == $module) {
                $url = $ruta['url'];

                $search = array_keys($parameters);
                array_walk($search, 'self::addDots');
                $url = str_replace($search, $parameters, $url);

                if (!isset($_SERVER['SERVER_NAME'])) {
                    $_SERVER['SERVER_NAME'] = str_replace(array('http://', 'https://'), '', FrontController::config('domain'));
                }

                while (substr($url, -1) == '/') {
                    $url = substr($url, 0, -1);
                }

                return 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['SERVER_NAME'] . FrontController::config('preurl') . $url;
            }
        }
    }

    private static function addDots(&$item, $key)
    {
        $item = ':'.$item;
    }

    private static function addPrefix(&$item, $key, $prefix)
    {
        $item['url'] = $prefix . '/' . $item['url'];
    }
    
    public function title($title, $postitle = true)
    {
        if ($postitle) {
            $title .= ' | ' . FrontController::config('postitle');
        }
        self::$sarasa['title'] = $title;
    }
    
    /**
     * Imprime un error 500 (Actualizando).
     * Si el error se produjo por un fallo y el sitio se encuentra en producción,
     * entonces muestra el error.
     *
     * @param Exception $e
     */
    public static function error500($e = null)
    {
        header("HTTP/1.1 500 Internal Server Error");
        $smarty = new \Sarasa\Models\Template();

        if ($e && !FrontController::config('production')) {
            $err = $e->getMessage();
        }
        
        $smarty->title(Lang::_('El sitio se encuentra en mantenimiento'));
        $smarty->assign('noindex', true);
        if (isset($err)) {
            $smarty->assign('err', $err);
        }
        $smarty->display('/500.tpl');
        die();
    }
    
    /**
     * Imprime un error 404
     *
     */
    public static function error404()
    {
        header("HTTP/1.0 404 Not Found");
        $smarty = new \Sarasa\Models\Template();

        $smarty->title('Página no encontrada');
        $smarty->assign('noindex', true);
        $smarty->display('/404.tpl');
        die();
    }

    /**
     * Imprime un error 222
     *
     */
    public static function error222($e = null)
    {
        $template = new \Sarasa\Models\Template();

        $template->title('Ocurrió un error');
        $template->assign('error', $e->getMessage());
        $template->assign('noindex', true);
        $template->display('/222.tpl');
    }

    public static function sanitizeOutput($buffer)
    {
        $search = array(
            '/\>[^\S ]+/s', //strip whitespaces after tags, except space
            '/[^\S ]+\</s', //strip whitespaces before tags, except space
            '/(\s)+/s'  // shorten multiple whitespace sequences
            );
        $replace = array(
            '>',
            '<',
            '\\1'
            );
            $buffer = preg_replace($search, $replace, $buffer);
        return $buffer;
    }
}
