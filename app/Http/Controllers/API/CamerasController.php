<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Camera;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ixudra\Curl\Facades\Curl;
use jessedp\Timezones\Facades\Timezones;

class CamerasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
         $cameraAll = Curl::to('http://openapi-alpha-eu01.ivideon.com/cameras?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
            ->withHeader('Content-Type: application/json')
            ->withHeader('Authorization: Basic access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b' )
//            ->withData([
//                'face_galleries'=>["100-GVaGUwCF2mHejrHbKykm"],
////               'start_time'=>1616485424
//            ])
            ->asJson()
//           ->asJsonRequest()
            ->post();


        foreach ($cameraAll->result->items as $itemCamera){
            $camera=new Camera();
            $camera->camera_id = Str::uuid()->toString();
            $camera->camera_ivideon_id = $itemCamera->id;
            $camera->department_id = 1;
            $camera->camera_name = $itemCamera->name;
            $camera->camera_adress = $itemCamera->name;
            $camera->save();
        }
//        $person=json_encode($person);

//       $person->toJson();
//        return $person;

//        return response()->json([
//            'persons'=>Person::latest()->get()
//        ],200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function camerasByDepartment($id)
    {
        return response()->json([
            'data' => Camera
                ::whereIn('department_id', [$id, '1ac09526-9db1-42f6-b438-68caaae2eb8a'])
                ->get()->toArray()
        ], 200);
    }
}
