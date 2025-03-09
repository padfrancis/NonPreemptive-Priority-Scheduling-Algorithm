<?php
// Start the session if not started already
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Ensure we have a patients array in the session
if (!isset($_SESSION['patients'])) {
    $_SESSION['patients'] = [];
}

// Handle "Clear All Patients"
if (isset($_POST['clear'])) {
    $_SESSION['patients'] = [];
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle "Add Patient" (but do not schedule yet)
if (isset($_POST['addPatient'])) {
    $name         = trim($_POST['name'] ?? '');
    $arrival_time = floatval($_POST['arrival_time'] ?? 0);
    $burst_time   = floatval($_POST['burst_time'] ?? 0);
    $priority     = intval($_POST['priority'] ?? 1);

    // Add the patient to the session
    $_SESSION['patients'][] = [
        'name'         => $name,
        'arrival_time' => $arrival_time,
        'burst_time'   => $burst_time,
        'priority'     => $priority
    ];

    // Redirect to avoid re-post on refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
