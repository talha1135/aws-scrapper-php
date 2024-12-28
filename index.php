<?php
// index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <h1>Upload your Excel file</h1>
    <form action="upload.php" method="POST" enctype="multipart/form-data">
        <label for="file">Choose Excel file (.xlsx):</label>
        <input type="file" name="file" id="file" required>
        <button type="submit">Upload</button>
    </form>
    <div id="progress-container" style="display:none;">
        <div id="progress-bar"></div>
    </div>
    <div id="message"></div>
    <script src="assets/js/script.js"></script>
</body>
</html>
