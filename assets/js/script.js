// script.js
document.getElementById('file').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    // Show progress bar
    document.getElementById('progress-container').style.display = 'block';
    const progressBar = document.getElementById('progress-bar');
    let progress = 0;

    // AJAX upload with progress tracking
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'upload.php', true);
    
    xhr.upload.addEventListener('progress', function (e) {
        if (e.lengthComputable) {
            progress = (e.loaded / e.total) * 100;
            progressBar.style.width = progress + '%';
        }
    });

    xhr.onload = function () {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                document.getElementById('message').innerHTML = 
                    `File processed successfully. <a href="processed_files/${response.file}" download>Download here</a>`;
            } else {
                document.getElementById('message').innerHTML = 'Error: ' + response.message;
            }
        }
    };

    xhr.send(formData);
});
