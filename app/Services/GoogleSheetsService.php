<?php

namespace App\Services;

class GoogleSheetsService
{
    public function pushToGoogleSheets(
        string $profileUrl,
        array  $videoLikes,
        int    $totalViewCount,
        string $profileLikeCount,
        string $followerCount,
        array  $videoUrls,
        int    $videoCount): void
    {
        $serviceAccountKeyPath = $_ENV['SERVICE_ACCOUNT_KEY_PATH'];
        $spreadsheetId = $_ENV['GOOGLE_SHEETS_SPREADSHEET_ID'];

        $values = [
            [$profileUrl, $videoLikes, $totalViewCount, $profileLikeCount, $followerCount],
        ];

        $range = 'Lapa1!A1:Z1000';

        $client = new \Google_Client();
        $client->setAuthConfig($serviceAccountKeyPath);
        $client->addScope(\Google_Service_Sheets::SPREADSHEETS);
        $sheets = new \Google_Service_Sheets($client);

        $body = new \Google_Service_Sheets_ValueRange(['values' => $values]);
        $params = [
            'valueInputOption' => 'RAW',
        ];

        $sheets->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
    }
}
