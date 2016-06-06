<?php
/**
 * Site set-up script.
 * TODO: make this more user-friendly
 */

include_once '../inc/config.php';

use Model\Model;
use Site\Site;

system("clear");
if(!function_exists("readline")){
    function readline( $prompt = '' ){
        echo $prompt;
        return rtrim( fgets( STDIN ), "\n\r" );
    }
}



echo "------------------------------------------------------------".PHP_EOL;
echo "                    ARCHIVE SET UP SCRIPT                   ".PHP_EOL;
echo "                           v 2.0                            ".PHP_EOL;
echo "------------------------------------------------------------".PHP_EOL;
echo PHP_EOL.PHP_EOL;

echo "Welcome to the archive set up script!".PHP_EOL.PHP_EOL;
echo "Before you begin, I will assume you have a working MySQL".PHP_EOL;
echo "installation setup. ".PHP_EOL;
echo "ALSO: This website assumes an Apache2 webserver, a working".PHP_EOL;
echo "installation of GNU screen, mod_rewrite enabled, and many".PHP_EOL;
echo "other miscellaneous requirements.".PHP_EOL;
echo "Also, ensure that PHP has write permissions for the ../cfg/".PHP_EOL;
echo "directory. ".PHP_EOL;

$line = readline("Do you have these? (y/n): ");
if(strtolower($line) != "y"){
    die("Okay, go ahead and set those up first, then we can begin.".PHP_EOL);
}

echo PHP_EOL.PHP_EOL.PHP_EOL."[  MYSQL CONFIG  ]".PHP_EOL.PHP_EOL;
echo "Before anything, we need to create a database schema,".PHP_EOL;
echo "Along with two users, one with read-only privileges and one".PHP_EOL;
echo "with read+write privileges for that database only.".PHP_EOL.PHP_EOL;

echo "To do this, you need to enter a mysql root username and".PHP_EOL;
echo "password, or the username and password of a MySQL user that".PHP_EOL;
echo "has the privileges to create users and databases.".PHP_EOL.PHP_EOL;

$driver = new mysqli_driver();
$driver->report_mode = MYSQLI_REPORT_ALL;

user_entry_1:
$root_username = readline("Enter your mysql root username: ");
$root_password = readline("Enter your mysql root password: ");
echo "Attempting login... ";
try {
     $db = new mysqli('localhost', $root_username, $root_password) ;
} catch (Exception $e ) {
     echo "Unable to login!".PHP_EOL;
     echo "Specific error: ".$e->getMessage().PHP_EOL;
    if (strtolower(readline("Try retyping user info? (y/n): ")) == "y") {
        goto user_entry_1;
    } else {
        echo "Make sure MySQL is configured properly and run this script again.".PHP_EOL;
        exit;
    }
}
echo "Login success!",PHP_EOL;

/*
 * Create or select database.
 */
