<?php
/**
 * # R76 by Nicolas Torres (76.io)
 * light-weight and powerful PHP router
 * CC BY-SA license: creativecommons.org/licenses/by-sa/3.0
 */
final class R76_base {

    /**
     * [$instance description]
     * @var [type]
     */
    private static $instance;

    /**
     * [$root description]
     * @var [type]
     */
    private $root;

    /**
     * [$path description]
     * @var array
     */
    private $path     = array();

    /**
     * [$callback description]
     * @var boolean
     */
    private $callback = false;

    /**
     * Build a new instance of R76_base
     * @return R76_base 
     */
    public static function instance() { 

        if(!self::$instance) self::$instance = new self(); 
        return self::$instance; 
    }

    private function __clone() {}

    /**
     * Parse URI and params & rewrite GET params
     * Ex :
     *     (e.g. URI?search=terms&page=2 => URI/search:terms/page:2)
     * @return   [description]
     */
    public function __construct() {

        if (count($_GET)) { 
            // Dafuq ?
            $base_url = trim(strstr($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], '?', true), '/');
            $query    = strtr(http_build_query($_GET), '=&', ':/');
            header('location://'.$base_url.'/'.$query);
            exit; 
        }

        // Dafuq ?
        $this->root = '//'.trim($_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']), '/').'/';

        // Dafuq ?
        $uri = explode('/', trim(substr('//'.$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"], strlen($this->root)), '/'));

        // Dafuq ?
        foreach ($uri as $p) {

            // Ok it seems to be $_GET from request
            if (strpos($p, ':') !== false) { 
                list ($k, $v) = explode(':', $p); 
                $_GET[$k] = trim(urldecode($v)); 
            }
        }

        // Dafuq ?
        $this->path = explode('/', preg_replace('/\.[a-z]+$/i', '', implode('/', array_slice($uri, 0, count($uri)-count($_GET)))));

        return ob_start();
    }

    /**
     * Perform config from file
     * @param  String $cmd File configuration for your routes
     */
    public function config($cmd) {

        if (is_file($cmd)) {
          return array_map(__METHOD__, preg_split('/\v/m', file_get_contents($cmd)));
        }

        if (!is_string($cmd)) {
            throw new Exception('Config — Command should be a string');
        }

        // Dafuq ?
        $param = preg_split('/\h+/', trim($cmd));

        // Dafuq ?
        if ($param[0]{0} == '#' OR empty($param[0])) return;

        // Dafuq ?
        switch (strtolower(array_shift($param))) {

            case 'route': 
                $this->route($param[0], $param[1], $param[2]);
                break;
            case 'call': 
                if (!$this->call(array_shift($param), $param)) {
                    throw new Exception('Call - Wrong syntax or callback: '.$cmd);
                }
                break;
            default: 
                throw new Exception('Config - Unknown command: '.$cmd); 
                break;
        }
    }

    /**
     * Get URL components
     * @return [type] [description]
     */
    public function root() { 
        return $this->root; 
    }

    /**
     * Get URL params ?
     * @return Array
     */
    public function uri() { 
        return implode('/', $this->path);
    }

    /**
     * [path description]
     * @param  [type] $k [description]
     * @return [type]    [description]
     */
    public function path($k) {
        return $this->path[$k];
    }
    
    /**
     * Get URL: 
     *     (void, void) -> current URL,
     *     (arr, void) -> current URL + updated params, 
     *     (str, arr) -> new URL + new params
     * @param  boolean $uri    [description]
     * @param  array   $params [description]
     * @return [type]          [description]
     */
    public function url($uri = false, $params = array()) {

      if (is_array($uri)) {
        $params = array_replace($_GET, $uri);

        }elseif ($uri === false) {
            $params = $_GET;
        }

        // Dafuq ?
        return $this->root.(($uri !== false AND !is_array($uri)) ? trim($uri, "/ \t\n\r\0\x0B") : $this->uri()).(count($params) ? '/'.strtr(http_build_query($params), '=&', ':/') : '');
    }

    /**
     * Call the callback file|function|method
     * @param  boolean $default [description]
     * @return [type]           [description]
     */
    public function run($default = false) {

        if (!$this->call($this->callback) AND !$this->call($default)) {
          throw new Exception('Run - Unknown callback: '.$this->callback.' or default: '.$default);
        }

        return ob_end_flush();
    }

    /**
     * Match route
     * Ex :
     *     - GET|POST|PUT|DELET
     *     - /path/with/@var
     *     - path/to/file.ext|func()|class->method()
     *
     * Note: you can use '@var' in callbacks.
     * @param  string $verb     HTTP verb
     * @param  string $route    the current route
     * @param  function $callback [description]
     * @return [type]           [description]
     */
    public function route($verb, $route, $callback) {

        if ($this->callback) return;

        if (!is_string($route = trim($route, '/')) OR !is_string($verb)) {
            throw new Exception('Route — First two parameters should be strings.');
        }

        // Dafuq ? Is this magic ?
        if (preg_match('/^(?:'.strtolower($verb).') '.preg_replace('/@[a-z0-9_]+/i', '([a-z0-9_-]+)', preg_quote($route, '/')).'$/i', strtolower($_SERVER['REQUEST_METHOD']).' '.$this->uri(), $m)) {
            $tmp = $this->path = array_combine(explode('/', str_replace('@', '', $route)), $this->path);

            if(!is_string($callback)) {
                $this->callback = $callback;
            }else {
                // Dafuq ? What is that ?
                $this->callback = preg_replace_callback('/@([a-z0-9_]+)/i', function($m) use ($tmp) {
                    return $tmp[$m[1]]; 
                }, trim($callback, '/'));
            }
        }
    }
    
  # Wrappers: get, post, put, delete

    /**
     * Wrappers for GET|POST|PUT|DELETE
     * @param  [type] $f    [description]
     * @param  [type] $args [description]
     * @return [type]       [description]
     */
    public function __call($f, $args) { 

        if (!in_array($f, explode(',', 'get,put,post,delete'))) {
            throw new Exception('R76 — Invalid method: '.$f);
        }

        $this->route($f, $args[0], $args[1]);
    }

    /**
     * Call user file|function|method
     * @param  [type]  $f    [description]
     * @param  boolean $args [description]
     * @return [type]        [description]
     */
    private function call($f, $args = false) {

        if (is_callable($f)) {
            call_user_func_array($f, (array)$args);
        } elseif (is_file((string)$f)) {
            include $f;

        // Dafuq ?
        } elseif (preg_match('/(.+)->(.+)/', (string)$f, $m) AND is_callable($f = array(new $m[1], $m[2]))) {
            call_user_func_array($f, (array)$args);
        } else {
            return false;
        }

        return true;
    }
} 
  
/**
 * Singleton pattern & return instance
 */
class R76 { 

    /**
     * [__callstatic description]
     * @param  [type] $f    [description]
     * @param  array  $args [description]
     * @return [type]       [description]
     */
    public static function __callstatic($f, array $args) { 

        return call_user_func_array(array(R76_base::instance(), $f), $args); 
    } 
}

return R76_base::instance();