<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Ad;
use Illuminate\Console\Command;

class ProcessAccountDeletions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:process-deletions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process account deletions for users who did not login within 14 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find users scheduled for deletion - use chunk() to avoid loading all into memory
        $deletedCount = 0;

        $query = User::where('account_status', 'pending_deletion')
            ->where('scheduled_deletion_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('last_login_at')
                  ->orWhereColumn('last_login_at', '<', 'scheduled_deletion_at');
            });

        $query->chunk(50, function ($users) use (&$deletedCount) {
            foreach ($users as $user) {
                $user->update(['account_status' => 'deleted']);
                Ad::where('user_id', $user->id)
                    ->update(['account_status' => 'deleted_account']);
                $deletedCount++;
            }
        });

        $this->info("Processed {$deletedCount} account deletions.");

        return Command::SUCCESS;
    }

}