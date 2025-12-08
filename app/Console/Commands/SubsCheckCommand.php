<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SubsCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:subs-check-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {

            DB::table('subscription_packages')->whereDate('expired_at', now())
                ->update([
                    'is_active' => false
                ]);

            DB::table('subscription_addons')->whereDate('expired_at', now())
                ->update([
                    'is_active' => false
                ]);

        } catch (\Throwable $th) {

        }
    }
}
