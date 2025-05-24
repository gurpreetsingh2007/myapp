<?php
use phpseclib3\Crypt\RSA;
function echoPublicKey()
{
    $publicKey = file_get_contents(__DIR__ . '/public.key');
    echo json_encode(['success' => true, 'message' => $publicKey]);
}
function loadPrivateKeyFromFile()
{
    $privateKey = file_get_contents(__DIR__ . '/private.key');
    return RSA::loadPrivateKey($privateKey);
}
function loadPublicKeyFromFile()
{
    $publicKey = file_get_contents(__DIR__ . '/public.key');
    return RSA::loadPublicKey($publicKey);
}