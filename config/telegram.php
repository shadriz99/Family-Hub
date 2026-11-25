<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Used for the Family Hub lightweight expense tracking & OCR Telegram bot.
    | Zero external LLMs required; completely local parsing on ODROID.
    |
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),

    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),

    'bot_username' => env('TELEGRAM_BOT_USERNAME', 'FamilyHubBot'),

    'api_url' => 'https://api.telegram.org/bot',
];
