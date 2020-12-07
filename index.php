<?php

date_default_timezone_set('Asia/Tokyo');
require_once __DIR__ . '/vendor/autoload.php';

use DOMWrap\Document;
use GuzzleHttp\Client;
use Dotenv\Dotenv;

$race_date ="2020";
$race_course_num="06";
$race_info ="03";
$race_count ="05";
$race_no="11";
$url = "https://race.netkeiba.com/race/result.html?race_id=" . $race_date . $race_course_num . $race_info . $race_count . $race_no . "&rf=race_list";

$client = new Client;

$response = $client->get($url);
$html = (string) $response->getBody();

//取得するページの列数
$raceRow = 15;

$doc = new Document;
$node = $doc->html($html);
$hourseCount = $doc->find('.Horse_Name')->count();
$nodes = $doc->find('.HorseList > td');
$chart = [];
$nodes->each(function($node) use (&$chart){
    $chart[] = $node->nodeValue;
});

// spreadsheet
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

define('CREDENTIALS_PATH', $_ENV["SERVICE_KEY_JSON"]);
define('SPREADSHEET_ID',   $_ENV["SPREADSHEET_ID"]);

putenv('GOOGLE_APPLICATION_CREDENTIALS='.dirname(__FILE__).'/'.CREDENTIALS_PATH);
$client = new Google_Client();
$client->useApplicationDefaultCredentials();
$client->addScope(Google_Service_Sheets::SPREADSHEETS);
$client->setApplicationName('test');

$service = new Google_Service_Sheets($client);

$multChart = [];
for($i=0; $i<count($chart); $i++){
    $index = $i/$raceRow;
    $multChart[$index][] = $chart[$i];
}

for($i=0; $i<=$raceRow; $i++) {
    $value = new Google_Service_Sheets_ValueRange();
    $value->setValues([ 'values' => $multChart[$i] ]);
    $rowNum = $i + 2;
    $response = $service->spreadsheets_values->update(SPREADSHEET_ID, 'シート1!A' . $rowNum, $value, [ 'valueInputOption' => 'USER_ENTERED' ] );
}

// A1:D5 の範囲を取得
// $response = $service->spreadsheets_values->get(SPREADSHEET_ID, 'シート1!A1:D5');
// foreach ($response->getValues() as $index => $cols) {
//     echo sprintf('#%d >> "%s"', $index+1, implode('", "', $cols)).PHP_EOL;
// }
