<?php
require_once 'autoload.php';

// Initialize user session.
session_start();

// Sets all PHP errors to exceptions.
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});