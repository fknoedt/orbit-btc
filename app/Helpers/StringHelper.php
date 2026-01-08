<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class StringHelper
{
    static function extractErrorMessage(string $input): string
    {
        $input = trim($input);

        $message = null;

        // Define error keys/tags once
        $errorKeys = ['error', 'message', 'err', 'msg', 'description', 'detail', 'error message'];
        $errorMessages = ['error', 'failed', 'exception', 'not found'];

        // First, check if pure text (no tags, no JSON-like structures)
        if (!preg_match('/<[^<]+>/', $input) && !Str::startsWith($input, ['{', '['])) {
            $message = $input;
        } else {
            // Try JSON
            $jsonData = @json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                foreach ($jsonData as $key => $value) {
                    if (in_array(Str::lower($key), $errorKeys) && is_string($value)) {
                        $message = $value;
                        break;
                    }
                }
                // XML/HTML raw check with regex
                // Check if likely HTML (has <html> or <body>)
            } elseif (preg_match('/<html/i', $input) || preg_match('/<body/i', $input)) {
                // Extract content within <body> if present, strip tags
                if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $input, $matches)) {
                    $bodyContent = trim(strip_tags($matches[1]));
                    if (!empty($bodyContent) && strlen($bodyContent) < 500) { // Assume short body is just the message
                        $message = $bodyContent;
                    }
                }
                // Fallback: whole stripped text if error-like
                $text = trim(strip_tags($input));
                if (Str::contains(Str::lower($text), $errorMessages)) {
                    $message = $text;
                }
            } else {
                // Assume XML: regex for error tags
                foreach ($errorKeys as $tag) {
                    if (preg_match('/<' . preg_quote($tag, '/') . '[^>]*>(.*?)<\/' . preg_quote($tag, '/') . '>/is', $input, $matches)) {
                        $message = trim(strip_tags($matches[1]));
                        break;
                    }
                }
            }

            if (! $message) {
                // Ultimate fallback: strip all tags and return if error-like
                $text = trim(strip_tags($input));
                if (Str::contains(Str::lower($text), $errorMessages)) {
                    $message = $text;
                }
            }
        }

        return $message ?? $input;
    }
}
