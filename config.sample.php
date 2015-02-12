<?php

$brandcode = 'zz';
if (filter_input(INPUT_GET, 'brandcode')) {
    $brandcode = filter_input(INPUT_GET, 'brandcode');
}

// Connect to the api
\tabs\api\client\ApiClient::factory(
   "http://{$brandcode}.api.carltonsoftware.co.uk",
    'key',
    'secret'
);

$brands = array(
    'zz' => 'Dummy'
);