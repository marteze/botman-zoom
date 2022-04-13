<?php

return [
    'zoom_bot_client_id' => env('ZOOM_BOT_CLIENT_ID'),
    'zoom_bot_client_secret' => env('ZOOM_BOT_CLIENT_SECRET'),
    'zoom_bot_jid' => env('ZOOM_BOT_JID'),
    'zoom_bot_verification_token' => env('ZOOM_BOT_VERIFICATION_TOKEN'),
    
    'zoom_api_key' => env('ZOOM_API_KEY'),
    'zoom_api_secret' => env('ZOOM_API_SECRET'),
    'zoom_api_verification_token' => env('ZOOM_API_VERIFICATION_TOKEN'),
    
    'zoom_actions_limit_display' => env('ZOOM_ACTIONS_LIMIT_DISPLAY', 1000),
    
    'send_message_secret' => env('SEND_MESSAGE_SECRET', ''),
];
