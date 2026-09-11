<?php

namespace Sharifuddin\LaravelAiBridge\DTO;

use Sharifuddin\LaravelAiBridge\Contracts\ToolInterface;

final class RetrievalCandidate
{
    public function __construct(
        public readonly ToolInterface $tool,
        public readonly float $semanticScore,
        public readonly float $lexicalScore,
        public readonly float $metadataScore,
        public readonly float $finalScore,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->tool->name(),
            'semantic_score' => round($this->semanticScore, 4),
            'lexical_score' => round($this->lexicalScore, 4),
            'metadata_score' => round($this->metadataScore, 4),
            'final_score' => round($this->finalScore, 4),
        ];
    }
}
