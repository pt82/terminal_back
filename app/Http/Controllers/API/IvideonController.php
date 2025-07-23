<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;

class IvideonController extends Controller
{
    // Загрузка всех новых id_ivideon с Ivideon, которых нет в базе
    public function LoadIdIvideon()
    {
        $arrDbPerson=Person::all();
        $arrIvideonPerson = Curl::to('http://openapi-alpha-eu01.ivideon.com/faces?op=FIND&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
            ->withHeader('Content-Type: application/json')
            ->withHeader('Authorization: Basic access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b' )
            ->withData([
                'face_galleries'=>["100-GVaGUwCF2mHejrHbKykm"],
            ])
            ->asJson()
            ->post();
        $newPersons = [];
        foreach($arrIvideonPerson->result->items as $itemsIvideonPerson) {
            $personExists = false;
            foreach($arrDbPerson as $itemsDbPerson) {
                if($itemsDbPerson->person_ivideon_id === $itemsIvideonPerson->id) {
                    $personExists = true;
                    break;
                }
            }
            if (!$personExists) {
                $photos=[];
                for($itemPhoto=0; $itemPhoto<count($itemsIvideonPerson->photos); $itemPhoto++)
                {
                    array_push($photos, $itemsIvideonPerson->photos[$itemPhoto]->thumbnails->original->url);
                }
                $newPersons[] = [
                    'id_ivideon'=>$itemsIvideonPerson->id,
                    'face_gallery_id' =>$itemsIvideonPerson->face_gallery_id,
                    'persons' =>$itemsIvideonPerson->person,
                    'name'=>$itemsIvideonPerson->description,
                    'photos'=>$photos
                ];
            }
        }
        return json_encode($newPersons);
    }

    //загрузка одного id_ivideon с Ivideon, которого нет в базе
    public function LoadIvideonPerson($id)
    {
        $ivideonPerson = Curl::to('http://openapi-alpha-eu01.ivideon.com/faces/'.$id.'?op=GET&access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b')
            ->withHeader('Content-Type: application/json')
            ->withHeader('Authorization: Basic access_token=100-Kc3888ffd-4287-4242-87e0-522d90b57c1b' )
            ->withData()
            ->post();
        $person= json_decode($ivideonPerson, true);
        $photos=[];

        for ($itemPhoto=0; $itemPhoto<count($person['result']['photos']); $itemPhoto++)
        {
            array_push($photos,$person['result']['photos'][$itemPhoto]['thumbnails']['original']['url']);
        }
        $result=[
            'ivideon_id' => $person['result']['id'],
            'face_gallery_id' =>$person['result']['face_gallery_id'],
            'person' => $person['result']['person'],
            'name' => $person['result']['description'],
            'photos' => $photos
        ];
        return $result;
    }
}
