<?php
# R76 by Nicolas Torres (ntorres.me), CC BY-SA license: creativecommons.org/licenses/by-sa/3.0
  class R76 { public static function __callstatic($func, array $args) { return call_user_func_array(array(R76_base::instance(), $func), $args); } }
  final class R76_base {
    private static $instance; private $root, $path = array(), $callback = false;

  # Parse URI and params & rewrite GET params (e.g. URI?search=terms&page=2 => URI/search:terms/page:2)
    public function __construct() {
      if (count($_GET)) { header('location://'.trim(strstr($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], '?', true), '/').'/'.strtr(http_build_query($_GET), '=&', ':/')); exit; }
      $this->root = '//'.trim($_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']), '/').'/';
      $uri = explode('/', trim(substr('//'.$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"], strlen($this->root)), '/'));
      foreach ($uri as $chunk) if (strpos($chunk, ':') !== false) { list ($k, $v) = explode(':', $chunk); $_GET[$k] = trim(urldecode($v)); }
      $this->path = explode('/', preg_replace('/\.[a-z]+$/i', '', implode('/', array_slice($uri, 0, count($uri)-count($_GET)))));
    }

  # Get URL components
    public function root() { return $this->root; }
    public function uri($k = false) { 
      $p = is_int($k) ? array_values($this->path) : $this->path;
      return ($k === false) ? implode('/', $p) : $p[$k];
    }

  # Get URL: (void, void) -> current URL; (arr, void) -> current URL + updated GET params; (str, arr) -> new URL + new GET params
    public function url($uri = false, $get = array()) {
      if (is_array($uri)) $get = array_replace($_GET, $uri);
      elseif ($uri === false) $get = $_GET;
      return $this->root.(($uri !== false AND !is_array($uri))?trim($uri, "/ \t\n\r\0\x0B"):$this->uri()).(count($get)?'/'.strtr(http_build_query($get), '=&', ':/'):'');
    }

  # Call the callback file|function|method
    public function run($default = false) { return $this->call(array_shift($this->callback), $this->callback) OR $this->call($default); }

  # Match route (e.g. GET|POST|PUT|DELETE, /path/with/@var, path/to/file.ext|func()|class->method()). Note: you can use '@var' in callbacks name.
    public function on($verb, $route, $callback) {
      if ($this->callback) return;
      if (!is_string($route = trim($route, '/')) OR !is_string($verb)) throw new Exception('Route - First two parameters should be strings.');
      if (preg_match('/^(?:'.strtolower($verb).') '.preg_replace('/@[a-z][a-z0-9_]*/i', '([a-z0-9_-]+)', preg_quote($route, '/')).'$/i', strtolower($_SERVER['REQUEST_METHOD']).' '.$this->uri(), $m)) {
        $tmp = $this->path = array_combine(explode('/', str_replace('@', '', $route)), $this->path);
        $this->callback = array_merge(array(!is_string($callback) ? $callback : preg_replace_callback('/@([a-z][a-z0-9_]*)/i', function($m) use ($tmp) { return $tmp[$m[1]]; }, trim($callback, '/'))), array_slice($m, 1));
      }
    }

  # Wrappers: get, post, put, delete
    public function __call($func, $args) { 
      if (!in_array($func, explode(',', 'get,put,post,delete'))) throw new Exception('R76 - Invalid method: '.$func);
      $this->on($func, $args[0], $args[1]);
    }

  # Call user file|function|method
    private function call($func, $args = array()) {
      if (is_callable($func)) $abort = call_user_func_array($func, $args);
      elseif (is_file((string)$func)) $abort = include $func;
      elseif (preg_match('/(.+)->(.+)/', (string)$func, $m) AND is_callable($func = array(new $m[1], $m[2]))) $abort = call_user_func_array($func, $args);
      else return false; return $abort !== false;
    }

  # Singleton pattern
    public static function instance() { if(!self::$instance) self::$instance = new self(); return self::$instance; }
    private function __clone() {}
  }

# Helpers
  function root() { return R76::root(); }
  function url($uri = false, $params = array()) { return R76::url($uri, $params); }
  function uri($k = false) { return R76::uri($k); }
  function on($verb, $route, $callback) { R76::on($verb, $route, $callback); }
  function get($route, $callback) { R76::get($route, $callback); }
  function post($route, $callback) { R76::post($route, $callback); }
  function put($route, $callback) { R76::put($route, $callback); }
  function delete($route, $callback) { R76::delete($route, $callback); }
  function run($callback = false) { R76::run($callback); }
  function verb() { return $_SERVER['REQUEST_METHOD']; }
  function async() { return strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'; }
  function load($path) { foreach (glob(trim($path, '/').'/*.php') as $file) include_once $file; }
  function go($location = false) { if (!$location) $location = url(); header('location:'.$location); exit; }