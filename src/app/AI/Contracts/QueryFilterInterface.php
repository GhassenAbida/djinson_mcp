<?php
namespace Djinson\OpenAiMcp\app\AI\Contracts;

use Djinson\OpenAiMcp\app\AI\Models\FilterResult;

interface QueryFilterInterface
{
    /**
     * @param  string      $input
     * @param  string|null $priorContext  previous Q&A, if any
     */
    public function filter(string $input, ?string $priorContext = null): FilterResult;
}
