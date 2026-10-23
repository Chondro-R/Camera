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
        
        // সরাসরি টেলিগ্রামের মেইন API ব্যবহার করা হলো
        $telegramUrl = "https://api.telegram.org/bot{$botToken}/sendPhoto";
        
        $postFields = [
            'chat_id' => $chatId,
            'caption' => "New image captured: " . $fileName,
            'photo'   => new CURLFile(realpath($filePath))
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $telegramUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($result) {
            $jsonResult = json_decode($result, true);
            if (isset($jsonResult['ok']) && $jsonResult['ok'] === true) {
                echo "ছবি সফলভাবে টেলিগ্রাম বটে পাঠানো হয়েছে!";
            } else {
                echo "সার্ভারে সেভ হয়েছে, কিন্তু টেলিগ্রামে পাঠাতে সমস্যা হয়েছে: " . ($jsonResult['description'] ?? 'Unknown error');
            }
        } else {
            echo "cURL এরর: " . $curlError;
        }

    } else {
        echo "ছবি সেভ করতে ব্যর্থ হয়েছে।";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camera Capture & Telegram Send</title>
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
        button {
            margin-top: 15px;
            padding: 10px 20px;
            font-size: 16px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        #status {
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h2>ক্যামেরা থেকে ছবি তুলো</h2>
    
    <video id="video" autoplay playsinline></video><br>
    
    <button id="capture-btn">ছবি তুলুন ও পাঠান</button>
    
    <canvas id="canvas" style="display:none;"></canvas>
    
    <div id="status"></div>

    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const captureBtn = document.getElementById('capture-btn');
        const statusDiv = document.getElementById('status');

        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                video.srcObject = stream;
            })
            .catch(err => {
                console.error("ক্যামেরা এক্সেস পাওয়া যায়নি: ", err);
                statusDiv.innerText = "ক্যামেরা পারমিশন পাওয়া যায়নি বা দেওয়া হয়নি!";
                statusDiv.style.color = "red";
            });

        captureBtn.addEventListener('click', () => {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const imageData = canvas.toDataURL('image/png');

            statusDiv.innerText = "ছবি প্রসেস ও টেলিগ্রামে পাঠানো হচ্ছে...";
            statusDiv.style.color = "blue";

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'image=' + encodeURIComponent(imageData)
            })
            .then(response => response.text())
            .then(result => {
                statusDiv.innerText = result;
                statusDiv.style.color = "green";
            })
            .catch(error => {
                console.error('Error:', error);
                statusDiv.innerText = "পাঠাতে সমস্যা হয়েছে!";
                statusDiv.style.color = "red";
            });
        });
    </script>
</body>
</html>
