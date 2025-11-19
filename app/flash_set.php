<?php
session_start();
function set_flash($msg, $type = 'info') {
    $_SESSION['flash'] = [
        'msg'  => $msg,
        'type' => $type
    ];
}

function get_flash() {
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

?>
