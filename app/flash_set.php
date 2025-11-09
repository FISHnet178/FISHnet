<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

if (!empty($_SESSION['flash']) && isset($_SESSION['flash']['msg'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $colors = [
        'success' => '#4CAF50',
        'error'   => '#f44336',
        'info'    => '#2196F3',
        'warning' => '#ff9800',
    ];

    $color = $colors[$flash['type']] ?? '#2196F3';
    $msg   = htmlspecialchars($flash['msg']);

    echo '<div class="flash-message" style="
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        background:' . $color . ';
        color:#fff;
        padding:12px 20px;
        border-radius:6px;
        box-shadow:0 3px 8px rgba(0,0,0,0.2);
        font-size:15px;
        z-index:9999;
        animation: fadeInOut 4s ease forwards;
    ">' . $msg . '</div>

    <style>
    @keyframes fadeInOut {
        0% { opacity: 0; transform: translateY(-10px) translateX(-50%); }
        10% { opacity: 1; transform: translateY(0) translateX(-50%); }
        80% { opacity: 1; }
        100% { opacity: 0; transform: translateY(-10px) translateX(-50%); }
    }
    </style>';
}
?>
