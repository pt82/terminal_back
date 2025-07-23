<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;

class CronService
{
    public function tableEachRow($label, callable $func, $startBackId = 0)
    {
        $path = 'cron_tmp/' . $label . '.txt';
        if (Storage::disk('public')->exists($path)) {
            $file = trim(Storage::disk('public')->get($path));
            if (strstr($file, 'busy') or strstr($file, 'done'))
                return false;
        } else {
            $file = $startBackId;
        }
        Storage::disk('public')->put($path, 'busy_' . $file);
        $stop = $func($file, $file);
        if ($stop == $file) {
            Storage::disk('public')->put($path, 'done');
        } else {
            Storage::disk('public')->put($path, $stop);
        }
    }
}
