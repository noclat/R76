<?php
# R76 by Nicolas Torres (76.io), CC BY-SA license: creativecommons.org/licenses/by-sa/3.0
  class R76 { static function __callstatic($f, array $args) { return call_user_func_array(array(R76_base::instance(), $f), $args); } }
  final class R76_base {
    private static $instance; private $root, $path = array(), $callback = false;
    static function instance() { if(!self::$instance) self::$instance = new self(); return self::$instance; }
    function __clone() {}

  # Parse URI and params & rewrite GET params (e.g. URI?search=terms&page=2 => URI/search:terms/page:2)
    function __construct() {
      if (count($_GET)) { header('location://'.trim(strstr($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], '?', true), '/').'/'.strtr(http_build_query($_GET), '=&', ':/')); exit; }
      $this->root = '//'.trim($_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']), '/').'/';
      $uri = explode('/', trim(substr('//'.$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"], strlen($this->root)), '/'));
      foreach ($uri as $p) if (strpos($p, ':') !== false) { list ($k, $v) = explode(':', $p); $_GET[$k] = trim(urldecode($v)); }
      $this->path = explode('/', preg_replace('/\.[a-z]+$/i', '', implode('/', array_slice($uri, 0, count($uri)-count($_GET)))));
      return ob_start();
    }

  # Perform config (e.g. file|array|string)
    function config($cmd, $callback = false) {
      if (is_file($cmd)) $cmd = preg_split('/\v/m', file_get_contents($cmd));
      if (is_array($cmd)) return array_map(__METHOD__, $cmd);
      $cmd = trim($cmd);
      if ($cmd{0} == '#' OR empty($cmd)) return;
      $param = preg_split('/\h+/', $cmd);
      switch (strtolower(array_shift($param))) {
        case 'load': if (!$this->load($param)) throw new Exception('Config - Unexisting folder(s): '.$cmd); break;
        case 'route': if (!$this->callback) $this->route(array_merge($param, (array)$callback)); break;
        case 'define': if (!define($param[0], $param[1])) throw new Exception('Config - Wrong syntax: '.$cmd); break;
        case 'custom': if (!$this->call(array_shift($param), $param)) throw new Exception('Config - Wrong syntax or callback: '.$cmd); break;
        default: throw new Exception('Config - Unknown command: '.$cmd); break;
      }
    }

  # Get URL components
    function root() { return $this->root; }
    function uri() { return implode('/', $this->path); }
    function path($k) { return $this->path[$k]; }
    function url($uri = false, $params = array()) {
      if (is_array($uri)) $params = array_replace($_GET, $uri);
      elseif ($uri === false) $params = $_GET;
      return $this->root.(($uri !== false AND !is_array($uri))?trim($uri, "/ \t\n\r\0\x0B"):$this->uri()).(count($params)?'/'.strtr(http_build_query($params), '=&', ':/'):'');
    }

  # Call the callback file|function|method
    function run($default = false) {
      if (!$this->call($this->callback) AND !$this->call($default)) throw new Exception('Run - Unknown callback: '.$this->callback.' or default: '.$default);
      return ob_end_flush();
    }

  # Load given PHP files (e.g. path/dir1;path/dir2;...)
    private function load($path) {
      if (is_array($path)) return array_map(__METHOD__, $path);
      foreach (glob(trim($path, '/').'/*.php') as $file) include_once $file;
      return is_dir($path);
    }

  # Match route (e.g. GET|POST|PUT|DELETE /path/with/@var path/to/file.ext|func()|class->method()). Note: you can use '@var' in callbacks
    private function route($params) {
      list ($verb, $route, $callback) = $params;
      $route = trim($route, '/');
      if (preg_match('/^(?:'.$verb.') '.preg_replace('/@[a-z0-9_]+/i', '([a-z0-9_-]+)', preg_quote($route, '/')).'$/i', $_SERVER['REQUEST_METHOD'].' '.$this->uri(), $m)) {
        $tmp = $this->path = array_combine(explode('/', str_replace('@', '', $route)), $this->path);
        $this->callback = !is_string($callback) ? $callback : preg_replace_callback('/@([a-z0-9_]+)/i', function($m) use ($tmp) { return $tmp[$m[1]]; }, trim($callback, '/'));
      }
    }

  # Call user file|function|method
    private function call($f, $args = false) {
      if (is_callable($f)) call_user_func_array($f, (array)$args);
      elseif (is_file((string)$f)) include $f;
      elseif (preg_match('/(.+)->(.+)/', (string)$f, $m) AND is_callable($f = array(new $m[1], $m[2]))) call_user_func_array($f, (array)$args);
      else return false; return true;
    }
  } 
  return R76_base::instance();