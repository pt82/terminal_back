<?php

namespace App\Http\Requests;

use App\Models\User;
use GuzzleHttp\Psr7\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
       public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => [
                'email',
                 Rule::unique('users')->ignore(\Auth::id()),
            ],
            'phone' => [
                 'required','min:11','max:11',
                  Rule::unique('users'),
             ],
           ];
    }
    protected function prepareForValidation()
    {
        $this->merge([
            'phone' => '7'.(preg_replace('/[^0-9]/', '', $this->phone)),
            ]);
    }

    public function messages()
    {
        return [
              'email.unique' => 'Почтовый адрес '. $this->email. ' занят.',
              'email.email' => 'Почтовый адрес '. $this->email. ' имеет неверный формат.',
              'phone.unique'=> 'Номер телефона +' .$this->phone. ' занят.',
              'phone.required'=> 'Не указан номер телефона',
              'phone.min'=> 'Недопустимое количество цифр +' .$this->phone,
              'phone.max'=> 'Недопустимое количество цифр +' .$this->phone,
             ];
    }
}
