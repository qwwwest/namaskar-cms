<?php

require_once "qwwwest/Kernel.php";

$sessData = $_SESSION['sessData'] ?? false;

$file = $urlParts[0] ?? false;
$idInUrl = $urlParts[1] ?? null;
$action = $_POST['action'] ?? $urlParts[2] ?? 'read';


require_once "Model.php";
$m = new Model();
$m->scan($dataFolder, "*.json");
die('too');

$title = "JconCrud";
$statusMessage = $sessData['status']['msg'] ?? false;
// Get status message from session 
if ($statusMessage) {
    $statusType = ($sessData['status']['type'] === 'success') ? 'success' : 'danger';
    unset($_SESSION['sessData']['status']);
}
$members = false;
$db = null;


if ($file === '') {
    $action = "none";
}

if ($file) {


}

$phoneRegex = "/^((\+)33|0|0033)[1-9](\d{2}){4}$/g";
$phoneRegex = "^\d([+. -]?\d){15,18}$";

$form = new FormWiz($file, true);
// $v->props('id', 'name', 'email', 'login', 'site', 'password');
// $v->types('int', 'str', 'email', 'str', 'site::id::domain', 'password');
// $v->labels('id', 'Name', 'Email', 'Login', 'Site', 'Mot De Passe');

$form->add('id', 'int')->readonly();
$form->req('firstname', 'text', 'Prénom')->min(2)->max(32);
$form->req('lastname', 'text', 'Nom');
$form->req('birthdate', 'date', 'Date De Naissance');
$form->req('postalcode', 'text', 'Code Postal')->regex('/[0-9]{4,5}/');
$form->req('phone', 'text', 'Téléphone')->regex($phoneRegex);
$form->req('newsletter', 'checkbox', 'Inscrit à la NL');
$form->req('email', 'email', 'E-mail');


if ($action === 'read' && $db) {
    $members = $db->findAll();
}

if ($action === 'create') {
    $title = 'Add ' . $file;
}

if ($action === 'edit') {
    $memberData = $db->findOne($idInUrl);

    $userData = $sessData['userData'] ?? $memberData;
    unset($_SESSION['sessData']['userData']);

    $title = !$memberData ? 'Edit ' . $file : 'Add ' . $file;
}
if ($action === 'edit') {
    $memberData = $db->findOne($idInUrl);

    $userData = $sessData['userData'] ?? $memberData;
    unset($_SESSION['sessData']['userData']);

    $title = !$memberData ? 'Edit ' . $file : 'Add ' . $file;
}

if (isset($_POST['submit'])) {
    include('userAction.php');
}

if ($action == 'delete' && $idInUrl) {
    // Delete data 
    $delete = $db->delete($idInUrl);

    if ($delete) {
        $sessData['status']['type'] = 'success';
        $sessData['status']['msg'] = 'Member data has been deleted successfully.';
    } else {
        $sessData['status']['type'] = 'error';
        $sessData['status']['msg'] = 'Some problem occurred, please try again.';
    }

    // Store status into the session 
    $_SESSION['sessData'] = $sessData;
    // Redirect to the respective page 
    header("Location:" . $redirectURL);
    exit();
}

$template = new Template('index.php', $GLOBALS); //evil ^^
// include('template/index.php');

echo $template->render();