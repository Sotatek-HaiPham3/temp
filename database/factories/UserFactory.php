<?php

use App\Models\User;
use App\Consts;
use Illuminate\Support\Str;
use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
	$gender        = $faker->randomElements(['male', 'female'])[0];
    $genderInt     = $gender == 'male' ? 1 : 0;
	$streetAddress = $faker->streetAddress;
	$city          = $faker->city;
	$state         = $faker->state;
	$address       = $streetAddress.', '.$city.', '.$state;
    $firstName     = $faker->firstName($gender);
    $lastName      = $faker->lastName($gender);
    $fullName      = $firstName.' '.$lastName;
    return [
        'full_name'       => $fullName,
        'email'          => $faker->unique()->safeEmail,
        'email_verified' => Consts::TRUE,
        'first_name'     => $firstName,
        'last_name'      => $lastName,
        'phone_no'       => $faker->phoneNumber,
        'gender'         => $genderInt,
        'address_1'      => $address,
        'address_2'      => $address,
        'state'          => $state,
        'city'           => $city,
        'status'         => Consts::USER_ACTIVE,
        'avatar'         => '/images/no_avatar.svg',
        'zipCode'        => Str::random(10),
        'headline'       => $faker->realText($maxNbChars = 50, $indexSize = 1),
        'rating'         => mt_rand(70, 100),
        'profile_summary'=> $faker->realText($maxNbChars = 200, $indexSize = 2),
        'password'       => bcrypt('123123'), // password
        'remember_token' => Str::random(10),
    ];
});
