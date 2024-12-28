# Project Name: Excel File Processing with ASIN Data Retrieval

## Overview
This project is a PHP-based application that allows users to upload an Excel file containing Amazon ASINs, fetch relevant product data from an external API (like Amazon), and generate a new Excel file with updated product information. The application uses Core PHP for file handling, PHPSpreadsheet for Excel file manipulation, GuzzleHTTP for HTTP requests, and Symfony DOM Crawler for web scraping.

## Problem Being Solved
This project automates the process of:
- Uploading an Excel file containing ASINs.
- Fetching product details such as name, price, description, etc., from an external API (such as Amazon).
- Generating a new Excel file with the updated data for use in inventory tracking, reporting, or analytics.

## Why Use Core PHP?
We have chosen Core PHP for the following reasons:
- **Control**: Provides full control over the logic and structure.
- **Simplicity**: No complex framework needed for the scope of this project.
- **Performance**: No overhead from unnecessary libraries or frameworks.

## Packages Used
- **phpoffice/phpspreadsheet**: Used to read and write Excel files.
- **guzzlehttp/guzzle**: Used to send HTTP requests to an external API (e.g., Amazon) for retrieving ASIN data.
- **symfony/dom-crawler**: Used to parse and extract data from web pages for ASIN information.
- **symfony/css-selector**: Required by Symfony Dom Crawler for CSS selector-based querying.

## Code Structure and Explanation

### 1. composer.json
Your `composer.json` file contains the dependencies for the project, which are installed via Composer.

{
    "require": {
        "phpoffice/phpspreadsheet": "^3.7",
        "guzzlehttp/guzzle": "^7.9",
        "symfony/dom-crawler": "^7.2",
        "symfony/css-selector": "^7.2"
    }
}
This file lists the necessary libraries for handling Excel files, HTTP requests, and web scraping.

###  2. File Upload Form (index.php)
This is the simple HTML form used to upload the Excel file containing ASINs.
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Excel File</title>
</head>
<body>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <label for="file">Upload Excel File:</label>
        <input type="file" name="file" id="file" accept=".xlsx, .xls">
        <button type="submit">Upload</button>
    </form>
</body>
</html>

- **sAction URL**: The form submits the file to upload.php, where the file will be processed.
- **sFile Input**: The file input allows users to upload .xlsx or .xls files.
- **sSubmit Button**: Triggers the file upload.

### 3. File Processing (upload.php)
This PHP script handles file upload, processes the Excel file, fetches ASIN data, and generates a new Excel file.
<?php
require_once 'vendor/autoload.php';

// Ensure necessary directories exist
if (!is_dir('uploads')) mkdir('uploads', 0775, true);
if (!is_dir('processed_files')) mkdir('processed_files', 0775, true);

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadedFile = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    
    // Move file to 'uploads/' directory
    move_uploaded_file($uploadedFile, 'uploads/' . $fileName);

    // Load the uploaded Excel file
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('uploads/' . $fileName);
    $sheet = $spreadsheet->getActiveSheet();
    
    // Iterate through the rows to process ASINs (Assume ASINs are in the second column)
    foreach ($sheet->getRowIterator() as $rowIndex => $row) {
        $cellValue = $sheet->getCellByColumnAndRow(2, $rowIndex)->getValue(); // ASIN in the 2nd column
        if ($cellValue) {
            // Fetch product data for each ASIN
            $productData = getProductData($cellValue);

            // Update the Excel sheet with the fetched product data
            $sheet->setCellValueByColumnAndRow(3, $rowIndex, $productData['name']);
            $sheet->setCellValueByColumnAndRow(4, $rowIndex, $productData['price']);
            $sheet->setCellValueByColumnAndRow(5, $rowIndex, $productData['description']);
        }
    }

    // Save the processed file to 'processed_files/' directory
    $newFileName = 'processed_files/' . uniqid() . '.xlsx';
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($newFileName);

    // Provide a link for the user to download the processed file
    echo "File processed successfully. <a href='$newFileName'>Download Here</a>";
}

// Function to fetch product data using Guzzle (can be extended to scrape Amazon or use API)
function getProductData($asin) {
    // Example API request to get product data (replace with your own API or scraping logic)
    $client = new \GuzzleHttp\Client();
    try {
        $response = $client->get("https://api.example.com/asin/$asin");
        $data = json_decode($response->getBody(), true);
        return [
            'name' => $data['product_name'],
            'price' => $data['product_price'],
            'description' => $data['product_description'],
        ];
    } catch (\Exception $e) {
        return [
            'name' => 'Not Found',
            'price' => 'N/A',
            'description' => 'No description available',
        ];
    }
}
?>

- **Explanation**:
- **Directory Setup**: The code checks and creates the uploads and processed_files directories if they don't exist.
if (!is_dir('uploads')) mkdir('uploads', 0775, true);
if (!is_dir('processed_files')) mkdir('processed_files', 0775, true);
- **File Upload Handling**: The uploaded file is saved in the uploads directory.
move_uploaded_file($uploadedFile, 'uploads/' . $fileName);
Loading the Excel File: The file is loaded using PHPSpreadsheet to manipulate the data.

$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load('uploads/' . $fileName);
- **Processing Each Row**: We loop through each row in the spreadsheet, fetching the ASIN from the second column (index 2) and calling the getProductData() function to retrieve data for the ASIN.

$cellValue = $sheet->getCellByColumnAndRow(2, $rowIndex)->getValue();
- **Product Data Retrieval**: The getProductData() function sends a request to an external API using Guzzle and retrieves product details. If no data is found, default values are returned.

$client = new \GuzzleHttp\Client();
$response = $client->get("https://api.example.com/asin/$asin");
Updating Excel Sheet: The product data (name, price, description) is written into the Excel sheet in columns 3, 4, and 5.

$sheet->setCellValueByColumnAndRow(3, $rowIndex, $productData['name']);
$sheet->setCellValueByColumnAndRow(4, $rowIndex, $productData['price']);
$sheet->setCellValueByColumnAndRow(5, $rowIndex, $productData['description']);
Saving the Processed File: The updated Excel file is saved in the processed_files directory with a unique file name.

$newFileName = 'processed_files/' . uniqid() . '.xlsx';
Providing Download Link: A link to the processed file is provided for the user to download.

echo "File processed successfully. <a href='$newFileName'>Download Here</a>";
Guidelines for Running the Code
### Prerequisites
PHP 7.4 or higher.
Composer installed to manage dependencies.
Setup
Clone the repository:


git clone https://github.com/talha1135/aws-scrapper-php
cd your-repository-name
Install dependencies:


composer install
Set up directories for uploads and processed files:


mkdir uploads
mkdir processed_files
Set correct permissions:


chmod -R 775 uploads/
chmod -R 775 processed_files/

-**serve code** run php -S localhost:8000 
### Conclusion
This project provides a fully automated solution to process ASINs from Excel files, retrieve product data from external APIs, and generate updated reports in Excel format. It leverages PHP’s flexibility, powerful packages, and external APIs to streamline the workflow.