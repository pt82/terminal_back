<?php

namespace App\Http\Controllers\API\Lcms;

use App\Http\Controllers\Controller;
use App\Http\Requests\LessonRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Ixudra\Curl\Facades\Curl;
use IvoPetkov\HTML5DOMDocument;
use Illuminate\Support\Facades\Storage;

class LessonsController extends Controller
{
    /**
     * Добавить урок для курса
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(LessonRequest $request)
    {
        info(print_r($request->all(), true));
        if (Auth::user()->level() >= 20) {
            $data = $request->all();
            $files = [];
            $course = Course::
            where('id', $request->course_id)
                ->where('franchise_id', $request->user()->franchise()->id)
                ->first();
            if(!$course){
                throw new ModelNotFoundException('Не найден курс');
            }
            $data['description'] = $this->replaceBase64($data['description'], $request->course_id);
            $result=$course->lessons()->create($data);
            if(!empty($request->files)){
                foreach ($request->files as $fileOne) {
                    $dataFiles=[];
                    $filename = $fileOne->getClientOriginalName();
                    $path = 'files/lessons/' . $result->id . '/';
                    $url = env('APP_URL') . $path . $filename;
                    $dataFiles['path'] = $url;
                    $fileOne->move(public_path() . '/' . $path, $filename);
                    $dataFiles['type'] = 1;
                    $dataFiles['name'] = $filename;
                    $files[] = $result->files()->create($dataFiles);
                }
            }
            return response()->json([
                "message" => "Create Lesson",
                'success' => (boolean)$result,
                'data' => $result ?? [],
                'files' => $files ?? []
            ]);
        }
        return response()->json(['success'=>false,'error' => 'Нет прав'], 403);
    }

    /**
     * Показать урок
     *
     * @param Request $request
     * @return JsonResponse
     */


    public function show(Request $request, $id)
    {
        if (Auth::user()->level() >= 90) {
           $lesson = Lesson::where('id',$id)->with('files')->first();
            return response()->json([
                "message" => "Edit Lesson",
                'success' => (boolean)$lesson,
                'data' => $lesson  ?? [],
            ]);
        }
        if (Auth::user()->level() >= 11 && Auth::user()->level() < 20) {
            $lesson = Lesson::where('id',$id)->select('id','course_id','title','description','time','test')->with('files')->first();
//            $count = DB::table('lesson_user')
//                ->where('user_id',Auth::id())
//                ->where('lesson_id',$lesson->id)
//                ->first();
//            if (empty($count)){
//                $lesson->users()->sync(Auth::user());
//            }
            return response()->json([
                "message" => "Begin study Lesson",
                'success' => (boolean)$lesson,
                'data'=>$lesson ?? []
            ]);
        }
        if (Auth::user()->level() < 11) {
            return response()->json(['success'=>false,'error' => 'Нет прав'], 403);
        }
    }


