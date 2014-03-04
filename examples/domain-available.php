<?php
// Require ODR API demo class
require_once '../Api/Odr.php';

// Configuration array, with user API Keys
$config = array(
    'api_key'    => '#API_KEY#',
    'api_secret' => '#API_SECRET#',
);

// Create new instance of API demo class
$demo = new Api_Odr($config);

// Login into API
$demo->login();

$loginResult = $demo->getResult();

if ($loginResult['status'] === 'error') {
    echo 'Can\'t login, reason - '. $loginResult['response'];

    exit(1);
}

// Check if target domain is available for registration or not
$demo->checkDomain('test.nl');

// Get result of request
$result = $demo->getResult();

if ($result['status'] !== 'success') {
    echo 'Following error occured: '. $result['response'];

    exit();
}

$result = $result['response'];

if ($result['available'] === true) {
    // Domain is available for registration
    echo 'Domain "test.nl" is available';

    // Do something with available domain

    exit(1);
}

// D'oh, someone already took this domain!
echo 'Domain "test.nl" is not available';