<?php
namespace Djinson\OpenAiMcp\app\AI\Models;

class FilterResult
{
    public bool   $approved;
    public string $normalizedQuery;
    public ?string $reason;

    public function __construct(bool $approved, string $normalizedQuery = '', ?string $reason = null)
    {
        $this->approved       = $approved;
        $this->normalizedQuery= $normalizedQuery;
        $this->reason         = $reason;
    }
}
