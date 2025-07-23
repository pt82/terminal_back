<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LessonUser;
use App\Models\Typeform;
use App\Models\Ycdb;
use App\Models\Ycrecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TypeFormWebhookController extends Controller
{

    public function hook(Request $request)
    {
//        info((array)$request->all());
        $test = new Ycdb();
        $test->custom_fields = $request->all();
        $test->comment = 'typeform';
        $test->save();

        if($request->event_type=='form_response'){
            $form_response=$request->form_response;
            $typeform=new Typeform();
            if(isset($form_response['hidden']['ycrecord_id'])) {
                $record = Ycrecord::where('id', (int)$form_response['hidden']['ycrecord_id'] ?? 0)->first();
                $typeform->ycrecord_id=(int)$form_response['hidden']['ycrecord_id'] ?? NULL;
                $typeform->department_id=$record->department_id ?? NULL;
                $record->update([
                    'rating'=>$form_response['calculated']['score'],
                    'typeform_status'=>2
                ]);
            }
            if(isset($form_response['hidden']['user_id']) && isset($form_response['hidden']['lesson_id'])){
                $pivot = LessonUser::firstOrNew([
                    'user_id' => $form_response['hidden']['user_id'],
                    'lesson_id' => $form_response['hidden']['lesson_id']
                ]);
                $pivot->calculated = floor($form_response['calculated']['score']/100);
                $pivot->save();
            }
            $typeform->event_id=$request->event_id ?? NULL;
            $typeform->chain_id=$record->chain_id ?? NULL;
            $typeform->form_id=$form_response['form_id'];
            $typeform->title=$form_response['definition']['title'];
            $typeform->answers=$form_response['answers'];
            $typeform->definition=$form_response['definition'];
            $typeform->calculated=$form_response['calculated']['score'];
            $typeform->save();

        }

    }
}
