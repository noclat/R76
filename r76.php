<?php
# R76 by Nicolas Torres (ntorres.me), CC BY-SA license: creativecommons.org/licenses/by-sa/3.0
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

  # Get URL: (void, void) -> current URL, (arr, void) -> current URL + updated params, (str, arr) -> new URL + new params
    public function url($uri = false, $params = array()) {
      if (is_array($uri)) $params = array_replace($_GET, $uri);
      elseif ($uri === false) $params = $_GET;
      return $this->root.(($uri !== false AND !is_array($uri))?trim($uri, "/ \t\n\r\0\x0B"):$this->uri()).(count($params)?'/'.strtr(http_build_query($params), '=&', ':/'):'');
    }

  # Call the callback file|function|method
    public function run($default = false) { return $this->call($this->callback) OR $this->call($default); }

  # Match route (e.g. GET|POST|PUT|DELETE, /path/with/@var, path/to/file.ext|func()|class->method()). Note: you can use '@var' in callbacks.
    public function route($verb, $route, $callback) {
      if ($this->callback) return true;
      if (!is_string($route = trim($route, '/')) OR !is_string($verb)) throw new Exception('Route - First two parameters should be strings.');
      if (preg_match('/^(?:'.strtolower($verb).') '.preg_replace('/@[a-z0-9_]+/i', '([a-z0-9_-]+)', preg_quote($route, '/')).'$/i', strtolower($_SERVER['REQUEST_METHOD']).' '.$this->uri(), $m)) {
        $tmp = $this->path = array_combine(explode('/', str_replace('@', '', $route)), $this->path);
        $this->callback = !is_string($callback) ? $callback : preg_replace_callback('/@([a-z0-9_]+)/i', function($m) use ($tmp) { return $tmp[$m[1]]; }, trim($callback, '/'));
      } return true;
    }

  # Wrappers: get, post, put, delete
    public function __call($func, $args) { 
      if (!in_array($func, explode(',', 'get,put,post,delete'))) throw new Exception('R76 - Invalid method: '.$func);
      $this->route($func, $args[0], $args[1]);
    }

  # Perform config from file
    public function config($file) {
      if (!is_file($file)) throw new Exception('Config - Invalid file: '.$file);
      foreach (array_map('trim', preg_split('/\v/m', file_get_contents($file))) as $cmd) {
        if ($cmd{0} == '#' OR count($chunks = preg_split('/\h+/', $cmd)) <= 1) continue;
        if (call_user_func_array(array($this, strtolower(array_shift($chunks))), (array)$chunks) === false) throw new Exception('Config - Unknown command: '.$cmd);
      }
    }

  # Call user file|function|method
    private function call() {
      $args = func_get_args();
      if (is_callable($func = array_shift($args))) $abort = call_user_func_array($func, (array)$args);
      elseif (is_file((string)$func)) $abort = include $func;
      elseif (preg_match('/(.+)->(.+)/', (string)$func, $m) AND is_callable($func = array(new $m[1], $m[2]))) $abort = call_user_func_array($func, (array)$args);
      else return false; return $abort !== false;
    }

  # Singleton pattern
    public static function instance() { if(!self::$instance) self::$instance = new self(); return self::$instance; }
    private function __clone() {}
  } 

# R76 Static call class & return instance
  class R76 { public static function __callstatic($func, array $args) { return call_user_func_array(array(R76_base::instance(), $func), $args); } }
  return R76_base::instance();