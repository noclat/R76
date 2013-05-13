<?php
# Helpers
  function root() { return R76::root(); }
  function url($uri = false, $params = array()) { return R76::url($uri, $params); }
  function verb() { return R76::verb(); }
  function uri() { return R76::uri(); }
  function path($k) { return R76::path($k); }
  function param($k) { return R76::param($k); }
  function params() { return R76::params(); }
  function async() { return strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'; }

  function go($location = false) {
    if (!$location) $location = url();
    header('location:'.$location); exit;
  }
