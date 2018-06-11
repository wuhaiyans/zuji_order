<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:daoru';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        try {
            $limit = 10;
            $page = 111;
            do {
                DB::beginTransaction();

                $list = self::list($limit, $page);
                $items = $list->items();
                $totalpage = $list->lastPage();

                foreach ($items as $item) {
                    if (!$item->cert_no || !$item->realname) continue;

                    $params = [
                        'certNo' => $item->cert_no,
                        'name' => $item->realname,
                        'mobile' => $item->mobile
                    ];

                    $result = Kn::kinfo($params);
                    $item->intro = json_encode($result);
                    $item->update();
                }
                $page++;
                Log::info($page);
                DB::commit();
            } while ($page <= $totalpage);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
