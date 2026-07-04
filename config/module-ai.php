<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Platforms
    |--------------------------------------------------------------------------
    |
    | Platform entry files written by `jw:ai:init` (used by --refresh).
    | Built-in: claude, cursor, copilot, codex. Register more via 'adapters'.
    |
    */
    'platforms' => ['claude'],

    /*
    |--------------------------------------------------------------------------
    | Context Depth
    |--------------------------------------------------------------------------
    |
    | full       — complete guidelines with examples
    | compressed — rules and tables only
    | summary    — single-page digest only (.ai/summary.md)
    |
    */
    'depth' => 'full',

    // Canonical context directory. Null = base_path('.ai').
    'output_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Sections
    |--------------------------------------------------------------------------
    */
    'sections' => [
        'architecture' => true,
        'conventions' => true,
        'generators' => true,
        'ai_rules' => true,
        'modules' => true,
        'workflow' => true,
        'tooling' => true,
        'prompts' => true,
    ],

    // Modules excluded from the AI-facing inventory.
    'ignored_modules' => [],

    // Extra .md files merged verbatim into ai-rules.md (org standards).
    // Null = base_path('.ai/custom').
    'custom_guidelines_path' => null,

    /*
    |--------------------------------------------------------------------------
    | MCP Server
    |--------------------------------------------------------------------------
    */
    'mcp' => [
        'enabled' => true,
        // Allow AI assistants to execute jw:* generators via the run_generator tool.
        'allow_run_generator' => true,
        // Reserved for a future schema tool. Never queried at boot.
        'expose_database_schema' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Adapters
    |--------------------------------------------------------------------------
    |
    | 'mytool' => \App\Ai\MyToolAdapter::class
    | (must implement Jackwander\ModuleMaker\AI\Contracts\PlatformAdapter)
    |
    */
    'adapters' => [],
];
