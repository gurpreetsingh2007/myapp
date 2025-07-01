<?php
function sendServerList()
{
    $url = 'http://127.0.0.1:8081/serverList';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Curl error: " . curl_error($ch) . "\n";
        curl_close($ch);
        return;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "Request failed with HTTP code $httpCode\n";
        return;
    }

    echo $response;
}