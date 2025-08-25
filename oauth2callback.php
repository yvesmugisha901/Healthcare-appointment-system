<?php
session_start();
require __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/credentials.json');
$client->addScope(Google_Service_Calendar::CALENDAR);
$client->setRedirectUri('http://localhost/google_oauth/oauth2callback.php');

if (!isset($_GET['code'])) {
    // If no code, redirect to Google auth
    header('Location: calendar.php');
    exit;
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error'])) {
    die('Error fetching access token: ' . $token['error']);
}

// Save token in session
$_SESSION['google_access_token'] = $token;

// Redirect back to calendar
header('Location: calendar.php');
exit;
