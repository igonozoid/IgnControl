<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => $this->faker->sentence(4),
            'status' => 'pending',
            'due_date' => $this->faker->dateTimeBetween('-5 days', '+30 days')->format('Y-m-d'),
        ];
    }
}
