<?php

$EM_CONF['rrp_t3toon'] = [
    'title' => 'T3Toon – Token-Efficient Data Format for TYPO3 AI',
    'description' => 'TOON for TYPO3 — a compact, human-readable, and token-efficient data format for AI prompts & LLM contexts. Perfect for ChatGPT, Gemini, Claude, Mistral, and OpenAI integrations (JSON ⇄ TOON).',
    'category' => 'be',
    'author' => 'Rohan R. Parmar',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.0.0-13.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];