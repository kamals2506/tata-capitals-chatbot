<?php

if (!function_exists('t')) {
    function t($key){
        $lang = session()->get('lang') ?? 'en';

        $text = [
            'en' => ['start' => 'Start Simulation'],
            'hi' => ['start' => '???????? ???? ????']
        ];

        return $text[$lang][$key] ?? $key;
    }
}