<?php

namespace App\Index;

use App\Models\TiktokProfile;
use App\Services\GoogleSheetsService;
use Dotenv\Dotenv;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

class TiktokScraper
{
    private RemoteWebDriver $driver;
    private array $scrapedData = [];

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        $serverUrl = 'http://localhost:1337';
        $this->driver = RemoteWebDriver::create($serverUrl, DesiredCapabilities::chrome());
    }

    public function scrapeTikTokProfiles(array $urls): void
    {
        $uniqueProfileUrls = [];

        foreach ($urls as $url) {
            $this->driver->get($url);
            $this->driver->manage()->timeouts()->pageLoadTimeout(30);
            $this->driver->wait(10, 500)->until(
                WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::cssSelector('div.tiktok-1as5cen-DivWrapper.e1cg0wnj1 a')
                )
            );

            $elements = $this->driver->findElements(WebDriverBy::cssSelector('div.tiktok-1as5cen-DivWrapper.e1cg0wnj1 a'));

            foreach ($elements as $element) {
                $href = $element->getAttribute('href');
                $matches = [];
                if (preg_match('/https:\/\/www\.tiktok\.com\/@([^\/]+)/', $href, $matches)) {
                    $username = $matches[1];
                    $profileUrl = "https://www.tiktok.com/@$username";
                    $uniqueProfileUrls[$profileUrl] = true;
                }
            }
        }

        foreach (array_keys($uniqueProfileUrls) as $profileUrl) {
            $this->driver->get($profileUrl);
            $this->driver->manage()->timeouts()->pageLoadTimeout(30);

            $this->driver->wait(10, 500)->until(
                WebDriverExpectedCondition::presenceOfElementLocated(
                    WebDriverBy::cssSelector('a[href*="/video/"]')
                )
            );

            $videoElements = $this->driver->findElements(WebDriverBy::cssSelector('a[href*="/video/"]'));
            $videoUrls = [];
            $videoCount = min(5, count($videoElements));

            for ($i = 0; $i < $videoCount; $i++) {
                $videoUrls[] = $videoElements[$i]->getAttribute('href');
            }

            $totalViewCount = 0;
            for ($i = 0; $i < $videoCount; $i++) {
                $viewCount = $this->driver->executeScript("
                    const viewCountElement = document.querySelector('#main-content-others_homepage > div > div.tiktok-833rgq-DivShareLayoutMain.ee7zj8d4 > div.tiktok-1qb12g8-DivThreeColumnContainer.eegew6e2 > div > div:nth-child(" . ($i + 1) . ") > div.tiktok-x6f6za-DivContainer-StyledDivContainerV2.eq741c50 > div > div > a > div > div.tiktok-11u47i-DivCardFooter.e148ts220 > strong');
                    return viewCountElement ? viewCountElement.innerText : null;
                ");
                $totalViewCount += $this->convertViewCount($viewCount);
            }

            $profileLikeCount = $this->driver->executeScript("
                const profileLikeCountElement = document.querySelector('#main-content-others_homepage > div > div.tiktok-1g04lal-DivShareLayoutHeader-StyledDivShareLayoutHeaderV2.enm41492 > h3 > div.tiktok-ntsum2-DivNumber.e1457k4r1 > strong');
                return profileLikeCountElement ? profileLikeCountElement.innerText : null;
            ");

            $followerCount = $this->driver->executeScript("
                const followerCountElement = document.querySelector('#main-content-others_homepage > div > div.tiktok-1g04lal-DivShareLayoutHeader-StyledDivShareLayoutHeaderV2.enm41492 > h3 > div:nth-child(2) > strong');
                return followerCountElement ? followerCountElement.innerText : null;
            ");

            $videoLikes = [];
            for ($i = 0; $i < $videoCount; $i++) {
                $videoUrl = $videoUrls[$i];

                $this->driver->get($videoUrl);
                $this->driver->manage()->timeouts()->pageLoadTimeout(30);
                $this->driver->wait(10, 500)->until(
                    WebDriverExpectedCondition::presenceOfElementLocated(
                        WebDriverBy::cssSelector('[data-e2e="like-count"]')
                    )
                );

                $likeCountElement = $this->driver->findElement(WebDriverBy::cssSelector('[data-e2e="like-count"]'));
                $likeCount = $likeCountElement->getText();

                $videoLikes[] = $likeCount;
            }

            $this->scrapedData[$profileUrl] = new TiktokProfile(
                $profileUrl,
                $totalViewCount,
                $profileLikeCount,
                $followerCount,
                $videoUrls,
                $videoCount,
                $videoLikes
            );
        }

        $this->pushToGoogleSheetsBatch();
        $this->closeDriver();
    }

    private function convertViewCount($viewCountString): float|int
    {
        $multiplier = 1;
        if (str_contains($viewCountString, 'K')) {
            $multiplier = 1000;
        }
        return (int)str_replace(['K', 'M'], ['', ''], $viewCountString) * $multiplier;
    }

    private function pushToGoogleSheetsBatch(): void
    {
        $googleSheetsService = new GoogleSheetsService();

        foreach ($this->scrapedData as $data) {
            $googleSheetsService->pushToGoogleSheets(
                $data->getProfileUrl(),
                (array)implode(', ', $data->getVideoLikes()),
                $data->getTotalViewCount(),
                $data->getProfileLikeCount(),
                $data->getFollowerCount(),
                $data->getVideoUrls(),
                $data->getVideoCount()
            );
        }
    }

    public function closeDriver(): void
    {
        $this->driver->quit();
    }
}
