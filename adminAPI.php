<?php
use Model\OldModel;
use Site\Site;

include("inc/config.php");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: x-requested-with, if-modified-since");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

$errmsg = "";
if (Site::getUser()->getPrivilege() >= Site::LEVEL_ADMIN) {
  try {
    switch ($_POST['a']) {
      case "deletePost":
        OldModel::deletePost($_POST['no'], $_POST['b']);
        $err = false;
        break;
      case "banImage":
        OldModel::banHash($_POST['hash']);
        $err = false;
        break;
      case "deleteReport":
        OldModel::deleteReport($_POST['no'], $_POST['b']);
        $err = false;
        break;
      case "banReporter":
        OldModel::banReporter($_POST['no'], $_POST['b']);
        $err = false;
        break;
      case "restorePost":
        if (Site::getUser()->getPrivilege() >= Site::LEVEL_TERRANCE) {
          OldModel::restorePost($_POST['no'], $_POST['b']);
        } else {
          list($err, $errmsg) = [true, "Check your privilege"];
        }
        break;
      default:
        $err = true;
        $errmsg = "No action.";
        break;
    }
  } catch (Exception $e) {
    $err = true;
    $errmsg = $e->getMessage();
  }
} else {
  $err = true;
  $errmsg = "Check your privilege.";
}

echo json_encode(["err"=>$err,"errmsg"=>$errmsg]);