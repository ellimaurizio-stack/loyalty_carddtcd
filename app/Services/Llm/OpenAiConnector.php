<?php

namespace App\Services\Llm;

use App\Contracts\LlmProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiConnector implements LlmProviderInterface
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = env('OPENAI_API_KEY', '');
        $this->model = env('OPENAI_MODEL', 'gpt-4o-mini'); // default model
    }

    public function ask(string $question, string $context): string
    {
        if (empty($this->apiKey)) {
            Log::warning('LLM Connector called but OPENAI_API_KEY is missing.');
            return "Errore: Connettore AI non configurato. Inserisci la OPENAI_API_KEY nel file .env per sbloccare l'Intelligenza Artificiale.";
        }

        $systemPrompt = "Sei un Copilot di Marketing esperto. Ti verrà fornito un contesto JSON con i dati storici degli acquisti, l'analisi del Market Basket (Supporto, Lift) e la segmentazione RFM dei clienti del negozio. Usa SOLO i dati del contesto per rispondere alla domanda dell'utente in modo strategico, professionale e azionabile. Se i dati non supportano un'azione, dillo chiaramente.";

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => "CONTESTO ANALITICO:\n" . $context . "\n\nDOMANDA UTENTE:\n" . $question],
                    ],
                    'temperature' => 0.7,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content') ?? 'Nessuna risposta generata.';
            }

            Log::error('OpenAI API Error: ' . $response->body());
            return "Si è verificato un errore di comunicazione con il motore di Intelligenza Artificiale.";
        } catch (\Exception $e) {
            Log::error('OpenAI Connection Exception: ' . $e->getMessage());
            return "Errore tecnico di connessione al motore AI.";
        }
    }
}
