<?php


namespace App\Services;


use App\Models\Department;
use App\Models\Franchise;
use App\Models\StaffComment;
use App\Models\User;

class ImportCommentService
{
    //импортируем коментарии из yc
    public function __invoke(YcService $yc)
    {
        $departments=Department::all();
        foreach ($departments as $departmentOne) {
            $user_token=Franchise::where('id',$departmentOne->franchise_id)->first()->user_token;
            $comments=$yc->auth($user_token)->to('comments/' . $departmentOne->yc_company_id)
                ->withData([
                    'start_date'=>date('Y-m-d',time() - 86400),
                    'end_date'=>date('Y-m-d',time() - 86400)
                ])
                ->asJsonRequest()->get();
            $result = json_decode($comments, true);
            foreach ((array)$result['data'] as $commentOne){
                if($commentOne['master_id']==0 || StaffComment::where('yc_id',$commentOne['id'])->count()>0){
                    continue;
                }
                $user=User::where('yc_staff_id',$commentOne['master_id'])->first();
                if($user) {
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
}
