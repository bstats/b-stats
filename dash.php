<?php
include('inc/config.php');
$page = new Page('b-stats dashboard',"",1);

$you = Site::getUser();

$error = '';
if(isset($_POST['action']) && $_POST['action'] == 'change'){
    if(Model::changePassword($you->getUID(), $_POST['old'], $_POST['new'])){
        $error = 'Password changed.';
    }
    else
        $error = 'Could not change password.';
            
}

ob_start();
?>
<h2>Dashboard</h2>
<table class='dashTable'>
    <tr>
        <th colspan='2'>General Infos</th>
    </tr>
    <tr>
        <td>Your username</td> <td><?=$you->getUsername()?></td>
    </tr>
    <tr>
        <td>Your privilege</td> <td><?=$you->getPrivilege()?></td>
    </tr>
    <tr>
        <td>Your preferred style</td> <td><?=$you->getTheme()?></td>
    </tr>
</table><br>
<form action='/dash.php' method='post'>
<input type='hidden' name='action' value='change'>
<table class='dashTable'>
    <tr>
        <th colspan='2'>Change Password</th>
    </tr>
    <tr>
        <td>Current Password</td> <td><input style='width:90%' type='password' name='old'></td>
    </tr>
    <tr>
        <td>New Password</td> <td><input style='width:90%' type='password' name='new'></td>
    </tr>
    <tr>
        <td colspan='2' class='center'><input type='submit' value='Change!'><?=$error?></td>
    </tr>
</table>
</form>
<?
$page->appendToBody(ob_get_clean());
ob_end_clean();
echo $page->display();