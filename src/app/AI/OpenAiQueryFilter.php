<?php
namespace App\AI\Services;

use App\AI\Contracts\QueryFilterInterface;
use App\AI\Models\FilterResult;
use App\AI\Contracts\LlmClientInterface;
use Illuminate\Support\Facades\File;

class OpenAiQueryFilter implements QueryFilterInterface
{
    public function __construct(protected LlmClientInterface $llm) {}

    public function filter(string $input, ?string $priorContext = null): FilterResult
    {
        $promptPath = config('openai-mcp.paths.prompts', resource_path('ai-prompts'));

        // 1) load system prompt
        $system = File::get($promptPath . '/prefilter_system.txt');

        // 2) define function schema
        $functions = [[
            'name'        => 'classify_query',
            'description' => 'Decide if a query is menu-related (or a refinement) and normalize it',
            'parameters'  => [
                'type'       => 'object',
                'properties' => [
                    'approved'        => ['type'=>'boolean'],
                    'normalized_query'=> ['type'=>'string'],
                    'reason'          => ['type'=>'string'],
                ],
                'required'   => ['approved'],
            ],
        ]];

        // 3) build messages, injecting prior context if present
        $messages = [
            ['role'=>'system', 'content'=>$system],
        ];
        if ($priorContext) {
            $messages[] = ['role'=>'system', 'content'=>$priorContext];
        }
        $messages[] = ['role'=>'user', 'content'=>$input];

        // 4) call LLM
        $resp = $this->llm->call($messages, $functions, 'auto');
        $choice = $resp['choices'][0]['message']
                  ?? throw new \RuntimeException('Filter: no choice returned');

        // 5) parse function_call
        if (isset($choice['function_call'])) {
            $args = json_decode($choice['function_call']['arguments'], true);
            return new FilterResult(
                $args['approved'],
                $args['normalized_query'] ?? '',
                $args['reason']           ?? null
            );
        }

        // fallback: block
        return new FilterResult(false, '', 'Unable to classify query');
    }
}
