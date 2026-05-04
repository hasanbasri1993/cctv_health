<?php

namespace App\Console\Commands;

use App\Models\DeviceHealthLog;
use Illuminate\Console\Command;

class PruneHealthLogs extends Command
{
    protected $signature = 'monitor:prune-logs {--days=30 : Keep logs for this many days}';

    protected $description = 'Delete old device health logs';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $deleted = DeviceHealthLog::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Pruned {$deleted} health log entries older than {$days} days.");

        return self::SUCCESS;
    }
}
