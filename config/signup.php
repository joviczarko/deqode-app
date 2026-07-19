<?php

return [

    'intent_ttl_hours' => (int) env('SIGNUP_INTENT_TTL_HOURS', 48),

    'max_intents_per_email_per_day' => (int) env('SIGNUP_MAX_INTENTS_PER_EMAIL', 5),

    'max_intents_per_ip_per_hour' => (int) env('SIGNUP_MAX_INTENTS_PER_IP', 10),

];
