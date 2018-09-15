<?php
session_start();
//exit('ssdd');
error_reporting(-1);
date_default_timezone_set('UTC');

/**
 * Important Define Constants
 */
define("APP_BASE_PATH", __DIR__.'/');



include_once "includes/application.php";
include_once "includes/libraries/functions.php";
require_once "includes/libraries/PHPImageWorkshop/autoload.php";

$application = Application::getInstance();
$application->run();
