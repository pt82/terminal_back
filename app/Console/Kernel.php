<?php

namespace App\Console;

use App\Models\Chain;
use App\Models\Department;
use App\Models\Item;
use App\Models\Photo;
use App\Models\User;
use App\Models\Ycitem;
use App\Models\Ycrecord;
use App\Services\ImportComments;
use App\Services\ImportCommentService;
use App\Services\NewClientYcService;
use App\Services\QaAnaliticsService;
use App\Services\ReportRecordsLLService;
use App\Services\ReportRecordsService;
use App\Services\ReportService;
use App\Services\TerminalRecordService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ixudra\Curl\Facades\Curl;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

//        try {
//            $schedule->call(new ReportService)->dailyAt('10:00')->timezone('Asia/Novosibirsk');
//        } catch (\Exception | \Throwable $e) {}

        try {
            $schedule->call(new NewClientYcService)->everyFifteenMinutes()->timezone('Asia/Novosibirsk');
        } catch (\Exception | \Throwable $e) {}

//        try {
//            $schedule->call(new ReportRecordsLLService)->dailyAt('00:55')->timezone('Asia/Novosibirsk');
//        } catch (\Exception | \Throwable $e) {}

//        try {
//            $schedule->call(new ReportRecordsService)->dailyAt('20:55')->timezone('Asia/Novosibirsk');
//        } catch (\Exception | \Throwable $e) {}

//        try {
//            $schedule->call(new TerminalRecordService)->everyMinute();
//        } catch (\Exception | \Throwable $e) {}

        try {
            $schedule->call(new ImportCommentService)->dailyAt('08:05')->timezone('Asia/Novosibirsk');
        } catch (\Exception | \Throwable $e) {}

        try {
            $schedule->command('telescope:prune --hours=360')->dailyAt('23:55')->timezone('Asia/Novosibirsk');
        } catch (\Exception | \Throwable $e) {}

//        try {
//            $schedule->call(new QaAnaliticsService)->dailyAt('09:05')->timezone('Asia/Novosibirsk');
//        } catch (\Exception | \Throwable $e) {}


//        $schedule->call(function () {
////            $formService = app()->make('FormService');
//            app()->make('App\Services\CronService')
//                ->tableEachRow('task1', function ($prevId, $currId) {
//                    try {
//                        $records = Ycrecord::
//                        where('chain_id',49)
//                            ->where('id', '>', $prevId)
//                            ->orderBy('id', 'asc')->take(10)->get();
//                        foreach ($records as $recordOne) {
//                            $r=[];
//                            $currId = $recordOne->id;
//                            sleep(1);
//                            $r = Curl::to('https://strizhevskiy.esalon.pro/getPhotosForBorodinAndZaripov')
//                                ->withHeader('Content-Type: application/json')
//                                ->withHeader('SuperSecretTokenFromBorodin: D53308005MHPVK6T4MT1HF8T2B8ELMTBBXNROCT31WBP71O4LYGH1JXMWETKO3NB48G6GCYUTVY9IHAQWZHB7P1CSW64XEUM26DITFXPJRMQGL07EAK2BOCLU5QFNLDS')
//                                ->withHeader('Authorization: ru.exploitit.strizhevski')
//                                ->withData([
//                                    'record_id' => $recordOne->record_id,
//                                ])
//                                ->asJson()
//                                ->get();
//                            if(isset($r->record_id)&&$recordOne->user_id==NULL) {
//                                $user_id = User::where('phone', preg_replace('/[^0-9]/', '', $recordOne->phone ?? '') ?? NULL)->first();
//                                Ycrecord::select('record_done','user_id')->where('id',$recordOne->id)->update(['user_id'=>$user_id->id ?? NULL]);
//                                $ycItem = new Ycitem();
//                                $ycItem->ycitem_id = Str::uuid()->toString();
//                                $ycItem->person_id = $user_id->person_id ?? NULL;
//                                $ycItem->yc_id = $r->client_id ?? NULL;
//                                $ycItem->department_id = Department::where('id',$recordOne->department_id)->first()->department_id ?? NULL;
//                                $ycItem->chain_id = Chain::where('id',$recordOne->chain_id)->first()->chain_id ?? NULL;
//                                $ycItem->name = $user_id->firstname ?? '';
//                                $ycItem->phone = preg_replace('/[^0-9]/', '', $user_id->phone ?? '') ?? '';
//                                $ycItem->save();
//                            }
//                            if (isset($r->photos)) {
//                                foreach ($r->photos as $photoOne) {
//                                    if($recordOne->user_id==NULL){
//                                        $user_id=User::where('phone',preg_replace('/[^0-9]/', '', $recordOne->phone ?? '')?? NULL)->first()->id ?? NULL;
//                                    }
//
//                                    $newPhoto = new Photo();
//                                    $newPhoto->user_id = $recordOne->user_id ?? $user_id;
//                                    $newPhoto->ycrecord_id = $recordOne->id;
//
//                                    $contents = file_get_contents($photoOne);
//                                    $filename=Str::random(15).'.jpeg';
//                                    if (!is_dir(public_path() . '/photos/records/'.$recordOne->id)) {
//                                        mkdir(public_path() . '/photos/records/'.$recordOne->id);
//                                    }
//
//                                    if($newPhoto->user_id==NULL){
//                                        $filename='recordbis-'.$recordOne->id.'_useryc-'.($r->client_id ?? '').''.'_recordyc-'.($r->record_id ?? '').'_'.Str::random(5).'.jpeg';
//                                        $newPhoto->user_id='undefined';
//                                    }
//                                    $path = 'photos/records/'.$recordOne->id .'/';
//                                    $url = env('APP_URL') . $path . $filename;
//                                    file_put_contents(public_path() .'/'. $path . $filename, $contents);
//                                    if($newPhoto->user_id=='undefined'){
//                                        $newPhoto->user_id=NULL;
//                                    }
//                                    $newPhoto->type = 1;
//                                    $newPhoto->path = $url;
//                                    $newPhoto->type_title = 'фото стрижки с моб приложения';
//                                    Ycrecord::select('record_done','user_id')->where('id',$recordOne->id)->update(['user_id'=>$newPhoto->user_id,'record_done'=>1]);
//                                    $newPhoto->save();
//                                }
//                            } else {
//                                continue;
//                            }
//                        }
//                    } catch (\Exception | \Throwable $e) {
//                        info($e);
//                        exit;
//                    }
//                    return $currId;
//                });
//        })->everyMinute();




    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
