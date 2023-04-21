<?php

use App\Collection;

$data = new Collection([
    'name' => 'John Doe',
    'age' => 30,
    'address' => [
        'street' => '123 Main St',
        'city' => 'New York',
        'state' => 'NY',
        'zip' => '10001',
    ],
]);

echo $data->first()->name; // John Doe

$dataList = new Collection([
    [
        'name' => 'John Doe',
        'age' => 30,
        'sex' => 'male',
        'address' => [
            'street' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'zip' => '10001',
        ],
    ],
    [
        'name' => 'Alice Doe',
        'age' => 18,
        'sex' => 'female',
        'address' => [
            'street' => '456 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'zip' => '10000',
        ],
    ],
    [
        'name' => 'Bob Doe',
        'age' => 22,
        'sex' => 'male'
    ],
    'address' => [
        'street' => '789 Main St',
        'city' => 'New York',
        'state' => 'NY',
        'zip' => '10003',
    ],
]);

echo $dataList->select(['name'])->toList(); // ['John Doe', 'Alice Doe', 'Bob Doe']
echo $dataList->map(function ($item) {
    return $item->name;
})->toList(); // ['John Doe', 'Alice Doe', 'Bob Doe']