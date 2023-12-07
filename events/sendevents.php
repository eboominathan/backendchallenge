<?php


function sendRequest($request)
{
    $body = json_encode($request);

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "http://localhost:8321/receive");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Length: ' . strlen($body),
            'Content-Type: application/json'
        ]
    );

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $ret = curl_exec($ch);

    curl_close($ch);

    return $ret;
}


$jsonData = file_get_contents(__DIR__ . '/events.json');

$data = json_decode($jsonData, true);

foreach ($data['requests'] as $request) {
    $ret = sendRequest($request);
    echo $ret . PHP_EOL;
}
