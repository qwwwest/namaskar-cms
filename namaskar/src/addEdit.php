<?php

$memberData = $db->findOne($idFromPost);

$userData = $sessData['userData'] ?? $memberData;
unset($_SESSION['sessData']['userData']);

$title = !$memberData ? 'Edit ' . $file : 'Add ' . $file;

// // Get status message from session 
// if (!empty($sessData['status']['msg'])) {
//     $statusMsg = $sessData['status']['msg'];
//     $statusMsgType = $sessData['status']['type'];
//     unset($_SESSION['sessData']['status']);
// }
?>