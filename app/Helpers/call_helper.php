<?php

function analyzeCall($call) {

    $text = strtolower($call->transcript ?? '');

    // Keyword detection
    $compliance = (strpos($text, 'policy') !== false) 
        ? "? Compliant" 
        : "? Missing Compliance";

    // Sentiment
    $sentiment = $call->sentiment ?? "Neutral";

    // Talk Ratio
    $agent = $call->agent_talk_time ?? 1;
    $customer = $call->customer_talk_time ?? 1;

    $ratio = round(($agent / ($agent + $customer)) * 100);

    return [
        'compliance' => $compliance,
        'sentiment'  => $sentiment,
        'ratio'      => $ratio
    ];
}