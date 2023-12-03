# TikTok Scraper

This PHP script uses Selenium WebDriver to scrape data from TikTok profiles and stores the information in a Google Spreadsheet.

## Screenshots
![Screenshot 1](https://github.com/xanthopsia/TikTokScraper/blob/main/spreadsheets.png)\

## Setup
1. Install PHP, Composer and ChromeDriver on your machine
2. Run `composer install` to install the required dependencies

## Usage
1. Run ChromeDriver using `chromedriver --port=1337`
2. Add path to service key and spreadsheet id for Google Sheets in your .env file
3. If needed, add URLs to index.php file and run the script using `php index.php`

