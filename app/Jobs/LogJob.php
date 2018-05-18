<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	
	private $str;
	
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( $str )
    {
        //
		$this->str = $str;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
		\Illuminate\Support\Facades\Storage::append('logjob.log', $this->str);
//        file_put_contents('./jobtest.log', $this->str, FILE_APPEND);
    }
}
