<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [

            'name' =>$this->faker->name(),
            'email'=> $this->faker->unique()->safeEmail(),
            'father_name'=>$this->faker->unique()->name(),
            'mother_name'=>$this->faker->unique()->name(),
            'mobile_no'=>$this->faker->mobileNumber,
            'sector_id'=>$this->faker->create(App\Models\Orientation\Sector::class)->id,
            'address'=>$this->faker->address,

        ];
    }
}
