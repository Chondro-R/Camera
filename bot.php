<?php
$botToken = "8836334469:AAE6WOWEabXtkgCWVaIHhpMgTwAEQ3KxLdY";

// টেলিগ্রাম থেকে আসা আপডেট রিড করা
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

$chatId = $update["message"]["chat"]["id"] ?? null;
$text = $update["message"]["text"] ?? null;

if ($chatId && $text) {
    if (strpos($text, "/gen") === 0) {
        $parts = explode(" ", $text, 2);
        
        if (isset($parts[1]) && !empty(trim($parts[1]))) {
            $targetUrl = trim($parts[1]);
            
            // ইউনিক আইডি তৈরি
            $uniqueId = substr(md5(mt_rand()), 0, 6);
            
            $linksFile = 'links.json';
            $links = file_exists($linksFile) ? json_decode(file_get_contents($linksFile), true) : [];
            $links[$uniqueId] = $targetUrl;
            
            file_put_contents($linksFile, json_encode($links, JSON_PRETTY_PRINT));

            // লাইভ ডোমেইন লিংক তৈরি
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $finalLink = "$protocol://$host/?id=$uniqueId";

            $replyText = "✅ **নতুন লিংক সফলভাবে জেনারেট হয়েছে!**\n\n🎯 **টার্গেট সাইট:** $targetUrl\n🔗 **জেনারেটেড লিংক:**\n`$finalLink`";
        } else {
            $replyText = "দয়া করে একটি লিংক দিন। যেমন:\n`/gen https://example.com`";
        }

        // টেলিগ্রামে মেসেজ পাঠানো
        $url = "https://api.telegram.org/bot$botToken/sendMessage";
        $postData = [
            'chat_id' => $chatId,
            'text' => $replyText,
            'parse_mode' => 'Markdown'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
}
?>
