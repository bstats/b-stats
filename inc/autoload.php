<?php
// Set class autoloader
function loader($className) {
  $className = str_replace('\\','/', $className);
  if(file_exists(__DIR__."/classes/$className.php")) {
    require_once __DIR__."/classes/$className.php";
  } elseif (file_exists(__DIR__."/pages/$className.php")) {
    require_once __DIR__."/pages/$className.php";
  }
  return false;
}
spl_autoload_register('loader');