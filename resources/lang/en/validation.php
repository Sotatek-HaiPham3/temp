<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'The :attribute must be accepted.',
    'active_url' => 'The :attribute is not a valid URL.',
    'after' => 'The :attribute must be a date after :date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'alpha' => 'The :attribute may only contain letters.',
    'alpha_dash' => 'The :attribute may only contain letters, numbers, dashes and underscores.',
    'alpha_num' => 'The :attribute may only contain letters and numbers.',
    'array' => 'The :attribute must be an array.',
    'before' => 'The :attribute must be a date before :date.',
    'before_or_equal' => 'The :attribute must be a date before or equal to :date.',
    'between' => [
        'numeric' => 'The :attribute must be between :min and :max.',
        'file' => 'The :attribute must be between :min and :max kilobytes.',
        'string' => 'The :attribute must be between :min and :max characters.',
        'array' => 'The :attribute must have between :min and :max items.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'confirmed' => 'The :attribute does not match.',
    'date' => 'The :attribute is not a valid date.',
    'date_equals' => 'The :attribute must be a date equal to :date.',
    'date_format' => 'The :attribute does not match the format :format.',
    'different' => 'The :attribute and :other must be different.',
    'digits' => 'The :attribute must be :digits digits.',
    'digits_between' => 'The :attribute must be between :min and :max digits.',
    'dimensions' => 'The :attribute has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'email' => 'The :attribute must be a valid email address.',
    'ends_with' => 'The :attribute must end with one of the following: :values',
    'exists' => 'The selected :attribute is invalid.',
    'file' => 'The :attribute must be a file.',
    'filled' => 'The :attribute field must have a value.',
    'gt' => [
        'numeric' => 'The :attribute must be greater than :value.',
        'file' => 'The :attribute must be greater than :value kilobytes.',
        'string' => 'The :attribute must be greater than :value characters.',
        'array' => 'The :attribute must have more than :value items.',
    ],
    'gte' => [
        'numeric' => 'The :attribute must be greater than or equal :value.',
        'file' => 'The :attribute must be greater than or equal :value kilobytes.',
        'string' => 'The :attribute must be greater than or equal :value characters.',
        'array' => 'The :attribute must have :value items or more.',
    ],
    'image' => 'The :attribute must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field does not exist in :other.',
    'integer' => 'The :attribute must be an integer.',
    'ip' => 'The :attribute must be a valid IP address.',
    'ipv4' => 'The :attribute must be a valid IPv4 address.',
    'ipv6' => 'The :attribute must be a valid IPv6 address.',
    'json' => 'The :attribute must be a valid JSON string.',
    'lt' => [
        'numeric' => 'The :attribute must be less than :value.',
        'file' => 'The :attribute must be less than :value kilobytes.',
        'string' => 'The :attribute must be less than :value characters.',
        'array' => 'The :attribute must have less than :value items.',
    ],
    'lte' => [
        'numeric' => 'The :attribute must be less than or equal :value.',
        'file' => 'The :attribute must be less than or equal :value kilobytes.',
        'string' => 'The :attribute must be less than or equal :value characters.',
        'array' => 'The :attribute must not have more than :value items.',
    ],
    'max' => [
        'numeric' => 'The :attribute may not be greater than :max.',
        'file' => 'The :attribute may not be greater than :max kilobytes.',
        'string' => 'The :attribute may not be greater than :max characters.',
        'array' => 'The :attribute may not have more than :max items.',
    ],
    'mimes' => 'The :attribute must be a file of type: :values.',
    'mimetypes' => 'The :attribute must be a file of type: :values.',
    'min' => [
        'numeric' => 'The :attribute must be at least :min.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'string' => 'The :attribute must be at least :min characters.',
        'array' => 'The :attribute must have at least :min items.',
    ],
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute is invalid.',
    'numeric' => 'The :attribute must be a number.',
    'present' => 'The :attribute field must be present.',
    'regex' => 'The :attribute is invalid.',
    'required' => 'The :attribute field is required.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'The :attribute and :other must match.',
    'size' => [
        'numeric' => 'The :attribute must be :size.',
        'file' => 'The :attribute must be :size kilobytes.',
        'string' => 'The :attribute must be :size characters.',
        'array' => 'The :attribute must contain :size items.',
    ],
    'starts_with' => 'The :attribute must start with one of the following: :values',
    'string' => 'The :attribute must be a string.',
    'timezone' => 'The :attribute must be a valid zone.',
    'unique' => 'The :attribute has already been taken.',
    'unique_vehicle_num' => 'The :attribute has already been taken.',
    'unique_mo_num' => 'The :attribute has already been taken.',
    'uploaded' => 'The :attribute failed to upload.',
    'url' => 'The :attribute format is invalid.',
    'uuid' => 'The :attribute must be a valid UUID.',
    'unique_email' => 'The email has already been taken.',
    'unique_username' => 'The username has already been taken.',
    'unique_phone_number' => 'The phone number has already been taken.',
    'password_white_space' => 'The :attribute should not contain white space.',
    'correct_password' => 'That password was incorrect. Please try again .',
    'game_profile_exists' => 'A session for this game already exists.',
    'rank_exists' => 'The selected :attribute is invalid.',
    'server_exists' => 'The selected :attribute is invalid.',
    'platform_exists' => 'The selected :attribute is invalid.',
    'belong_gamelancer' => 'The game profile is invalid.',
    'valid_offer_type' => 'The :attribute is invalid.',
    'valid_offer_price' => 'The :attribute is invalid.',
    'is_offer_type' => 'The :attribute must be a offer type.',
    'valid_invitation_code' => 'The :attribute is invalid.',
    'provider_valid' => 'The provider cannot support.',
    'social_link_valid' => 'The social link isn\'t supported, please used the full URL.',
    'bounty_already_review' => 'You\'ve already reviewed this Bounty.',
    'session_already_review' => 'You\'ve already reviewed this Session.',
    'exists_username' => 'This username does not exist.',
    'phone' => 'The :attribute field contains an invalid number.',
    'valid_phone_contry_code' => 'The :attribute is invalid.',
    'social_type_valid' => 'The social network is not support.',
    'verified_account' => 'The account is not activated.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
        'password' => [
            'regex' => 'The password must contain between 6 and 72 characters including capital letters, lowercase letters, numbers.',
        ],
        'dob' => [
            'before' => 'Your age must be more than 13.',
            'after' => 'Your age must be less than 120.',
            'date_format' => 'The date of birth must be valid and in the format DD/MM/YYYY.',
        ],
        'username' => [
            'regex' => 'The username field is invalid.'
        ],
        'audio' => [
            'max' => 'The :attribute must be less or equal to 2MB.'
        ],
        'card_exp_month' => [
            'length' => 'The month field is invalid'
        ],
        'username' => [
            'exists' => 'The :attribute is invalid.'
        ],
        'username' => [
            'special_characters' => 'The :attribute not allowing certain special characters.'
        ],
        'email' => [
            'regex' => 'The :attribute only letters (a-z), numbers (0-9), and periods (.) are allowed.'
        ],
        'receiver_id' => [
            'not_in' => 'You can not tip for yourself.'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'dob' => 'date of birth',
        'total_hours' => 'available hours',
        'social_link' => 'social account link',
        'introduction' => 'become a gamelancer reason',
        'rank_id' => 'game rank',
        'offers.*.price' => 'offer price',
        'offers.*.quantity' => 'offer quantity',
        'offers.*.type' => 'offer type',
        'medias.*.url' => 'media url',
        'medias.*.type' => 'media type',
        'match_servers.*' => 'match server',
        'platform_ids.*' => 'game platform',
        'session.rank_id' => 'game rank',
        'session.offers.*.price' => 'offer price',
        'session.offers.*.quantity' => 'offer quantity',
        'session.offers.*.type' => 'offer type',
        'session.medias.*.url' => 'media url',
        'session.medias.*.type' => 'media type',
        'session.match_servers.*' => 'match server',
        'session.platform_ids.*' => 'game platform',
        'available_times.*.weekday' => 'day of week',
        'available_times.*.from' => 'available time',
        'available_times.*.to' => 'available time',
        'available_times.*.all' => 'available time',
        'user_level_meta_id' => 'require level',
        'invitation_code' => 'invitation code',
        'real_amount' => 'amount',
        'exchange_offer_id' => 'offer',
        'channel_id' => 'channel',
        'opposite_user_id' => 'opposite user',
        'report_user_id' => 'report user',
        'user_interests_game_id' => 'interests game',
        '*.game_id' => 'game id',
        '*.platform_id' => 'platform',
        '*.server_ids' => 'server game',
        'server_ids' => 'server game',
        '*.server_ids.*' => 'server game',
        '*.game_name' => 'username',
        'prioritize' => 'prioritize',
        'social_networks.social_id' => 'social network id',
        'social_networks.social_type' => 'social network type'
    ],
];
