<?php

return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute must be a valid email address.',
    'min' => [
        'string' => 'The :attribute must be at least :min characters.',
        'numeric' => 'The :attribute must be at least :min.',
    ],
    'max' => [
        'string' => 'The :attribute may not be greater than :max characters.',
        'numeric' => 'The :attribute may not be greater than :max.',
        'file' => 'The :attribute may not be greater than :max kilobytes.',
        'array' => 'The :attribute may not have more than :max items.',
    ],
    'unique' => 'The :attribute has already been taken.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'string' => 'The :attribute must be a string.',
    'numeric' => 'The :attribute must be a number.',
    'integer' => 'The :attribute must be an integer.',
    'in' => 'The selected :attribute is invalid.',
    'exists' => 'The selected :attribute does not exist.',
    'image' => 'The :attribute must be an image.',
    'uploaded' => 'We could not upload :attribute. Try a smaller image (preferably under 5 MB).',
    'mimes' => 'The :attribute must be a file of type: :values.',
    'date' => 'The :attribute is not a valid date.',
    'after' => 'The :attribute must be a date after :date.',
    'before' => 'The :attribute must be a date before :date.',

    'attributes' => [
        'name' => 'name',
        'email' => 'email',
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'phone' => 'phone',
        'title' => 'title',
        'description' => 'description',
        'price' => 'price',
        'location' => 'location',
        'category_id' => 'category',
        'image' => 'image',
        'images' => 'ad images',
        'images.*' => 'ad image',
    ],
];

