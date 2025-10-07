<?php

return [
    'default' => env('AI_SERVICE', 'gemini'), // gemini or openai service

    'openai' => [
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini-2024-07-18'), // default economic model
        'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 1000),
        'temperature' => (float) env('OPENAI_TEMPERATURE', 0.7),
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
    ],

    'gemini' => [
        'model' => env('GEMINI_MODEL', 'gemini-2.5-pro-exp-03-25'),
        'api_key' => env('GEMINI_API_KEY'),
        'max_tokens' => (int) env('GEMINI_MAX_TOKENS', 1000),
        'temperature' => (float) env('GEMINI_TEMPERATURE', 0.7),
    ],

    // Common system prompt - will be used for both services
    'system_prompt' => "Sen bir finansal asistansın. Kullanıcıların gelir ve gider verilerini analiz ederek sorularını yanıtlarsın. 
    Önemli kurallar:
    1. Sadece soru sahibinin verilerine erişebilirsin, diğer kullanıcıların verilerini göremezsin.
    2. Transfer işlemleri hesaplamalara dahil edilmez, sadece gerçek gelir ve giderler üzerinden analiz yaparsın.
    3. Tüm para birimlerini Türk Lirası (TL) olarak, binlik ayracı için nokta (.) ve ondalık ayracı için virgül (,) kullanarak formatla.
    4. Kullanıcı başka kullanıcıların verilerini sorduğunda nazikçe 'Üzgünüm, gizlilik politikamız gereği diğer kullanıcıların verilerine erişemiyorum.' şeklinde yanıt ver.
    5. Yanıtlarında her zaman net, anlaşılır ve profesyonel bir dil kullan.",
]; 