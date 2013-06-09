<?php
# R76 by Nicolas Torres (76.io), CC BY-SA license: creativecommons.org/licenses/by-sa/3.0
  final class base {
    private static $instance;
    private $root, $path = array(), $params = array(), $callback = false;

  # Parse URI and params & rewrite GET params (e.g. URI?search=terms&page=2 => URI/search:terms/page:2)
    function __construct() {
      if (count($_GET)) { header('location://'.trim(strstr($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], '?', true), '/').'/'.strtr(http_build_query($_GET), '=&', ':/')); exit; }
      $this->root = '//'.trim($_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']), '/').'/';
      $uri = explode('/', trim(substr('//'.$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"], strlen($this->root)), '/'));
      foreach ($uri as $p) if (strpos($p, ':') !== false) { list ($k, $v) = explode(':', $p); $this->params[$k] = trim(urldecode($v)); }
      $uri = implode('/', array_reverse(array_slice(array_reverse($uri), count($this->params))));
      $this->path = explode('/', rtrim(strrev(substr(strrev($uri), strpos(strrev($uri), '.'))), '.'));
      $_GET = $this->params;
      ob_start();
    }

  # Perform config (e.g. file|array|string)
    function config($cmd) {
      if (is_array($cmd)) return array_map(__METHOD__, $cmd);
      elseif (is_file($cmd)) return call_user_func(__METHOD__, preg_split('/\v/m', file_get_contents($cmd)));
      $cmd = trim($cmd);
      if ($cmd{0} == '#' OR empty($cmd)) return;
      $param = trim(strstr($cmd, ' '));
      switch (strtolower(strstr($cmd, ' ', true))) {
        case 'load': if (!$this->load(array_map('trim', explode(';', $param)))) throw new Exception('Config - Unexisting folder(s): '.$cmd); break;
        case 'route': $this->route($param); break;
        case 'define': if (!define(strstr($param, ' ', true), trim(strstr($param, ' ')))) throw new Exception('Config - Wrong syntax: '.$cmd); break;
        case 'custom': if (!$this->call(strstr($param, ' ', true), preg_split('/\h+/', trim(strstr($param, ' '))))) throw new Exception('Configs - Wrong syntax: '.$cmd); break;
        default: throw new Exception('Config - Unknown command: '.$cmd); break;
      }
    }

  # Get URL components
    function root() { return $this->root; }
    function uri() { return implode('/', $this->path); }
    function path($k) { return $this->path[$k]; }
    function param($k) { return $this->params[$k]; }
    function params() { return $this->params; }

  # Get absolute current|custom URL
    function url($uri = false, $params = array()) {
      if (is_array($uri)) $params = array_replace($this->params, $uri);
      elseif ($uri === false) $params = $this->params;
      return $this->root.(($uri !== false AND !is_array($uri))?trim($uri, "/ \t\n\r\0\x0B"):$this->uri()).(count($params)?'/'.strtr(http_build_query($params), '=&', ':/'):'');
    }

  # Call the callback file|function|method
    function run($default = false) {
      if (is_string($this->callback)) $this->callback = array_map('trim', explode(';', $this->callback));
      if (!$this->call($this->callback)) {
        if ($default !== false) { $this->callback = $default; return $this->run(); }
        else throw new Exception('Run - Unknown callback: '.$this->callback);
      }
      return ob_end_flush();
    }

  # Load given PHP files (e.g. path/dir1;path/dir2;...)
    private function load($path) {
      if (is_array($path)) return array_map(__METHOD__, $path);
      if (!is_dir($path)) return false;
      foreach (glob(trim($path, '/').'/*.php') as $file) include_once $file;
      return true;
    }

  # Match route (e.g. GET|POST|PUT|DELETE /path/with/@var file|func()|class->method() argument1:value1;argument2:value2) 
  # PS: you can use '@var' in the name of your callback (e.g. GET /@section/@id @section->@id())
    private function route($cmd) {
      if ($this->callback) return;
      list ($protocol, $route, $callback) = preg_split('/\h+/', trim($cmd));
      $route = trim($route, '/');
      if (preg_match('/^(?:'.$protocol.') '.preg_replace('/@[a-z0-9_]+/i', '([a-z0-9_-]+)', preg_quote($route, '/')).'$/i', $_SERVER['REQUEST_METHOD'].' '.$this->uri(), $m)) {
        $this->path = array_combine(explode('/', str_replace('@', '', $route)), $this->path);
        $tmp = $this; $this->callback = preg_replace_callback('/@([a-z0-9_]+)/i', function($m) use ($tmp) { return $tmp->path($m[1]); }, trim($callback, '/'));
      }
    }

  # Call user file|function|method
    private function call($f, $args = false) {
      if (is_array($f)) return array_sum(array_map(__METHOD__, $f));
      elseif (is_callable($f)) call_user_func_array($f, (array)$args);
      elseif (is_string($f)) {
        if (is_file($f)) include $f;
        elseif (preg_match('/(.+)->(.+)/', $f, $m)) {
          if (!class_exists($m[1]) OR !is_callable($f = array(new $m[1], $m[3]))) return false;
          call_user_func_array($f, (array)$args);
        } else return false;
      } else return false;
      return true;
    }

  # Singleton pattern
    function __clone() {}
    static function instance() { 
      if(!self::$instance) self::$instance = new self(); 
      return self::$instance; 
    }
  }



# Static-fy R76 methods
  class R76 {
    private static $_;
    static function __callstatic($func, array $args) { 
      if (!self::$_) self::$_ = base::instance();
      return call_user_func_array(array(self::$_, $func), $args); 
    }
  }



# Load site
  return base::instance();