<?php

require("config.php");

$temperature = filter_var($_GET['temperature'], FILTER_VALIDATE_FLOAT);
$humidity = filter_var($_GET['humidity'], FILTER_VALIDATE_FLOAT);

// Le champ battery_level est utile pour mon circuit d'origine avec l'esp8266.
// Vous pouvez le retirer et adapter le code (dans ce cas, pensez a modifier aussi index.php)
$battery_level = filter_var($_GET['battery_level'], FILTER_VALIDATE_INT);

date_default_timezone_set('Europe/Zurich');

$today = date('Y-m-d');
$fp = fopen('output/' . $today . '.csv', 'a');
$ts = date('c');

if (!$fp) {
    die('Could not open file!');
}
$fields = [$today, $ts, $temperature, $humidity, $battery_level];
fputcsv($fp, $fields);
fclose($fp);

// Refresh DataWrapper chart
$header = array(
    'Accept: */*',
    'Authorization: Bearer ' . $dw_key
);

$ch1 = curl_init();
$ch2 = curl_init();

curl_setopt($ch1, CURLOPT_URL, 'https://api.datawrapper.de/v3/charts/' . $temp_chart_id . '/data/refresh');
curl_setopt($ch1, CURLOPT_POST, 1);
curl_setopt($ch1, CURLOPT_HEADER, 1);
curl_setopt($ch1, CURLOPT_HTTPHEADER, $header);
curl_setopt($ch2, CURLOPT_URL, 'https://api.datawrapper.de/v3/charts/' . $hum_chart_id . '/data/refresh');
curl_setopt($ch2, CURLOPT_POST, 1);
curl_setopt($ch2, CURLOPT_HEADER, 1);
curl_setopt($ch2, CURLOPT_HTTPHEADER, $header);

$mh = curl_multi_init();
curl_multi_add_handle($mh, $ch1);
curl_multi_add_handle($mh, $ch2);

do {
    $status = curl_multi_exec($mh, $active);
    if ($active) {
        curl_multi_select($mh);
    }
} while ($active && $status == CURLM_OK);

curl_multi_remove_handle($mh, $ch1);
curl_multi_remove_handle($mh, $ch2);
curl_multi_close($mh);
