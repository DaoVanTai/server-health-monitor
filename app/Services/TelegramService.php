<?php

namespace App\Services;

class TelegramService
{
    public static function sendMessage($message)
    {
        $token = "8578604024:AAFkqh8-rHKmMjZL_aV6KzTXs2WLupjTcV4";
        $chatId = "1735680363";
        
        // Gọi API Telegram với định dạng HTML
        $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chatId&parse_mode=HTML&text=" . urlencode($message);
        @file_get_contents($url);
    }
}
