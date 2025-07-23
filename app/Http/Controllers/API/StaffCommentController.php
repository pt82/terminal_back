<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\StaffComment;

use App\Models\User;
use App\Services\YcService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StaffCommentController extends Controller
{
    /**
     * Импорт из YC отзывов о мастерах
     *
     * @return JsonResponse
     */
    public function import(YcService $yc)
    {
       $departments=Department::all();
        $result = [];
    foreach ($departments as $departmentOne) {
        $comments = $yc->to('comments/' . $departmentOne->yc_company_id)
//            ->withData([
//                'start_date' => date('Y-m-d', time() - 86400),
//                'end_date' => date('Y-m-d', time() - 86400)
//            ])
            ->asJsonRequest()->get();
        $result = json_decode($comments, true);
        foreach ((array)$result['data'] as $commentOne) {
            if($commentOne['master_id']==0 || StaffComment::where('yc_id',$commentOne['id'])->count()>0){
                continue;
            }

            $user = User::where('yc_staff_id', $commentOne['master_id'])->first();
            if ($user) {
                $comment = new StaffComment();
                $comment->user_id = $user->id;
                $comment->chain_id = $departmentOne->chain_id;
                $comment->department_id = $departmentOne->id;
                $comment->yc_id = $commentOne['id'];
                $comment->salon_id = $commentOne['salon_id'];
                $comment->type = $commentOne['type'];
                $comment->master_id = $commentOne['master_id'];
                $comment->text = $commentOne['text'];
                $comment->date = $commentOne['date'];
                $comment->rating = $commentOne['rating'];
                $comment->yc_user_id = $commentOne['user_id'];
                $comment->user_name = $commentOne['user_name'];
                $comment->user_avatar = $commentOne['user_avatar'];
                $comment->user_email = $commentOne['user_email'];
                $comment->user_phone = $commentOne['user_phone'];
                $comment->save();
            }
        }
    }

  }

    /**
     * Импорт из YC отзывов о мастерах
     *
     * @return JsonResponse
     */
    public function staffComments(Request $request,YcService $yc)
    {
       $comments=StaffComment::where('user_id',$request->user()->id)->orderBy('date', 'desc')->get();
        if($comments){
            $result=[];
            foreach ($comments as $commentOne){
                $result[]=[
                   'id'=>$commentOne->id,
                    'user_name'=>$commentOne->user_name,     //имя клиента
                    'date'=>date('d.m.Y', strtotime($commentOne->date)),
                    'rating'=>$commentOne->rating,
                    'text'=>$commentOne->text
                ];
            }
          }
        return response()->json([
            'success'=>true,
            'data'=>$result ?? []
            ]);
    }
}