if(strtolower(readline("Create a new database? (y/n)")) == "y"){
    database_new_entry:
    $database = $db->real_escape_string(readline("Enter your database name: "));
    echo "Attempting to create database `$database`... ";
    try {
       $db->query("CREATE DATABASE `$database` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    } catch (Exception $ex) {
        echo "Could not create database `$database`!".PHP_EOL;
        echo "Specific error: ".$e->getMessage().PHP_EOL;
        if (strtolower(readline("Try re-entering user info? (y/n): ")) == "y") {
            goto user_entry_1;
        } else {
            echo "Make sure MySQL is configured properly and run this script again.".PHP_EOL;
            exit;
        }
    }
    echo "Success!".PHP_EOL;
}
else{
    database_existing_entry:
    $database = $db->real_escape_string(readline("Enter your existing database name: "));
    echo "Connecting to `$database`... ";
    try {
        $db->select_db($database);
    } catch (Exception $ex) {
        echo "Error! Could not select database `$database`.".PHP_EOL;
        if (strtolower(readline("Try re-entering db name? (y/n): ")) == "y") {
            goto database_existing_entry;
        } elseif (strtolower(readline("Try making a new db instead? (y/n): ")) == "y") {
            goto database_new_entry;
        } else{
            echo "Make sure MySQL is configured properly and run this script again.".PHP_EOL;
            exit;
        }
    }
    echo "Success!".PHP_EOL;
}

/*
 * Create or enter user information.
 */
if(strtolower(readline("Create new readonly and read+write users? (y/n)")) == "y"){
    $username_ro = readline("Enter your new read-only user's username: ");
    $password_ro = readline("Enter your new read-only user's password: ");
    $username_rw = readline("Enter your new read+write user's username: ");
    $password_rw = readline("Enter your new read+write user's password: ");

    echo "Creating users... ";
    try {
        $db->query("CREATE USER '".$db->real_escape_string($username_ro).
        "'@'localhost' IDENTIFIED BY '".$db->real_escape_string($password_ro)."';");
        $db->query("CREATE USER '".$db->real_escape_string($username_rw).
        "'@'localhost' IDENTIFIED BY '".$db->real_escape_string($password_rw)."';");
        $db->query("GRANT SELECT ON `$database`.* TO '".$db->real_escape_string($username_ro)."'@'localhost'; ");
        $db->query("GRANT ALL ON `$database`.* TO '".$db->real_escape_string($username_rw)."'@'localhost'; ");
    } catch (Exception $ex) {
        echo "Could not create users!".PHP_EOL;
        echo "Specific error: ".$e->getMessage();
        if (strtolower(readline("Try re-entering root user info? (y/n): ")) == "y") {
            goto user_entry_1;
        } else {
            echo "Make sure MySQL is configured properly and run this script again.".PHP_EOL;
            exit;
        }
    }
    echo "Success!".PHP_EOL;
}
else{
    $username_ro = readline("Enter your new read-only user's username: ");
    $password_ro = readline("Enter your new read-only user's password: ");
    $username_rw = readline("Enter your new read+write user's username: ");
    $password_rw = readline("Enter your new read+write user's password: ");
}

echo PHP_EOL.PHP_EOL.PHP_EOL."[  SERVER CONFIG  ]".PHP_EOL.PHP_EOL;
echo "The site must know its servers for the front-end, thumbs, images, and swfs.".PHP_EOL;
$servers = [];
foreach(["site","images","thumbs","swf"] as $server)
{
  echo "Server: $server";
  $servers[$server]["http"] =  strtolower(readline("Use http (y/n): ")) == "y";
  $servers[$server]["hostname"] = readline("Enter HTTP hostname: ");
  $servers[$server]["port"] = readline("Enter HTTP port: ");
  $servers[$server]["https"] = strtolower(readline("Use https (y/n): ")) == "y";
  $servers[$server]["httpshostname"] = readline("Enter HTTPS hostname: ");
  $servers[$server]["httpsport"] = readline("Enter HTTPS port: ");
}

echo PHP_EOL."For the image, thumbs, and swf servers we need the URL format.".PHP_EOL;
echo "The format should look something like this:".PHP_EOL;
echo "/dir/%hex%%ext%".PHP_EOL;
echo "Available paramaters:".PHP_EOL;
echo "%hex% : the media's MD5 hash".PHP_EOL;
echo "  %1% : the first hex digit of the MD5".PHP_EOL;
echo "  %2% : the second hex digit of the MD5".PHP_EOL;
echo "%ext% : the media's file extension".PHP_EOL;
foreach(["images","thumbs","swf"] as $server)
{
  $servers[$server]["format"] = readline("Enter $server URL format: ");
}

echo "Writing mysql configuration to ../cfg/mysql.json ... ";
$mysql = [];
$mysql["read-only"] = [];
$mysql["read-write"] = [];

$mysql["read-only"]["username"] = $username_ro;
$mysql["read-only"]["password"] = $password_ro;
$mysql["read-only"]["db"] = $database;
$mysql["read-only"]["server"] = 'localhost';
$mysql["read-write"]["username"] = $username_rw;
$mysql["read-write"]["password"] = $password_rw;
$mysql["read-write"]["db"] = $database;
$mysql["read-write"]["server"] = 'localhost';
file_put_contents("../cfg/mysql.json", json_encode($mysql, JSON_PRETTY_PRINT));
echo "Done.".PHP_EOL;

echo "Writing server configuration to ../cfg/servers.json ... ";
$json = json_encode($cfg,JSON_PRETTY_PRINT);
file_put_contents("../cfg/mysql.json", json_encode($servers, JSON_PRETTY_PRINT));

echo "Done.".PHP_EOL;


echo "Setting up required tables...".PHP_EOL;
$driver->report_mode = MYSQLI_REPORT_ERROR;
if(!$db->query(file_get_contents("../sql/init.sql"))){
    echo "Could not set up all the tables for some reason! Start over!".PHP_EOL;
    echo "Error(s): ".PHP_EOL;
    foreach($db->error_list as $error) {
        print_r($error);
        echo PHP_EOL;
    }
    exit;
}

echo PHP_EOL.PHP_EOL.PHP_EOL."[  SITE CUSTOMIZATION  ]".PHP_EOL.PHP_EOL;
$site = [];
$site['name'] = readline("Enter a site name to show up on all pages (e.g., 'b-stats archive'): ");
$date = date('j F Y');
$site['subtitle'] = readline("Enter a subtitle to show up below the name (e.g. 'since $date'): ");
$site['pagetitle'] = readline("Enter a default HTML page title (e.g., 'archive'): ");

echo "Writing site configuration to ../cfg/site.json ... ";
$json = json_encode($cfg,JSON_PRETTY_PRINT);
file_put_contents("../cfg/site.json", json_encode($servers, JSON_PRETTY_PRINT));
echo "Done!".PHP_EOL.PHP_EOL;

echo "One last thing, before your site is ready. You must create".PHP_EOL;
echo "an admin user account.".PHP_EOL;

$username = readline("Enter admin username: ");
$password = readline("Enter admin password: ");

Model::get()->addUser($username, $password, Site::LEVEL_TERRANCE, "yotsuba");

echo "That's it! Your site is ready to go (hopefully)!".PHP_EOL;
echo "Thank you for choosing my shitty PHP scripts!".PHP_EOL;
echo PHP_EOL."~terrance".PHP_EOL;