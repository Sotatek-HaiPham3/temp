<?php

namespace App\Console\Commands;

use App\Helpers\GoogleSheet;
use Illuminate\Console\Command;
use Google_Client;
use Google_Service_Sheets;
use Exception;
use App\Utils;
use App\Consts;
use Log;

class GoogleSheetGameData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create game data from google sheet';

    // https://docs.google.com/spreadsheets/d/1WjaSC_tMiE2zZ3M1myxrAXCshQp0cf6oE3Gdbcwv1Og
    const SHEET_ID = '1WjaSC_tMiE2zZ3M1myxrAXCshQp0cf6oE3Gdbcwv1Og';

    const GAMES_DATA_FILE = 'masterdata/games-meta.json';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getGoogleClient()
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
        $client->setAuthConfig(storage_path('credentials/credentials-google-sheet.json'));
        $client->setAccessType('offline');

        $tokenPath = storage_path('credentials/google-sheet-token.json');
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }

            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }

        return $client;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = $this->getGoogleClient();
        $service = new Google_Service_Sheets($client);
        $spreadsheetId = static::SHEET_ID;
        $range = "meta!A1:C";
        $metadata = $service->spreadsheets_values->get($spreadsheetId, $range)->getValues();
        $allData = [];

        foreach ($metadata as $row) {
            $tableName = $row[0];
            $rowCount = $row[1];
            $colCount = $row[2];
            $tableData = $this->processSheet($service, $spreadsheetId, $tableName, $rowCount, $colCount);
            $allData[$tableName] = $tableData;
        }

        $filename = static::GAMES_DATA_FILE;
        $file = fopen(storage_path($filename), 'w');

        ksort($allData);
        $jsonData = json_encode($allData, JSON_PRETTY_PRINT);
        fwrite($file, $jsonData);
        fclose($file);
    }

    private function processSheet($service, $spreadsheetId, $tableName, $rowCount, $colCount)
    {
        $result = [];
        $fieldsKeyByIndex = [];
        $fieldsKeyByName = [];
        printf ("Processing table %s: %s rows and %s columns\n", $tableName, $rowCount, $colCount);
        $range = $tableName.'!A1:'.$rowCount;
        $data = $service->spreadsheets_values->get($spreadsheetId, $range)->getValues();
        printf("Rows to process: %s\n", count($data));

        foreach ($data as $rowIndex => $row) {
            if ($rowIndex == 0) {
                for ($colIndex = 0; $colIndex < $colCount; $colIndex++) {
                    if (isset($row[$colIndex])) {
                        $fieldName = $row[$colIndex];
                        $fieldsKeyByIndex[$colIndex] = $fieldName;
                        $fieldsKeyByName[$fieldName] = $colIndex;
                    }
                }
                continue;
            }

            $rowData = [];
            for ($colIndex = 0; $colIndex < $colCount; $colIndex++) {
                if (isset($fieldsKeyByIndex[$colIndex]) && isset($row[$colIndex])) {
                    $fieldName = $fieldsKeyByIndex[$colIndex];
                    if (empty($fieldName) || strpos($fieldName, '_localized_')) {
                        continue;
                    }

                    $fieldValue = $row[$colIndex];
                    if ($tableName === 'games' && $fieldName === 'logo') {
                        $fieldValue = Utils::saveFileFromDropBox($this->fixFileUrl($fieldValue), 'games', "{$rowData['slug']}-{$fieldName}", $this->getFileExtension($fieldValue));
                    }

                    if ($tableName === 'games' && $fieldName === 'thumbnail') {
                        $fieldValue = Utils::saveFileFromDropBox($this->fixFileUrl($fieldValue), 'games', "{$rowData['slug']}-{$fieldName}", $this->getFileExtension($fieldValue));
                    }

                    if ($tableName === 'games' && $fieldName === 'portrait') {
                        $fieldValue = Utils::saveFileFromDropBox($this->fixFileUrl($fieldValue), 'games', "{$rowData['slug']}-{$fieldName}", $this->getFileExtension($fieldValue));
                    }

                    $rowData[$fieldName] = $fieldValue;
                }
            }

            array_push($result, $rowData);
        }

        print "\n";
        return $result;
    }

    private function getFileExtension($fileUrl)
    {
        $start = strrpos($fileUrl, '.') + 1;
        $end = strrpos($fileUrl, '?');
        $length = $end - $start;
        return substr($fileUrl, $start, $length);
    }

    private function fixFileUrl($fileUrl)
    {
        return str_replace('?dl=0', '?dl=1', $fileUrl);
    }
}
