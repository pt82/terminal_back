<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PersonsControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_show_action()
    {
        $personId = 'c2e43d8a-7cd8-43fd-a380-c11d7adbf5b9';
        $this->withMyToken()
            ->json('get', '/api/persons/edit/' . $personId)
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'person_id',
                    'yc_staff_id',
                    'person_ivideon_id',
                    'face_gallery_id',
                    'email',
                    'firstname',
                    'lastname',
                    'fatherland',
                    'comment',
                    'phone',
                    'terminal_name',
                    'avatar',
                    'role',
                    'department',
                    'yc',
                    'photos'
                ]
            ])
            ->assertJson([
            'success' => true,
            'message' => 'Person',
            'data' => [
                'person_id' => $personId
            ]
        ]);
    }

    public function test_userall_action()
    {
        $page = 1;
        $res = $this->withMyToken()
            ->json('get', '/api/users?page=' . $page)
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data'
            ])
            ->assertJson([
                'success' => true,
                'message' => 'List Persons',
                'data' => [
                    'current_page' => $page
                ]
            ]);
        $this->assertNotEmpty((int)$res->original['data']->toArray()['data'][0]->id);
    }

    public function test_update_action()
    {
        $userId = 13;
        $personId = '1157cbc2-1dd4-4b85-b17d-e2b7c1c7b89a';
        $res = $this->withMyToken()
            ->json('post', '/api/persons/update/' . $personId, [
                'person_id' => $personId,
                'yc_staff_id' => '',
                'person_ivideon_id' => '100-OlBom1MNS1KeGtvqLQVt',
                'face_gallery_id' => '',
                'email' => '',
                'firstname' => 'Михаил',
                'lastname' => 'Вергазов',
                'fatherland' => '',
                'comment' => '111q',
                'phone' => '9237020398',
                'terminal_name' => '',
                'avatar' => 'https://permanent.hb.bizmrg.com:443/images/100-OlBom1MNS1KeGtvqLQVt/bY5vPADmhD.jpeg',
                'role' => [],
                'department' => [],
                'yc' => [],
                'photos' => []
            ])
            ->assertStatus(200);
//        dd($res);
    }
}
