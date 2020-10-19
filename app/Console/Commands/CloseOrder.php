<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CloseOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '取消待支付订单';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $orders = DB::table('order')
            ->where([
                ['state', '=', 0],
                ['add_time', '>=', ''],
            ])
            ->get();
    }
}
