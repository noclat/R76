<?php
$site = include 'site/core/r76.php';
$site->config('site/core/CONFIG');
$site->run(function() { go(url('404')); });