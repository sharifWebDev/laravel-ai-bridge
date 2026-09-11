<?php

namespace Sharifuddin\LaravelAiBridge\Console\Commands;

use Illuminate\Console\Command;
use Sharifuddin\LaravelAiBridge\Contracts\ToolRegistryInterface;
use Sharifuddin\LaravelAiBridge\Indexing\ToolIndexer;

class IndexToolsCommand extends Command
{
    protected $signature = 'ai:tools:index';

    protected $description = 'Generate/update vector embeddings for all registered AI tools (skips unchanged tools).';

    public function handle(ToolIndexer $indexer, ToolRegistryInterface $registry): int
    {
        $total = count($registry->all());
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $summary = $indexer->indexAll(force: false, onProgress: function () use ($bar) {
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();

        $this->info("Indexed: {$summary['indexed']}, Skipped (unchanged): {$summary['skipped']}");

        foreach ($summary['failed'] as $failure) {
            $this->error($failure);
        }

        return empty($summary['failed']) ? self::SUCCESS : self::FAILURE;
    }
}
