<?php

namespace App\Http\Controllers\API\Lcms;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CoursesController extends Controller
{


    /**
     * Вывод списка курсов франшизы
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index()
    {
//        if (Auth::user()->level() >= 20) {
//            return response()->json([
//                "message" => "List Courses for Admin",
//                'success' => (boolean)($result = Course::where('franchise_id', Auth::user()->franchise()->id)->get()),
//                'data' => $result ?? []
//            ]);
//        }
            if (Auth::user()->level() >= 11) {
                if (Auth::user()->level() >= 20 ) {
                  $courses=Course::all();
                    }
                if (Auth::user()->level() >= 11 && Auth::user()->level() < 20 ) {
                    $courses=Course::whereIn('published',[1])->get();
                }
                $result=[];
                foreach ($courses as $courseOne) {
                    $countTotalLessonDone = 0;
                    $time = 0;
                    foreach ($courseOne->lessons as $lessonOne ) {
                        $time += $lessonOne->time;
                        if(count($lessonOne->pivotLessonUser)>0 && $lessonOne->pivotLessonUser[0]->pivot->date_end != NULL){
                            $countTotalLessonDone++;//считаем количество завершенных уроков
                        }
                    }
                    $countTotalLesson = $courseOne->lessons->where('published',1)->count();//всего опубликованных уроков в курсе
                    $percent_done=0;
                    if($countTotalLesson!=0){
                        $percent_done = ($countTotalLessonDone/$countTotalLesson)*100;
                    }

                    $result[]=[
                        'id'=>$courseOne->id,
                        'franchise_id'=>$courseOne->franchise_id,
                        'user_id'=>$courseOne->user_id,
                        'role_id'=>$courseOne->role_id,
                        'title'=>$courseOne->title,
                        'description'=>$courseOne->description,
                        'published'=>$courseOne->published,
                        'created_at'=>$courseOne->created_at,
                        'updated_at'=>$courseOne->updated_at,
                        'percent_done'=>round($percent_done,0),
                        'lessons_total'=>count($courseOne->lessons),
                        'lessons_done'=>$countTotalLessonDone,
                        'time' => floor($time / 60) . ' часа ' . ($time % 60) .' минут'
                    ];
                }
                if (Auth::user()->level() >= 20 ) {
                    $result = collect($result)->map(function ($item) {
                       unset($item['percent_done']);
                       unset($item['lessons_done']);
                        return $item;
                    });
                }

                return response()->json([
                    "message" => "List Courses",
                    'success' => (boolean)$result,
                    'data'=>$result
                ]);
            }
         elseif (Auth::user()->level() < 11 ) {
            return response()->json(['success' => false, 'error' => 'Нет прав'], 403);
        }
    }

    /**
     * Добавить курс для франшизы
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {

        if (Auth::user()->level() >= 20) {
            $data = $request->all();
            $data['user_id'] = $request->user()->id;
            $data['franchise_id'] = $request->user()->franchise()->id;

            return response()->json([
                "message" => "Create Courses",
                'success' => (boolean)($result = Course::create($data)),
                'data' => $result ?? []
            ]);
        } else {
            return response()->json(['error' => 'Нет прав'], 403);
        }
    }

    /**
     * Показать курс с уроками
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request, $id)
    {
            if (Auth::user()->level() >= 20) {
                    $course = Course::
                    where('id', $id)
                        ->where('franchise_id', $request->user()->franchise()->id)
                        ->first();
               if(!$course){
                   throw new ModelNotFoundException('Не найден курс');
               }
                $course->lessons->each(function ($lesson) {
                    $lesson->done = true;
                    $lesson->unblocked = true;
                });
                return response()->json([
                    "message" => "Show Course with lessons for admin",
                    'success' => true,
                    'data' => $course ?? [],
                ]);
            }
        if (Auth::user()->level() >= 11 && Auth::user()->level() < 20) {
            $course=Course::where('id',$id)->where('published',1)->first();
            $result=[];
            $courseResult=[];
            if($course) {
                $lessons = Lesson::where('course_id', $id)
                    ->with(['lessonUser' => function ($query) {
                        $query->where('user_id', Auth::id());
                    }])
                    ->where('published', 1)
                    ->orderBy('sequence', 'asc')
                    ->get();
                foreach ($this->lessonStatus($lessons) as $lesson) {
                    if ($lesson) {
                        $result[] = [
                            'id' => $lesson->id,
                            'title' => $lesson->title,
                            'description' => $lesson->description,
                            'time' => $lesson->time,
                            'done' => $lesson->done,
                            'unblocked' => $course->random_view ? true : $lesson->unblocked,
                        ];
                    }
                }
                $courseResult=(object)[
                    'id'=>$course->id,
                    'franchise_id'=>$course->franchise_id,
                    'user_id' => $course->user_id,
                    'role_id' => $course->role_id,
                    'title'=>$course->title,
                    'description'=>$course->description,
//                    'published'=>$course->published,
                    'lessons' => $result
                ];
            }
            return response()->json([
                "message" => "Show Course with lessons for student",
                'success' => (boolean)$courseResult,
                'data'=> $courseResult
            ]);
        }

            elseif (Auth::user()->level() < 11) {
                return response()->json(['error' => 'Нет прав'], 403);
            }


    }

    protected function lessonStatus($lessons)
    {
        foreach ($lessons as $key => $lesson) {
            $lesson->done = boolval($lesson->lessonUser->date_end ?? false);

            if (!$key) {
                $lesson->unblocked = true;
            } else {
                $lesson->unblocked = ($donePrevious and $unblockedPrevious);
            }

            $donePrevious = $lesson->done;
            $unblockedPrevious = $lesson->unblocked;
        }
        return $lessons;
    }

    /**
     * Редактировать курс
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request, Course $course)
    {
      if (Auth::user()->level() >= 20) {
            $data=$request->all();
             return response()->json([
                "message" => "Edit Course",
                'success' => (boolean)$course->update($data),
                'data' => $course ?? [],
            ]);
        }
        else {
            return response()->json(['error' => 'Нет прав'], 403);
        }
    }

    /**
     * Опубликовать курс с уроками
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function published(Course $course)
    {
        if (Auth::user()->level() >= 20) {
            try {
                $published = \request('publish', 0);
                $lessons = $course->lessons;
                $success = $course->update(['published' => $published]);
                DB::transaction(function () use ($lessons, $published) {
                    foreach ($lessons as $lesson) {
                        Lesson::find($lesson->id)->update(['published' => $published]);
                    }
                });
            } catch (\Exception | \Throwable $e) {
                info($e);
            };
            return response()->json([
                "message" => "Published Course",
                'success' => (boolean)$success,
            ]);
        } else {
            return response()->json(['error' => 'Нет прав'], 403);
        }
    }


    /**
     * Курсы студента
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function study()
    {
        if (Auth::user()->level() >= 11) {
           $courses=Course::where('published',1)->get();
           $result=[];
           foreach ($courses as $courseOne) {
               $countTotalLessonDone=0;
               foreach ($courseOne->lessons as $lessonOne) {
                    if(count($lessonOne->pivotLessonUser)>0 && $lessonOne->pivotLessonUser[0]->pivot->date_end != NULL){
                       $countTotalLessonDone++;//считаем количество завершенных уроков
                    }
               }
               $countTotalLesson = $courseOne->lessons->where('published',1)->count();//всего опубликованных уроков в курсе
               $percent_done=0;
               if($countTotalLesson!=0){
                   $percent_done = ($countTotalLessonDone/$countTotalLesson)*100;
               }
               $result[]=[
                   'id'=>$courseOne->id,
                   'franchise_id'=>$courseOne->franchise_id,
                   'user_id'=>$courseOne->user_id,
                   'role_id'=>$courseOne->role_id,
                   'title'=>$courseOne->title,
                   'description'=>$courseOne->description,
                   'published'=>$courseOne->published,
                   'percent_done'=>$percent_done
               ];
           }
            return response()->json([
            "message" => "Course of student",
            'success' => (boolean)$result,
            'data'=>$result
            ]);
        }
        else {
            return response()->json(['error' => 'Нет прав'], 403);
        }
    }

    /**
     * Курс студента c уроками
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function studyShow($id)
    {
        if (Auth::user()->level() >= 11) {
         $course=Course::where('id',$id)->where('published',1)->first();
         $result=[];
         $courseResult=[];
         if($course) {
             foreach ($course->lessons->pluck('id') as $lessonId) {
                    $lesson = Lesson::find($lessonId)->pivotLessonUser;
                      if (count($lesson) > 0) {
                        $result[] = [
                         'id' => $lesson[0]->id,
                         'title' => $lesson[0]->course_id,
                         'description' => $lesson[0]->description,
                         'published' => $lesson[0]->published,
                         'done' => (boolean)$lesson[0]->pivot->date_end,
                     ];
                 }
             }
             $courseResult=(object)[
                 'id'=>$course->id,
                 'title'=>$course->title,
                 'description'=>$course->description,
                 'published'=>$course->published,
             ];
         }

           return response()->json([
                "message" => "Course of student with lessons",
                'success' => (boolean)$courseResult,
                'data'=>[
                   'course'=>$courseResult,
                    'lessons'=>$result
                ]
            ]);
        }
        else {
            return response()->json(['error' => 'Нет прав'], 403);
        }
    }

    /**
     * удалить курс студента
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if (Auth::user()->level() >= 20) {
            $course = Course::where('id',$id)->delete();
            return response()->json([
                "message" => "Delete Course",
                'success' => (boolean)$course
            ]);
        }
        else {
            return response()->json(['error' => 'Нет прав'], 403);
        }
    }


    public function lessonsOrder($courseId, Request $request)
    {
        if (Auth::user()->level() < 20)
            abort(403);

        $lessons = Course::find($courseId)->lessons->keyBy('id') ?? null;
        if (!$lessons)
            return ['success' => false];

        foreach ($request->lessons ?? [] as $key => $lesson) {
            if (!isset($lessons[$lesson['id']]))
                continue;

            $lessons[$lesson['id']]->sequence = $key + 1;
            $lessons[$lesson['id']]->save();
        }
        return ['success' => true];
    }
}

