<?php
# R76 by Nicolas Torres (76.io), CC BY-SA license: creativecommons.org/licenses/by-sa/3.0
  final class base {
    private static $instance;
    private $root, $path = array(), $params = array(), $callback = false;

  # Init
    function __construct() {
      $this->root = '//'.trim($_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']), '/').'/';
      $this->parseURI();
      $this->rewriteGETparams();
      ob_start();
    }

  # Perform config (e.g. file|array|string)
    function config($cmd) {
      if (is_array($cmd)) return array_map(__METHOD__, $cmd);
      elseif (is_file($cmd)) return call_user_func(__METHOD__, preg_split('/\v/m', file_get_contents($cmd)));
      $cmd = trim($cmd);
      if ($cmd{0} == '#' OR empty($cmd)) return;
      $param = trim(strstr($cmd, ' '));
      switch ($cmd = strtolower(strstr($cmd, ' ', true))) {
        case 'load': if (!$this->load(array_map('trim', explode(';', $param)))) throw new Exception('Config LOAD - Unexisting folder(s): '.$param); break;
        case 'route': $this->route($param); break;
        case 'define': if (!define(strstr($param, ' ', true), trim(strstr($param, ' ')))) throw new Exception('Config DEFINE - Wrong syntax: '.$param); break;
        case 'custom': if (!$this->call(strstr($param, ' ', true), preg_split('/\h+/', trim(strstr($param, ' '))))) throw new Exception('Config CUSTOM - Wrong syntax: '.$param); break;
        default: throw new Exception('Config - Unknown command: '.$cmd); break;
      }
    }

  # Get URI components (and UI)
    function root() { return $this->root; }
    function uri() { return implode('/', $this->path); }
    function path($k) { return $this->path[$k]; }
    function param($k) { return $this->params[$k]; }
    function params() { return $this->params; }

  # Get complete current|custom URL
    function url($path = false, $params = array()) {
      if (is_array($path)) $params = array_replace($this->params, $path);
      elseif ($path === false) $params = $this->params;
      $path = ($path !== false AND !is_array($path)) ? trim($path, "/ \t\n\r\0\x0B") : $this->uri();
      return $this->root.implode('/', array_merge((array)$path, array_map(function($k, $v) { return $k.':'.urlencode($v); }, array_keys((array)$params), (array)$params)));
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

  # Parse URI and params
    private function parseURI() {
      $uri = trim(substr('//'.$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"], strlen($this->root)), '/');
      if ($params = basename(strstr($uri, ':', true)).strstr($uri, ':')) {
        foreach (explode('/', $params) as $p) { list ($k, $v) = explode(':', $p); $this->params[$k] = trim(urldecode($v)); }
        $uri = strstr($uri, '/'.$params, true);
      }
      if ($ext = strrchr(basename($uri), '.')) $uri = substr($uri, 0, -strlen($ext));
      $this->path = explode('/', $uri);
    }

  # Rewrite GET params (e.g. URI?search=terms&page=2 => URI/search:terms/page:2)
    private function rewriteGETparams() {
      if (!count($_GET)) return;
      header('location://'.trim($this->url(''.strstr($this->uri(), '?', true)), '/').'/'.implode('/', array_map(function($k, $v) { return $k.':'.stripslashes($v); }, array_keys($_GET), $_GET))); 
      exit;
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
      $pattern = '/^(?:'.$protocol.') '.preg_replace('/@[a-z0-9_]+/i', '([a-z0-9_-]+)', preg_quote($route, '/')).'$/i';
      if (preg_match($pattern, $_SERVER['REQUEST_METHOD'].' '.$this->uri(), $m)) {
        $this->path = array_combine(explode('/', str_replace('@', '', $route)), $this->path);
        $tmp = $this; $this->callback = preg_replace_callback('/@([a-z0-9_]+)/i', function($m) use ($tmp) { return $tmp->path($m[1]); }, $callback);
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