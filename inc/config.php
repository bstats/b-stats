<?php
// Set class autoloader
function loader($className) {
  if(file_exists("inc/classes/$className.php")) {
    require_once "inc/classes/$className.php";
  } elseif (file_exists("pages/$className.php")) {
    require_once "pages/$className.php";
  }
  return false;
}
spl_autoload_register('loader');


// Initialize user session.
session_start();
if(!isset($_SESSION['user'])){
    $_SESSION['style']="yotsuba";
    $_SESSION['user'] = null;
    $_SESSION['privilege'] = 0;
    $_SESSION['banned'] = false;
}

// Sets all PHP errors to exceptions.
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});