    /**
     * Редактирование урока
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->level() >= 90) {
            $data=$request->all();
            $files=[];
            $lesson = Lesson::where('id',$id)->first();
            if(!empty($request->files)) {
                foreach ($request->files as $fileOne) {
                    $dataFile = [];
                    $filename = $fileOne->getClientOriginalName();
                    $path = 'files/lessons/' . $lesson->id . '/';
                    $url = env('APP_URL') . $path . $filename;
                    $dataFile['path'] = $url;
                    $fileOne->move(public_path() . '/' . $path, $filename);
                    $data['type'] = 1;
                    $data['name'] = $filename;
                    $files[] = $lesson->files()->create($data);
                }
            }
            $data['description'] = $this->replaceBase64($data['description'], $lesson->course_id);
            return response()->json([
                "message" => "Edit Lesson",
                'success' => (boolean)$lesson->update($data),
                'data' => $lesson ?? [],
                'files'=> $files ?? []
            ]);
        }
        else {
            return response()->json(['success'=>false,'error' => 'Нет прав'], 403);
        }
    }

    protected function replaceBase64($description, $courseId)
    {
        $path = 'photos/courses/';
        $dom = new HTML5DOMDocument();
        $dom->loadHTML($description);
        $tags = $dom->getElementsByTagName('img');
        foreach ($tags as $tag) {
            $oldSrc = explode(';base64,', $tag->getAttribute('src'));
            if (!isset($oldSrc[1]))
                continue;

            $ext = explode('/', $oldSrc[0])[1] ?? 'jpg';
            $file = $path . $courseId . '/' . Str::random(40) . ".$ext";
            $tag->setAttribute('src', env('APP_URL') . $file);
            Storage::disk('root_public')->put($file, base64_decode($oldSrc[1]));
        }
        return $dom->querySelector('body')->innerHTML;
    }


    /**
     * Опубликовать урок
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function published(Request $request, $id)
    {
        if (Auth::user()->level() >= 20) {
             $lesson = Lesson::where('id',$id)->first();
             return response()->json([
                "message" => "Published Course",
                'success' => (boolean) $lesson->update(['published'=>1]),
            ]);
        }
        else {
            return response()->json(['success'=>false,'error' => 'Нет прав'], 403);
        }
    }


    /**
     * Начинаем урок
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function studybegin(Lesson $lesson)
    {
        if (Auth::user()->level() >= 11) {
            return response()->json([
                "message" => "Begin study Lesson",
                'success' => $this->startFinishLesson($lesson->id, false),
                'data'=>$lesson
            ]);
        }
        else {
            return response()->json(['error' => 'Нет прав'], 403);
        }
    }

    /**
     * Окончить урок
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function studyend(Lesson $lesson)
    {
        if (Auth::user()->level() >= 11) {
            return response()->json([
                "message" => "End study Lesson",
                'success' => $this->startFinishLesson($lesson->id, true),
            ]);
        }
        else {
            return response()->json(['success'=>false,'error' => 'Нет прав'], 403);
        }
    }

    protected function startFinishLesson($lessonId, $finish)
    {
        $pivot = LessonUser::firstOrNew([
            'user_id' => Auth::id(),
            'lesson_id' => $lessonId
        ]);
        $pivot->date_begin = $pivot->date_begin ?: Now();
        if ($finish)
            $pivot->date_end = $pivot->date_end ?: Now();
        return $pivot->save();
    }

    /**
     * Удалить урок
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if (Auth::user()->level() >= 20) {
           $lesson = Lesson::where('id',$id)->delete();
           return response()->json([
                "message" => "Delete Lesson",
                'success' => (boolean)$lesson,
            ]);
        }
        else {
            return response()->json(['success'=>false,'error' => 'Нет прав'], 403);
        }
    }
    /**
     * добавить тест к уроку
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storeTest(Request $request, Lesson $lesson)
    {
        if (Auth::user()->level() >= 20) {
           $data=[];
            foreach ($request->fields ?? [] as $key=>$fielddOne){
                $choice=[];
                foreach ($fielddOne['choices'] as $choicesOne=>$val ) {
                    $vars=[];
                    $details=[];
                    $choice[]=[
                        'label'=>$val['label'],
                        'ref'=>$key.$choicesOne.'choice'
                    ];
                    $vars=[
                        [
                            'type'=>'field',
                            'value'=>$key.'field'
                        ],
                        [
                            'type'=>'choice',
                            'value'=>$key.$choicesOne.'choice'
                        ]
                    ];
                    $details=[
                        'target'=>[
                            'type'=>"variable",
                            'value'=>"score"
                        ],
                        'value'=>[
                            'type'=>"constant",
                            'value'=> $val['value']
                        ]
                    ];
                    $action[]=[
                        'action'=>'add',
                        'condition'=>[
                            'op'=>'is',
                            'vars'=>$vars,
                        ],
                        'details'=>$details
                    ];
                    $actions[]=[
                        'type'=>'field',
                        'ref'=>$key.'field',
                        'actions'=>$action

                    ];
                }
                $fields[]=[
                    'title'=>$fielddOne['title'],
                    'ref'=>$key.'field',
                    'type'=>'multiple_choice',
                    'properties'=>[
                        'allow_multiple_selection'=>false,
                        'allow_other_choice'=>false,
                        'vertical_alignment'=>true,
                        'choices'=>$choice
                    ]
                ];
                $logic=$actions;
            }
            $data=[
                'title'=>$request->title,
                'type' => 'score',
                'variables'=>[
                    'score'=>0,
                ],
                'settings'=>[
                    'hide_navigation'=>true,
                    'language'=>'ru',
                ],
                'workspace'=>(array)[
                    "href"=>"https://api.typeform.com/workspaces/wmxr97"
                ],
                'hidden'=>[
                    'user_id',
                    'lesson_id'

                ],
                'fields'=>$fields,
                'logic'=>$logic
            ];
          $form = Curl::to('https://api.typeform.com/forms')
                ->withHeader('Content-Type:application/json')
                ->withHeader('Authorization:Bearer 548Szn8TQNPu2RrPKw8EBZsuAPFSCL2LrgZHXtmHxvsJ')
                ->withData($data)
                ->asJson()
                ->post();
           if (isset($form->id)){
           $webhook =  Curl::to('https://api.typeform.com/forms/'.$form->id.'/webhooks/bis')
            ->withHeader('Content-Type:application/json')
            ->withHeader('Authorization:Bearer 548Szn8TQNPu2RrPKw8EBZsuAPFSCL2LrgZHXtmHxvsJ')
            ->withData(["url"=>"https://bis.zdato.ru/api/typeform-webhook", "enabled"=>true])
            ->asJsonRequest()
            ->put();
             return response()->json([
                 "message" => "Add test",
                 'success' => $lesson->update([
                                                'test'=>$form->id,
                                                'link_admin_test'=>'https://admin.typeform.com/form/'.$form->id.'/create',
                                                'total_question_test'=>count($form->fields)
                                                ]),
                 'data'=>$lesson
             ]);
           }
            return response()->json([
                "message" => "Add test",
                'success' => false,
                'error'=>'Добавить тест не удалось'
            ]);
        }
        else {
            return response()->json(['success'=>false, 'error' => 'Нет прав'], 403);
        }
    }
    /**
     * Удалить файл урока
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fileDestroy($id)
    {
        if (Auth::user()->level() >= 20) {
             return response()->json([
                "message" => "Delete File",
                'success' => (boolean)File::where('id',$id)->delete(),
            ]);
        }
        else {
            return response()->json(['success'=>false,'error' => 'Нет прав'], 403);
        }
    }


    public function getDownload(Request $request)
    {
        $path = request('path');

        //смотрим на расширение запрашиваемого файла
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        //создаем массив расширений файлов запрещенных к скачиванию
        $blocked = ['php', 'htaccess'];

        //если расширение запрашиваемого файла отсутствует в массиве выше
        if (!in_array($extension, $blocked)) {
            //скачиваем этот файл
            return response()->download($path);
        }

//        return $request->path;
        //PDF file is stored under project/public/download/info.pdf
//       return $dir = substr(strrchr($request->path, env('APP_URL')), 7);
////      return  $file= public_path(). $dir;
//        $headers = array(
//            'Content-Type: application/pdf',
//        );
//        return Response::download($request->path, 'filename.pdf', $headers);
    }



}
