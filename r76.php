<?php
/**
 * R76 by Nicolas Torres (76.io)
 * light-weight and powerful PHP router
 * CC BY-SA license: creativecommons.org/licenses/by-sa/3.0
 */
final class R76_base
{
    private static $instance; private $root, $path = array(), $callback = false;

    /**
     * Parse URI and params & rewrite GET params
     * Ex :
     *     (e.g. URI?search=terms&page=2 => URI/search:terms/page:2)
     * @return   [description]
     */
    public function __construct()
    {
        if (count($_GET)) {

            // Find current URL without queries params
            header('location://'.trim(strstr($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], '?', true), '/').'/'.strtr(http_build_query($_GET), '=&', ':/')); 

            // die(); Is better.
            exit;
        }

        // Find root URL for the site
        $this->root = '//'.trim($_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']), '/').'/';

        // Find URI based on the full URL. Built a fragment URL
        // Usefull for blog/article/@title etc.
        $uri = explode('/', trim(substr('//'.$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"], strlen($this->root)), '/'));

        // Build $_GET from custom query rules
        foreach ($uri as $chunk) {

            if (strpos($chunk, ':') !== false) {
                list ($k, $v) = explode(':', $chunk);
                $_GET[$k] = trim(urldecode($v));
            }
        }

        /**
         * 
         * $uri est un tableau, car je l'ai explode précédemment, je vire donc les paramètres GET
         * $URIwithoutGETparams = array_slice($uri, 0, count($uri)-count($_GET));
         * // j'en refais une chaîne pour mon preg_replace
         * $URIwithoutGETparams = implode('/', $URIwithoutGETparams); 
         * // je vire l'extension, comme ça le routeur interprète "site.com/sitemap" et "site.com/sitemap.xml" comme la même URL
         * $extensionFreeURI = preg_replace('/\.[a-z]+$/i', '', $URIwithoutGETparams);
         * // j'injecte dans $this->path sous forme de fragments (un "/sous-dossier/" = un fragment
         * $this->path = explode('/', $extensionFreeURI);
         */
        $this->path = explode('/', preg_replace('/\.[a-z]+$/i', '', implode('/', array_slice($uri, 0, count($uri)-count($_GET)))));

        return ob_start();
    }

    # Get URL components
    public function root() { return $this->root; }

    public function uri()
    {
        return implode('/', $this->path);
    }

    public function path($k)
    {
        $p = is_int($k) ? array_values($this->path) : $this->path;
        return $p[$k];
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
    public function url($uri = false, $params = array())
    {

        if (is_array($uri)) {
            $params = array_replace($_GET, $uri);
        }elseif ($uri === false) {
            $params = $_GET;
        }

        return $this->root.(($uri !== false AND !is_array($uri))?trim($uri, "/ \t\n\r\0\x0B"):$this->uri()).(count($params)?'/'.strtr(http_build_query($params), '=&', ':/'):'');
    }

    /**
     * Call the callback file|function|method
     * @param  boolean $default [description]
     * @return [type]           [description]
     */
    public function run($default = false)
    {
        if ( !$this->call($this->callback) AND !$this->call($default) ) {
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
    public function route($verb, $route, $callback)
    {
        if ($this->callback) return true;

        if ( !is_string($route = trim($route, '/')) OR !is_string($verb) ) {
            throw new Exception('Route — First two parameters should be strings.');
        }

        if (preg_match('/^(?:'.strtolower($verb).') '.preg_replace('/@[a-z0-9_]+/i', '([a-z0-9_-]+)', preg_quote($route, '/')).'$/i', strtolower($_SERVER['REQUEST_METHOD']).' '.$this->uri(), $m)) {

            $tmp = $this->path = array_combine(explode('/', str_replace('@', '', $route)), $this->path);

            $this->callback = !is_string($callback) ? $callback : preg_replace_callback('/@([a-z0-9_]+)/i', function($m) use ($tmp) { return $tmp[$m[1]]; }, trim($callback, '/'));
        }
        return true;
    }

     /**
     * Wrappers for GET|POST|PUT|DELETE
     * @param  String $func  Type of request
     * @param  Array $args
     */
    public function __call($func, $args)
    {
        if ( !in_array($func, explode(',', 'get,put,post,delete')) ) {

            throw new Exception('R76 — Invalid method: '.$func);
        }

        $this->route($func, $args[0], $args[1]);
    }

    /**
     * Perform config from file
     * @param  String file File configuration for your routes
     */
    public function config($file)
    {
        if (!is_file($file)) {
            throw new Exception('Config — Invalid file: '.$file);
        }

        $trims = array_map('trim', preg_split('/\v/m', file_get_contents($file)));

        foreach ($trims as $cmd) {

            if ($cmd{0} == '#' OR empty($cmd)) continue;

            $param = preg_split('/\h+/', $cmd);

            if ( !call_user_func_array(array($this, strtolower(array_shift($param))), $param) ) {
                throw new Exception('Config - Unknown command: '.$cmd);
            }
        }
    }

    /**
     * Call user file|function|method
     * @return boolean
     */
    private function call()
    {
        $args = func_get_args();

        if ( is_callable($func = array_shift($args)) ) {

            call_user_func_array($func, (array)$args);
        } elseif (is_file((string)$func)) {

            include $func;
        } elseif ( preg_match('/(.+)->(.+)/', (string)$func, $m) AND is_callable($func = array(new $m[1], $m[2])) ) {

            call_user_func_array($func, (array)$args);
        } else {
            return false;
        }

        return true;
    }

    /**
     * Singleton pattern
     * @return R76_base
     */
    public static function instance()
    {
        if(!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __clone() {}
}

/**
 * R76 Static call class & return instance
 */
class R76
{
    /**
     * [__callstatic description]
     * @param  [type] $f    [description]
     * @param  array  $args [description]
     * @return [type]       [description]
     */
    public static function __callstatic($func, array $args)
    {
        return call_user_func_array(
                  array(R76_base::instance(), $func),
                  $args
              );
    }

}

return R76_base::instance();
