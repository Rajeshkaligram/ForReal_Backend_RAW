<?php

namespace App\Console\Commands;

use App\Models\ExchangeRate;
use Illuminate\Console\Command;

class GetExchangeRates extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'exchange_rate:update';
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Get foreign exchange rates';
	
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
		$baseCurrency = 'CAD';
		$url = "https://api.exchangeratesapi.io/latest?base=" . $baseCurrency;
		if(!$result = file_get_contents($url)) {
			$msg = 'Error: Unable to fetch exchange rates';
			\Log::error($msg);
			$this->error($msg);
			exit(1);
		}
		
		if(!$result = @json_decode($result)) {
			$msg = 'Error: Unable to parse exchange rates';
			\Log::error($msg);
			$this->error($msg);
			exit(1);
		}
		
		if(ExchangeRate::whereDate('updated_at', $result->date)->count()) {
			$this->info('Exchange rates already updated');
			exit(0);
		}
		
		// Updating database
		foreach($result->rates as $code => $rate) {
			ExchangeRate::create(
				[
					'currency_code' => $code,
					'rate'          => $rate
				]);
		}
		
		$this->info('Exchange rates updated successfully');
		exit(0);
	}
}
