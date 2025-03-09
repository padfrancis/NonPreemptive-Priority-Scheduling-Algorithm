<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['patients'])) {
    $_SESSION['patients'] = [];
}

if (isset($_POST['clear'])) {
    $_SESSION['patients'] = [];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['addPatient'])) {
    $name         = trim($_POST['name'] ?? '');
    $arrival_time = floatval($_POST['arrival_time'] ?? 0);
    $burst_time   = floatval($_POST['burst_time'] ?? 0);
    $priority     = intval($_POST['priority'] ?? 1);

    $_SESSION['patients'][] = [
        'name'         => $name,
        'arrival_time' => $arrival_time,
        'burst_time'   => $burst_time,
        'priority'     => $priority
    ];

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
