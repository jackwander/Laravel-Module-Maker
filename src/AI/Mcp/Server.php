<?php

namespace Jackwander\ModuleMaker\AI\Mcp;

use Illuminate\Support\Facades\File;
use Jackwander\ModuleMaker\AI\Inspector;

class Server
{
    public const PROTOCOL_VERSION = '2024-11-05';

    public function __construct(protected ToolRegistry $tools)
    {
    }

    /**
     * Blocking stdio loop: one JSON-RPC message per line (newline-delimited).
     */
    public function run($input = STDIN, $output = STDOUT): void
    {
        while (($line = fgets($input)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $request = json_decode($line, true);

            if (! is_array($request)) {
                $this->emit($output, $this->error(null, -32700, 'Parse error'));
                continue;
            }

            $response = $this->handle($request);

            if ($response !== null) {
                $this->emit($output, $response);
            }
        }
    }

    /**
     * Handle a single JSON-RPC request. Returns null for notifications.
     */
    public function handle(array $request): ?array
    {
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];
        $id = $request['id'] ?? null;

        // Notifications expect no response.
        if (str_starts_with($method, 'notifications/')) {
            return null;
        }

        try {
            $result = match ($method) {
                'initialize' => $this->initialize($params),
                'ping' => (object) [],
                'tools/list' => ['tools' => $this->tools->schemas()],
                'tools/call' => $this->tools->call($params['name'] ?? '', $params['arguments'] ?? []),
                'resources/list' => ['resources' => $this->listResources()],
                'resources/read' => $this->readResource($params),
                default => null,
            };

            if ($result === null) {
                return $this->error($id, -32601, "Method not found: {$method}");
            }

            return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
        } catch (\InvalidArgumentException $e) {
            return $this->error($id, -32602, $e->getMessage());
        } catch (\Throwable $e) {
            return $this->error($id, -32603, $e->getMessage());
        }
    }

    protected function initialize(array $params): array
    {
        return [
            'protocolVersion' => $params['protocolVersion'] ?? self::PROTOCOL_VERSION,
            'capabilities' => [
                'tools' => (object) [],
                'resources' => (object) [],
            ],
            'serverInfo' => [
                'name' => 'module-maker',
                'version' => Inspector::packageVersion(),
            ],
        ];
    }

    protected function listResources(): array
    {
        $path = config('module-ai.output_path') ?: base_path('.ai');

        if (! File::isDirectory($path)) {
            return [];
        }

        $resources = [];

        foreach (File::files($path) as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $topic = $file->getFilenameWithoutExtension();
            $resources[] = [
                'uri' => "module-maker://guidelines/{$topic}",
                'name' => $topic,
                'mimeType' => 'text/markdown',
            ];
        }

        return $resources;
    }

    protected function readResource(array $params): array
    {
        $uri = $params['uri'] ?? '';

        if (! preg_match('#^module-maker://guidelines/([a-z0-9\-]+)$#', $uri, $matches)) {
            throw new \InvalidArgumentException("Unknown resource: {$uri}");
        }

        $path = (config('module-ai.output_path') ?: base_path('.ai')) . "/{$matches[1]}.md";

        if (! File::exists($path)) {
            throw new \InvalidArgumentException("Resource not generated yet: {$uri}. Run `php artisan jw:ai:init`.");
        }

        return [
            'contents' => [[
                'uri' => $uri,
                'mimeType' => 'text/markdown',
                'text' => File::get($path),
            ]],
        ];
    }

    protected function error($id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }

    protected function emit($output, array $response): void
    {
        fwrite($output, json_encode($response, JSON_UNESCAPED_SLASHES) . "\n");
    }
}
