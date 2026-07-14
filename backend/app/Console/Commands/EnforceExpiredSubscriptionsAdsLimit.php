<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;

class EnforceExpiredSubscriptionsAdsLimit extends Command
{
    protected $signature = 'subscriptions:enforce-expired-ads-limit';

    protected $description = 'Mark expired subscriptions and suspend excess ads for users returning to free tier';

    public function handle(): int
    {
        $expiredSubs = Subscription::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->get();

        if ($expiredSubs->isEmpty()) {
            return Command::SUCCESS;
        }

        $userIds = $expiredSubs->pluck('user_id')->unique();

        foreach ($expiredSubs as $sub) {
            $sub->update(['status' => 'expired']);
        }

        $suspended = 0;
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $suspended += $user->enforceAdsLimit();
            }
        }

        $this->info("Marked {$expiredSubs->count()} subscriptions as expired. Suspended {$suspended} excess ads for {$userIds->count()} users.");

        return Command::SUCCESS;
    }
}
