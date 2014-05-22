<?php

// Connect to the api
\tabs\api\client\ApiClient::factory(
    "http://yourbrandcode.api.carltonsoftware.co.uk",
    'apikey',
    'secret'
);

try {
    $apicompanies = \tabs\api\utility\Utility::getAllBrands();
    $brands = array();
    foreach ($apicompanies as $company) {
        $brands[$company['brandcode']] = $company['name'];
    }
} catch (Exception $ex) {
    die($ex->getMessage());
}