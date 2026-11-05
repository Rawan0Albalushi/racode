<?php

namespace App\Console\Commands;

use App\Services\ContentPublisher;
use Illuminate\Console\Command;

class PublishScheduledContent extends Command
{
    protected $signature = 'content:publish-scheduled';

    protected $description = 'Publish content whose scheduled time has arrived';

    public function handle(ContentPublisher $publisher): int
    {
        $count = $publisher->publishDue();
        $this->info("Published {$count} scheduled item(s).");

        return self::SUCCESS;
    }
}
