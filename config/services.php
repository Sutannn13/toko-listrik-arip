<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'bayargg' => [
        'base_url' => env('BAYARGG_BASE_URL', 'https://www.bayar.gg/api'),
        'api_key' => env('BAYARGG_API_KEY'),
        'webhook_secret' => env('BAYARGG_WEBHOOK_SECRET'),
        'webhook_tolerance_seconds' => (int) env('BAYARGG_WEBHOOK_TOLERANCE_SECONDS', 300),
        'webhook_replay_ttl_seconds' => (int) env('BAYARGG_WEBHOOK_REPLAY_TTL_SECONDS', 600),
        'callback_url' => env('BAYARGG_CALLBACK_URL'),
        'redirect_url' => env('BAYARGG_REDIRECT_URL'),
        'payment_method' => env('BAYARGG_PAYMENT_METHOD', 'qris'),
        'use_qris_converter' => filter_var(env('BAYARGG_USE_QRIS_CONVERTER', false), FILTER_VALIDATE_BOOLEAN),
        'timeout' => (int) env('BAYARGG_TIMEOUT', 15),
    ],

    'ai' => [
        'assistant_enabled' => filter_var(env('AI_ASSISTANT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'provider' => env('AI_PROVIDER', 'rule_based'),
        'model_fast' => env('AI_MODEL_FAST', 'gemini-2.5-flash'),
        'model_fallback' => env('AI_MODEL_FALLBACK', 'deepseek-chat'),
        'request_timeout' => (int) env('AI_REQUEST_TIMEOUT', 30),
        'max_input_tokens' => (int) env('AI_MAX_INPUT_TOKENS', 2500),
        'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 1024),
        'thinking_budget' => (int) env('AI_THINKING_BUDGET', 1024),
        'daily_budget_idr' => (int) env('AI_DAILY_BUDGET_IDR', 50000),
        'faq_cache_ttl_seconds' => (int) env('AI_FAQ_CACHE_TTL_SECONDS', 3600),
        'customer_voice_lookback_days' => (int) env('AI_CUSTOMER_VOICE_LOOKBACK_DAYS', 45),
        'web_search_enabled' => filter_var(env('AI_WEB_SEARCH_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'web_search_endpoint' => env('AI_WEB_SEARCH_ENDPOINT', 'https://api.duckduckgo.com/'),
        'web_search_timeout' => (int) env('AI_WEB_SEARCH_TIMEOUT', 8),
        'web_search_max_results' => (int) env('AI_WEB_SEARCH_MAX_RESULTS', 3),
        'gemini_api_key' => env('AI_GEMINI_API_KEY'),
        'deepseek_api_key' => env('AI_DEEPSEEK_API_KEY'),
    ],

];
