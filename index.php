<?php

define("START_TIME", microtime(true));

require_once('inc/config.php');
require_once('inc/globals.php');

// Page router
try {
  if (Site::backupInProgress()) {
    die((new Page("Backup in Progress", "<h2>Backing Up</h2><div class='centertext'>Please come back later.</div>"))->display());
  }
  if (Site::isBanned()) {
    die((new Banned())->display());
  }

  Router::route(strtok($_SERVER["REQUEST_URI"], '?'));

} catch (NotFoundException $ex) {
  echo (new FourOhFour($ex->getMessage()))->display();
} catch (PermissionException $ex) {
  die((new FancyPage("/b/ stats: ACCESS DENIED", 
          Site::parseHtmlFragment('accessDenied.html', 
                  ['__privilege__', '__required__'], 
                  [$ex->has, $ex->required]), 0))
          ->display());
} catch (PDOException $ex) {
  $page = new Page("Database Error","");
  $page->appendToBody(div('There was an error with the database.<br>'
          . 'It may be misconfigured.','centertext'));
  header("HTTP/1.0 500 Internal Server Error");
  echo $page->display();
} catch (Exception $ex) {
  $page = new FancyPage("Error", "", 0);
  $page->setBody(
          "<h1>Error</h1>"
          . "<div class='centertext'>"
          . "Your request could not be processed. The following error was encountered: "
          . "<br>" . $ex->getMessage() . " at ".$ex->getFile().":".$ex->getLine()."</div>");
  echo $page->display();
}