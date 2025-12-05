<?php

namespace App\AI\Exceptions;

use Exception;

class LlmException extends Exception
{
    public static function fromException(Exception $e): self
    {
        return new self("LLM Error: " . $e->getMessage(), $e->getCode(), $e);
    }

    public static function missingResponse(): self
    {
        return new self("LLM returned an empty or invalid response.");
    }
}
