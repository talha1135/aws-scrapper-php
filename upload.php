<?php
// upload.php
require_once 'config.php';
require_once 'functions.php';
require 'vendor/autoload.php';  // Ensure PhpSpreadsheet is installed

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $fileName = basename($file['name']);
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);

    // Validate file type and size
    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        echo json_encode(['success' => false, 'message' => 'Only .xlsx files are allowed.']);
        exit;
    }
    if ($fileSize > MAX_FILE_SIZE) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds 10MB limit.']);
        exit;
    }

    // Ensure processed_files directory exists
    if (!file_exists(PROCESSED_FILES_DIR)) {
        mkdir(PROCESSED_FILES_DIR, 0777, true); // Create the directory with proper permissions
    }

    // Move uploaded file to the upload directory
    $uploadPath = UPLOAD_DIR . uniqid() . '_' . $fileName;
    if (move_uploaded_file($fileTmpName, $uploadPath)) {
        try {
            // Load the Excel file
            $spreadsheet = new Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            $worksheet->setCellValue('A1', 'ASIN');
            $worksheet->setCellValue('B1', 'EU Responsible Name');
            $worksheet->setCellValue('C1', 'EU Responsible Address');
            $worksheet->setCellValue('D1', 'EU Responsible Email');
            $worksheet->setCellValue('E1', 'Manufacturer Name');
            $worksheet->setCellValue('F1', 'Manufacturer Address');
            $worksheet->setCellValue('G1', 'Manufacturer Contact');

            // Fetch ASINs from the Excel file (Assuming ASINs are in column B)
            $asins = [];
            $highestRow = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploadPath)->getActiveSheet()->getHighestRow();

            for ($row = 1; $row <= $highestRow; $row++) {
                $asin = \PhpOffice\PhpSpreadsheet\IOFactory::load($uploadPath)->getActiveSheet()->getCell('B' . $row)->getValue();
                if (!empty($asin)) {
                    $asins[] = $asin; // Save ASINs in an array
                }
            }

            // Process each ASIN to fetch additional details
            $rowNum = 2; // Start from the second row for the data
            foreach ($asins as $asin) {
                if (!$asin) continue;

                $headers = [
                    'accept' => 'text/html, application/json',
                    'accept-language' => 'en-GB,en;q=0.9,be;q=0.8,ur;q=0.7',
                    'content-type' => 'application/json',
                    'device-memory' => '8',
                    'downlink' => '4.25',
                    'dpr' => '2',
                    'ect' => '4g',
                    'priority' => 'u=1, i',
                    'rtt' => '250',
                    'sec-ch-device-memory' => '8',
                    'sec-ch-dpr' => '2',
                    'sec-ch-ua' => '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
                    'sec-ch-ua-mobile' => '?1',
                    'sec-ch-ua-platform' => '"Android"',
                    'sec-ch-ua-platform-version' => '"6.0"',
                    'sec-ch-viewport-width' => '1145',
                    'sec-fetch-dest' => 'empty',
                    'sec-fetch-mode' => 'cors',
                    'sec-fetch-site' => 'same-origin',
                    'viewport-width' => '1145',
                    'x-amz-acp-params' => 'tok=FBsk2BFo33RUH3sujiaU_dkdakUcEBnthvUxK3jaTj4;ts=1734623286395;rid=YPAQAPMK7HS057YPN4AD;d1=711;d2=0',
                    'x-amz-amabot-click-attributes' => 'disable',
                    'x-requested-with' => 'XMLHttpRequest',
                    'cookie' => 'session-id=261-5758951-0539711; session-id-time=2082787201l; i18n-prefs=EUR; lc-acbde=en_GB; sp-cdn="L5Z9:PK"; ubid-acbde=261-5393323-8128104; session-token=RVuGuCOz7rQrxfHb0cosNpD+u0bC7roD/2RaAnDtCXh9SGiSIzUEOGPNsdMo2/H607FyEYsyMy+zh8u/i3tXuhqUwki7bkMx1KYf8OFrr2SJsalca8qxe10aZmm1dq7UEZS1hA2CdN9EWE2sQGmHnBWb84YWuoPtFhBCv5BZGpWM42S8PYSiGlorZaav0JYEgUqVWCpJZpB13sq6Guy8C9wIrEjHGn2EtYaCj8PQiyZpQTF7qHQub3QSq517SaSOk+j8adBQPOeCOakcSgveJjTU/9y6sOi00KHadgZG4/x7rs5jm+ItnQBK1JoS81IGX2nsX4gCLycCjInxx9FUXE17K9oU4wil',
                    'Referer' => 'https://www.amazon.de/dp/B0BJ1Q3HWZ?th=1',
                    'Referrer-Policy' => 'strict-origin-when-cross-origin'
                ];


                // Create a Guzzle client
                $client = new Client();

                try {
                    $response = $client->post(AMAZON_API_URL, [
                        'json' => ['asin' => $asin],
                        'headers' => $headers
                    ]);

                    // Parse the response
                    $html = $response->getBody()->getContents();
                    $crawler = new Crawler($html);

                    // Extract EU Responsible Person details
                    $euResponsiblePerson = [
                        'name' => safe_get_text($crawler->filter('#buffet-sidesheet-mobile-rsp-content .a-box .a-box-inner .a-size-base.a-text-bold')->first()->getNode(0)),
                        'address' => implode(', ', [
                            safe_get_text($crawler->filter('#buffet-sidesheet-mobile-rsp-content .a-box .a-box-inner .a-list-item')->eq(1)->getNode(0)),
                            safe_get_text($crawler->filter('#buffet-sidesheet-mobile-rsp-content .a-box .a-box-inner .a-list-item')->eq(2)->getNode(0)),
                            safe_get_text($crawler->filter('#buffet-sidesheet-mobile-rsp-content .a-box .a-box-inner .a-list-item')->eq(3)->getNode(0))
                        ]),
                        'email' => safe_get_text($crawler->filter('#buffet-sidesheet-mobile-rsp-content .a-box .a-box-inner .a-spacing-top-small .a-list-item')->first()->getNode(0))
                    ];

                    // Extract Manufacturer details
                    $manufacturerInfo = [
                        'name' => safe_get_text($crawler->filter('#buffet-sidesheet-mobile-manufacturer-content .a-box .a-box-inner .a-size-base.a-text-bold')->first()->getNode(0)),
                        'address' => implode(', ', [
                            safe_get_text($crawler->filter('#buffet-sidesheet-mobile-manufacturer-content .a-box .a-box-inner .a-list-item')->eq(0)->getNode(0)),
                            safe_get_text($crawler->filter('#buffet-sidesheet-mobile-manufacturer-content .a-box .a-box-inner .a-list-item')->eq(1)->getNode(0)),
                            safe_get_text($crawler->filter('#buffet-sidesheet-mobile-manufacturer-content .a-box .a-box-inner .a-list-item')->eq(2)->getNode(0))
                        ]),
                        'contact' => safe_get_text($crawler->filter('#buffet-sidesheet-mobile-manufacturer-content .a-box .a-box-inner .a-list-item')->eq(3)->getNode(0))
                    ];

                    // Populate data into Excel
                    $worksheet->setCellValue('A' . $rowNum, $asin);
                    $worksheet->setCellValue('B' . $rowNum, $euResponsiblePerson['name']);
                    $worksheet->setCellValue('C' . $rowNum, $euResponsiblePerson['address']);
                    $worksheet->setCellValue('D' . $rowNum, $euResponsiblePerson['email']);
                    $worksheet->setCellValue('E' . $rowNum, $manufacturerInfo['name']);
                    $worksheet->setCellValue('F' . $rowNum, $manufacturerInfo['address']);
                    $worksheet->setCellValue('G' . $rowNum, $manufacturerInfo['contact']);

                    // Increment row number
                    $rowNum++;
                } catch (\Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Error fetching ASIN details: ' . $e->getMessage()]);
                    exit;
                }
            }

            // Save the Excel file
            $processedFileName = uniqid() . '.xlsx';
            $writer = new Xlsx($spreadsheet);
            $writer->save(PROCESSED_FILES_DIR . $processedFileName);

            // Return file name to user
            echo json_encode(['success' => true, 'file' => $processedFileName]);
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error reading the Excel file.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
}
