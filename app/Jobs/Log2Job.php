<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Log2Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	
	private $data;
	
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( $data )
    {
        //
		$this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
		
		//\Illuminate\Support\Facades\Storage::append('logjob2.log', json_encode( $this->data ) );
//		if( $this->data['data']['level'] == 'Debug' ){
			\Illuminate\Support\Facades\Redis::publish('zuji.log.publish', json_encode( $this->data ) );
//		}
    }
}
