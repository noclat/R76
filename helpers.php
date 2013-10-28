<?php
# Helpers
  function root() { return R76::root(); }
  function url($uri = false, $params = array()) { return R76::url($uri, $params); }
  function uri($k = false) { return R76::uri($k); }
  function get($k = false) { return $k !== false ? $_GET[$k] : $_GET; }
  function verb() { return $_SERVER['REQUEST_METHOD']; }
  function async() { return strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'; }
  function load($path) { foreach (glob(trim($path, '/').'/*.php') as $file) include_once $file; }
  function go($location = false) { if (!$location) $location = url(); header('location:'.$location); exit; }
  