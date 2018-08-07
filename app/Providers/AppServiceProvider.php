<?php

namespace App\Providers;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        if (config('app.debug')) {
            // Save the query to file
            $logFile = fopen(
                storage_path('logs' . DIRECTORY_SEPARATOR . date('Y-m-d') . '_query.log'),
                'a+'
            );
            $content = '============ URL: ' . request()->fullUrl() . ' ==============='.PHP_EOL;
            $content .= '============ URL的method: ' . request()->input('method') . ' ==============='.PHP_EOL;
            fwrite($logFile,  $content);
            fclose($logFile);
            DB::listen(function (QueryExecuted $query) {
                $sqlWithPlaceholders = str_replace(['%', '?'], ['%%', '%s'], $query->sql);

                $bindings = $query->connection->prepareBindings($query->bindings);
                $pdo = $query->connection->getPdo();
                $realSql = vsprintf($sqlWithPlaceholders, array_map([$pdo, 'quote'], $bindings));
                $duration = $this->formatDuration($query->time / 1000);

//                Log::debug();

                $logFile1 = fopen(
                    storage_path('logs' . DIRECTORY_SEPARATOR . date('Y-m-d') . '_query.log'),
                    'a+'
                );
                fwrite($logFile1, date('Y-m-d H:i:s') . ': ' . sprintf('[%s] %s', $duration, $realSql) . PHP_EOL);
                fclose($logFile1);


                // Insert bindings into query
//                $query = str_replace(array('%', '?'), array('%%', '%s'), $sql->sql);
//
//                $query = vsprintf($query, $sql->bindings);





            });
        }

        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }


    /**
     * Format duration.
     *
     * @param float $seconds
     *
     * @return string
     */
    private function formatDuration($seconds)
    {
        if ($seconds < 0.001) {
            return round($seconds * 1000000) . 'μs';
        } elseif ($seconds < 1) {
            return round($seconds * 1000, 2) . 'ms';
        }

        return round($seconds, 2) . 's';
    }
}
