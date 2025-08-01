<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LessonRequest extends FormRequest
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
            'title' => ['required','string','max:255'],
            ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Не запонено наименование урока',
            'title.max' => 'Слишком длинное наименование урока. Максимум 255 символов',
            ];
    }
}
