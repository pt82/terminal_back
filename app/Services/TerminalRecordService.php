<?php
namespace App\Services;

use App\Services\YcService;
use Illuminate\Support\Facades\DB;

class TerminalRecordService
{
    public function __invoke()
    {
        //через сколько секунд удалять записи без транзакций
        $seconds = 300;

        $records = DB::table('terminal_ycrecords')
            ->where('created_at', '<', now()->subSeconds($seconds)->toDateTimeString())
            ->get();

        $yc = app()->make(YcService::class);

        foreach ($records as $record) {
            $yc->to('record/' . $record->company_id . '/' . $record->record_id)->delete();
            $this->transactionCommitted($record->record_id);
        }
    }

    public function waitForTransaction($companyId, $recordId)
    {
        DB::table('terminal_ycrecords')->insert([
            'company_id' => $companyId,
            'record_id' => $recordId,
            'created_at' => now()->toDateTimeString()
        ]);
    }

    public function transactionCommitted($recordId)
    {
        DB::table('terminal_ycrecords')->where('record_id', $recordId)->delete();
    }

}
