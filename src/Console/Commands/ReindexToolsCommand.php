<?php

namespace Sharifuddin\LaravelAiBridge\Console\Commands;

use Illuminate\Console\Command;
use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\Indexing\ToolIndexer;

class ReindexToolsCommand extends Command
{
    protected $signature = 'ai:tools:reindex {--tool= : Re-index only this specific tool by name}';

    protected $description = 'Force-regenerate vector embeddings for all tools, or a single tool via --tool=.';

    public function handle(ToolIndexer $indexer, ToolRegistryInterface $registry): int
    {
        $toolName = $this->option('tool');

        if ($toolName) {
            try {
                $indexer->indexOne($toolName, force: true);
                $this->info("Re-indexed tool [{$toolName}].");

                return self::SUCCESS;
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }

        $total = count($registry->all());
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $summary = $indexer->indexAll(force: true, onProgress: function () use ($bar) {
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();

        $this->info("Re-indexed: {$summary['indexed']}");

        foreach ($summary['failed'] as $failure) {
            $this->error($failure);
        }

        return empty($summary['failed']) ? self::SUCCESS : self::FAILURE;
    }
}
