<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Artisan;

class CommandController extends Controller
{
    private function checkAccess()
    {
        // if (request('key') !== env('APP_SECRET_KEY')) {
        //     abort(403, 'Unauthorized');
        // }
    }

    public function cacheClear()
    {
        $this->checkAccess();
        Artisan::call('cache:clear');
        return "✅ Cache cleared!";
    }

    public function configClear()
    {
        $this->checkAccess();
        Artisan::call('config:clear');
        return "✅ Config cache cleared!";
    }

    public function configCache()
    {
        $this->checkAccess();
        Artisan::call('config:cache');
        return "✅ Config cache stored!";
    }

    public function routeCache()
    {
        $this->checkAccess();
        Artisan::call('route:cache');
        return "✅ Routes cached!";
    }

    public function routeClear()
    {
        $this->checkAccess();
        Artisan::call('route:clear');
        return "✅ Route cache cleared!";
    }

    public function viewClear()
    {
        $this->checkAccess();
        Artisan::call('view:clear');
        return "✅ View cache cleared!";
    }

    public function viewCache()
    {
        $this->checkAccess();
        Artisan::call('view:cache');
        return "✅ View cache stored!";
    }

    // public function migrate()
    // {
    //     $this->checkAccess();
    //     Artisan::call('migrate', ['--force' => true]);
    //     return "✅ Database migrated!";
    // }

    public function storageLink()
    {
        $this->checkAccess();
        Artisan::call('storage:link');
        return "✅ Storage linked!";
    }

    public function keyGenerate()
    {
        Artisan::call('key:generate', ['--force' => true]);
        return "✅ New application key generated!";
    } 
    
    public function queueWork()
    {
        Artisan::call('queue:work', ['--once' => true]); // Process one job
        //Artisan::call('queue:work');
        var_dump("Queue processed all jobs.");
        return 'Queue processed all jobs.';
    }
    
    public function queueRetry($id = null)
    {
        Artisan::call('queue:retry', [$id ? $id : 'all']);
        return 'Queue retry command executed.';
    }
    
    public function queueFailed()
    {
        Artisan::call('queue:failed');
        return nl2br(Artisan::output()); // Show failed jobs
    }
    
    public function queueForget($id)
    {
        Artisan::call('queue:forget', ['id' => $id]);
        return "Forgot failed job: $id";
    }
    
    public function queueFlush()
    {
        Artisan::call('queue:flush');
        return 'Flushed all failed jobs.';
    }  

    public function updateCurrencyRates()
    {
        $this->checkAccess();
        Artisan::call('update:currency-rates');

        return nl2br(e(Artisan::output()));
    }
    
    public function optimizeClear(Request $request)
    {
        Artisan::call('optimize:clear');

        if ($request->has('back') && $request->boolean('back')) {
            return response()->make(
                '<script>
                    alert("Cache cleared successfully.");
                    window.close();
                </script>',
                200,
                ['Content-Type' => 'text/html']
            );
        }

        return 'Optimize cleared successfully.';
    }    
}
