<?php

namespace App\Contracts;

interface LlmProviderInterface
{
    /**
     * Ask a question to the LLM with relevant context.
     *
     * @param string $question The user's prompt or question.
     * @param string $context The JSON or text context (e.g., RFM data, Basket rules).
     * @return string The generated answer.
     */
    public function ask(string $question, string $context): string;
}
