<?php

// National-number patterns exclude the international calling code.
return [
    'PH' => ['name' => 'Philippines', 'code' => '63', 'pattern' => '9[0-9]{9}', 'example' => '9123456789'],
    'US' => ['name' => 'United States', 'code' => '1', 'pattern' => '[2-9][0-9]{9}', 'example' => '2025550123'],
    'CA' => ['name' => 'Canada', 'code' => '1', 'pattern' => '[2-9][0-9]{9}', 'example' => '4165550123'],
    'GB' => ['name' => 'United Kingdom', 'code' => '44', 'pattern' => '[1-9][0-9]{9}', 'example' => '7700900123'],
    'AU' => ['name' => 'Australia', 'code' => '61', 'pattern' => '[1-9][0-9]{8}', 'example' => '412345678'],
    'NZ' => ['name' => 'New Zealand', 'code' => '64', 'pattern' => '[1-9][0-9]{7,9}', 'example' => '211234567'],
    'SG' => ['name' => 'Singapore', 'code' => '65', 'pattern' => '[3689][0-9]{7}', 'example' => '81234567'],
    'MY' => ['name' => 'Malaysia', 'code' => '60', 'pattern' => '[1-9][0-9]{7,9}', 'example' => '123456789'],
    'ID' => ['name' => 'Indonesia', 'code' => '62', 'pattern' => '[1-9][0-9]{7,11}', 'example' => '81234567890'],
    'TH' => ['name' => 'Thailand', 'code' => '66', 'pattern' => '[1-9][0-9]{7,8}', 'example' => '812345678'],
    'VN' => ['name' => 'Vietnam', 'code' => '84', 'pattern' => '[1-9][0-9]{8,9}', 'example' => '912345678'],
    'JP' => ['name' => 'Japan', 'code' => '81', 'pattern' => '[1-9][0-9]{8,9}', 'example' => '9012345678'],
    'KR' => ['name' => 'South Korea', 'code' => '82', 'pattern' => '[1-9][0-9]{7,9}', 'example' => '1012345678'],
    'CN' => ['name' => 'China', 'code' => '86', 'pattern' => '[1-9][0-9]{9,10}', 'example' => '13123456789'],
    'IN' => ['name' => 'India', 'code' => '91', 'pattern' => '[1-9][0-9]{9}', 'example' => '9123456789'],
    'AE' => ['name' => 'United Arab Emirates', 'code' => '971', 'pattern' => '[1-9][0-9]{7,8}', 'example' => '501234567'],
    'SA' => ['name' => 'Saudi Arabia', 'code' => '966', 'pattern' => '[1-9][0-9]{8}', 'example' => '512345678'],
    'QA' => ['name' => 'Qatar', 'code' => '974', 'pattern' => '[1-9][0-9]{7}', 'example' => '55123456'],
    'HK' => ['name' => 'Hong Kong', 'code' => '852', 'pattern' => '[1-9][0-9]{7}', 'example' => '51234567'],
    'TW' => ['name' => 'Taiwan', 'code' => '886', 'pattern' => '[1-9][0-9]{7,8}', 'example' => '912345678'],
    'DE' => ['name' => 'Germany', 'code' => '49', 'pattern' => '[1-9][0-9]{6,12}', 'example' => '15123456789'],
    'FR' => ['name' => 'France', 'code' => '33', 'pattern' => '[1-9][0-9]{8}', 'example' => '612345678'],
    'IT' => ['name' => 'Italy', 'code' => '39', 'pattern' => '[0-9]{6,11}', 'example' => '3123456789'],
    'ES' => ['name' => 'Spain', 'code' => '34', 'pattern' => '[1-9][0-9]{8}', 'example' => '612345678'],
];
