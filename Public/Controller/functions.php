<?php
// functions.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

function current_marketing_id() {
    return $_SESSION['user']['marketing_id'] ?? null;
}

// Fungsi helper untuk escape HTML
// function h($s) {
//     return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
// }

// Fungsi helper untuk normalisasi status
function normStatus($status) {
    return strtolower(trim($status));
}
?>
