<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use jeremykenedy\LaravelRoles\Models\Role;

class CourseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Course::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $roleId = Role::where('slug', 'student')->first()->id ?? 1;
        return [
            'franchise_id' => 1,
            'user_id' => 7,
            'role_id' => $roleId,
            'title' => $this->faker->sentence(3),
        ];
    }
}
