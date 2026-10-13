<?php
// Public entry point; application code and configuration stay outside the web root.
chdir(dirname(__DIR__, 2) . '/tools');
require dirname(__DIR__, 2) . '/tools/get-favicon.php';
