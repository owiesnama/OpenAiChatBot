<?php

if (!function_exists('tokenize')) {
    function tokenize($text): array
    {
        $normalizedText = preg_replace("/\n+/", "\n", $text);
        $words = explode(' ', $normalizedText);
        return array_filter($words);
    }
}
