<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = \App\Models\User::find(33); // A teacher
$token = $user->createToken('Test')->accessToken;

$ch = curl_init('http://127.0.0.1:8000/api/academic/my-offerings');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);
$response = curl_exec($ch);
echo $response;
