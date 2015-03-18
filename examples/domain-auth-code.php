<?php
// Require ODR API demo class
require_once '../Api/Odr.php';

// Configuration array, with user API Keys
$config = array(
    'api_key'    => '#API_KEY#',
    'api_secret' => '#API_SECRET#',
);

// ID of the domain you want to get auth code for
$domainId = '#DOMAIN_ID#';

// Create new instance of API demo class
$demo = new Api_Odr($config);

// Login into API
$demo->login();

$loginResult = $demo->getResult();

if ($loginResult['status'] === 'error') {
    echo 'Can\'t login, reason - '. $loginResult['response'];

    exit(1);
}

// Get auth code for domain ID
if (!is_numeric($domainId) || $domainId <= 0) {
    throw new Api_Odr_Exception('Domain ID must be a numeric and bigger than zero');
}

// Get result from request
$result = $demo->custom('/domain/auth-code/'. $domainId .'/', Api_Odr::METHOD_GET);

if ($result['is_error'] || $result['data']['status'] === 'error') {
    if ($result['is_error']) {
        echo 'Following error occured: '. $result['error_msg'];
    } else {
        echo 'Following error occured: '. $result['data']['response'];
    }

    exit(1);
}

$result = $result['data']['response'];

if (!empty($result['auth_code'])) {
    echo 'Auth code for domain is "'. $result['auth_code'] .'"';

    // Do something with auth code

    exit(1);
}

echo 'No auth code received';