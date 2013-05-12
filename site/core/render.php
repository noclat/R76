<?php
# Include a file with inner scope data
  render($file, $data = array()) {
    extract((array)$data);
    include $file.'.php';
  }