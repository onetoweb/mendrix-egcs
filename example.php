<?php

require 'vendor/autoload.php';

use Onetoweb\Mendrix\Egcs\Client;
use Onetoweb\Mendrix\Egcs\Token;

session_start();

// client parameters
$clientId = 42;
$clientSecret = 'client_secret';

// setup client
$client = new Client($clientId, $clientSecret);

// set token callback to store token
$client->setUpdateTokenCallback(function(Token $token) {
    
    // store token
    $_SESSION['token'] = [
        'value' => $token->getValue(),
        'expires' => $token->getExpires(),
    ];
    
});

// set rateLimit callback
$client->setRateLimitCallback(function(int $limit, int $remaining) {
    
    // check remaining ratelimit
    if ($remaining == 0) {
        
        // sleep 2 minutes
        sleep(120);
    }
    
});

// load token from storage
if (isset($_SESSION['token'])) {
    
    $token = new Token(
        $_SESSION['token']['value'],
        $_SESSION['token']['expires']
    );
    
    $client->setToken($token);
    
}


// create order
$order = $client->post('orders', [
    'Contact' => '',
    'Notes' => '',
    'GoodList' => [[
        'Packing' => '',
        'Barcode' => '',
        'Comments' => '',
        'Depth' => 0.1,
        'Height' => 0.1,
        'Width' => 0.1,
        'Parts' => 3,
        'Volume' => 0.1,
        'VolumeWeight' => 0.1,
        'ArticleWeight' => 0.1,
        'Weight' => 0.1
    ]],
    'PickUp' => [
        'Instructions' => '',
        'ReferenceOur' => '',
        'ReferenceYour' => '',
        'Requested' => [
            'DateTimeBegin' => '2022-01-01T12:00:00',
            'DateTimeEnd' => '2022-01-01T13:00:00'
        ],
    ],
    'Delivery' => [
        'Address' => [
            'Name' => '',
            'Premise' => '',
            'Street' => '',
            'Number' => '',
            'PostalCode' => '',
            'Place' => '',
            'Country' => 'Nederland',
            'CountryCode' => 'NL'
        ],
        'ContactName' => '',
        'Instructions' => '',
        'ReferenceOur' => '',
        'ReferenceYour' => '',
        'Connectivity' => [
            'Phone' => '',
            'Mobile' => '',
            'Email' => '',
            'Web' => ''
        ],
        'Requested' => [
            'DateTimeBegin' => '2022-01-01T12:00:00',
            'DateTimeEnd' => '2022-01-01T13:00:00'
        ]
    ]
]);

// get user
$user = $client->get('user');

// get serverdate
$serverdate = $client->get('serverdate');

// get orders
$orders = $client->get('orders', [
    'from' => '2022-01-01',
    'to' => '2022-02-01',
    'page' => 1,
    'limit' => 10,
    'clientNo' => -1,
    'operatorId' => -1
]);

// get order
$order = $client->get('order', [
    'orderId' => 42
]);

// orderlabel
$orderlabel = $client->get('orderlabel', [
    'orderId' => 42
]);

// save order label to file
$filename = 'path/to/'.$orderlabel['filename'];
file_put_contents($filename, base64_decode($orderlabel['data']));

// get order track and trace
$ordertracktrace = $client->get('ordertracktrace', [
    'orderId' => 42,
    'taskType' => 'all', //all, delivery or pickup
]);
