<?php

namespace App\Console\Commands;

use App\Services\ArticleMetricsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('analytics:flush-article-metrics')]
#[Description('Flush pending article view counters into hourly aggregates')]
class FlushArticleMetrics extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ArticleMetricsService $metrics): int
    {
        $views = $metrics->flush();

        $this->components->info("Flushed {$views} pending article views.");

        return self::SUCCESS;
    }
}
