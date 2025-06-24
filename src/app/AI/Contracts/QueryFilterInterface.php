<?php
namespace App\AI\Contracts;

use App\AI\Models\FilterResult;

interface QueryFilterInterface
{
    /**
     * @param  string      $input
     * @param  string|null $priorContext  previous Q&A, if any
     */
    public function filter(string $input, ?string $priorContext = null): FilterResult;
}
