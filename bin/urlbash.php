<?php


echo "Login... ";

if(!getenv('BASH_PASSWORD')) exit('Please set BASH_PASSWORD env first');

$ch = curl_init();
curl_setopt ($ch, CURLOPT_COOKIEJAR, '/tmp/ms_cookies'); 
curl_setopt ($ch, CURLOPT_COOKIEFILE, '/tmp/ms_cookies'); 

curl_setopt($ch, CURLOPT_URL, $argv[2].'/api/v1/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['username' => $argv[1], 'password' => getenv('BASH_PASSWORD')]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 0);

$login = curl_exec($ch);
$login = json_decode($login);
#var_dump($login);
$login = !is_null($login) && $login->success;
echo $login ? " OK" : " FAIL";
echo PHP_EOL;

if(!$login) exit();

$ch = curl_init();
curl_setopt ($ch, CURLOPT_COOKIEJAR, '/tmp/ms_cookies'); 
curl_setopt ($ch, CURLOPT_COOKIEFILE, '/tmp/ms_cookies'); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

for($i = 900; $i <= $argv[3]; $i++) {
    echo $i.' ';
    curl_setopt($ch, CURLOPT_URL, $argv[2].'/items/tickets?viewOnly=1&id='.$i);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    echo curl_exec($ch) ? " OK" : " FAIL";
    echo PHP_EOL;
}

