<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/*
Diagnostic tool: Shows exactly what data is found in the HTML
for each field category. Use this to debug why fields are missing.

Usage: php diagnose_listing.php --id=123
*/

$RAW_ID = null;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--id=')) {
        $RAW_ID = max(1, (int)substr($arg, 5));
    }
}

if ($RAW_ID === null) {
    echo "Usage: php diagnose_listing.php --id=123\n";
    exit(1);
}

function extract_contexts(string $html): array
{
    // Remove noise
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
    $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html) ?? $html;
    
    $result = [
        'title' => '',
        'meta' => [],
        'headers' => [],
        'price_contexts' => [],
        'area_contexts' => [],
        'room_contexts' => [],
        'bathroom_contexts' => [],
        'agent_contexts' => [],
        'phone_contexts' => [],
        'email_contexts' => [],
    ];
    
    // Title
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
        $result['title'] = trim(strip_tags($m[1]));
    }
    
    // Meta tags
    if (preg_match_all('/<meta\s+(?:property|name)="([^"]+)"\s+content="([^"]+)"/i', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $result['meta'][$match[1]] = $match[2];
        }
    }
    
    // Headers
    if (preg_match_all('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $html, $matches)) {
        foreach ($matches[1] as $h) {
            $text = trim(strip_tags($h));
            if (strlen($text) > 3) {
                $result['headers'][] = $text;
            }
        }
    }
    
    // Price contexts
    if (preg_match_all('/([^<>]{0,50}(?:€|EUR|precio|price)[^<>]{0,50})/iu', $html, $matches)) {
        foreach ($matches[1] as $ctx) {
            $clean = trim(strip_tags($ctx));
            if (strlen($clean) > 5 && preg_match('/\d/', $clean)) {
                $result['price_contexts'][] = $clean;
            }
        }
    }
    
    // Area contexts
    if (preg_match_all('/([^<>]{0,60}(?:m²|m2|sqm|superficie|construidos|built)[^<>]{0,60})/iu', $html, $matches)) {
        foreach ($matches[1] as $ctx) {
            $clean = trim(strip_tags($ctx));
            if (strlen($clean) > 5 && preg_match('/\d/', $clean)) {
                $result['area_contexts'][] = $clean;
            }
        }
    }
    
    // Room contexts
    if (preg_match_all('/([^<>]{0,50}(?:bedroom|dormitorio|habitacion|room|hab\.|dorm\.)[^<>]{0,50})/iu', $html, $matches)) {
        foreach ($matches[1] as $ctx) {
            $clean = trim(strip_tags($ctx));
            if (strlen($clean) > 3 && preg_match('/\d/', $clean)) {
                $result['room_contexts'][] = $clean;
            }
        }
    }
    
    // Bathroom contexts
    if (preg_match_all('/([^<>]{0,50}(?:bathroom|baño|bano|bath|aseo|wc)[^<>]{0,50})/iu', $html, $matches)) {
        foreach ($matches[1] as $ctx) {
            $clean = trim(strip_tags($ctx));
            if (strlen($clean) > 3 && preg_match('/\d/', $clean)) {
                $result['bathroom_contexts'][] = $clean;
            }
        }
    }
    
    // Agent contexts
    $agentPatterns = [
        '/<div[^>]*class="[^"]*(?:agent|agency|broker|contact|seller)[^"]*"[^>]*>(.*?)<\/div>/is',
        '/<span[^>]*class="[^"]*(?:agent|agency)[^"]*"[^>]*>(.*?)<\/span>/is',
        '/(?:agente|agencia|inmobiliaria|agent|agency|broker)[:\s]+([^<>\n]{5,80})/iu',
    ];
    foreach ($agentPatterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $ctx) {
                $clean = trim(strip_tags($ctx));
                if (strlen($clean) > 3 && strlen($clean) < 100) {
                    $result['agent_contexts'][] = $clean;
                }
            }
        }
    }
    
    // Phone contexts
    if (preg_match_all('/(\+?\d{1,3}[\s\-\.]?\(?\d{2,3}\)?[\s\-\.]?\d{3}[\s\-\.]?\d{3,4})/u', $html, $matches)) {
        foreach ($matches[1] as $phone) {
            $clean = trim($phone);
            if (preg_match_all('/\d/', $clean) >= 9) {
                $result['phone_contexts'][] = $clean;
            }
        }
    }
    
    // Email contexts
    if (preg_match_all('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/u', $html, $matches)) {
        foreach ($matches[1] as $email) {
            $email = strtolower(trim($email));
            if (!str_contains($email, 'example.') && 
                !str_contains($email, 'test@') &&
                !str_contains($email, 'noreply@')) {
                $result['email_contexts'][] = $email;
            }
        }
    }
    
    // Deduplicate
    foreach (array_keys($result) as $key) {
        if (is_array($result[$key])) {
            $result[$key] = array_values(array_unique($result[$key]));
        }
    }
    
    return $result;
}

// Main script
$pdo->exec('USE test2firstlisting');

$st = $pdo->prepare("SELECT id, url, html_raw, text_raw, jsonld_raw FROM raw_pages WHERE id = :id");
$st->execute([':id' => $RAW_ID]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "No listing found with ID {$RAW_ID}\n";
    exit(1);
}

$url = $row['url'];
$html = $row['html_raw'] ?? '';
$text = $row['text_raw'] ?? '';
$jsonld = $row['jsonld_raw'] ?? '';

echo "================================================================================\n";
echo "DIAGNOSTIC REPORT FOR LISTING ID: {$RAW_ID}\n";
echo "================================================================================\n";
echo "URL: {$url}\n\n";

// Check JSON-LD
if ($jsonld) {
    echo "✓ JSON-LD DATA FOUND\n";
    $data = json_decode($jsonld, true);
    if (is_array($data)) {
        echo "  Keys: " . implode(', ', array_keys($data)) . "\n";
    }
    echo "\n";
} else {
    echo "✗ NO JSON-LD DATA\n\n";
}

// Extract contexts
$contexts = extract_contexts($html);

echo "TITLE:\n";
echo "  " . ($contexts['title'] ?: '(not found)') . "\n\n";

echo "META TAGS:\n";
if (!empty($contexts['meta'])) {
    foreach ($contexts['meta'] as $name => $content) {
        echo "  {$name}: " . substr($content, 0, 80) . "\n";
    }
} else {
    echo "  (none found)\n";
}
echo "\n";

echo "HEADERS (H1-H3):\n";
if (!empty($contexts['headers'])) {
    foreach (array_slice($contexts['headers'], 0, 10) as $h) {
        echo "  - {$h}\n";
    }
} else {
    echo "  (none found)\n";
}
echo "\n";

echo "PRICE CONTEXTS:\n";
if (!empty($contexts['price_contexts'])) {
    foreach (array_slice($contexts['price_contexts'], 0, 10) as $ctx) {
        echo "  • {$ctx}\n";
    }
} else {
    echo "  ✗ NO PRICE CONTEXTS FOUND\n";
}
echo "\n";

echo "AREA CONTEXTS (sqm):\n";
if (!empty($contexts['area_contexts'])) {
    foreach (array_slice($contexts['area_contexts'], 0, 10) as $ctx) {
        echo "  • {$ctx}\n";
    }
} else {
    echo "  ✗ NO AREA CONTEXTS FOUND\n";
}
echo "\n";

echo "ROOM CONTEXTS:\n";
if (!empty($contexts['room_contexts'])) {
    foreach (array_slice($contexts['room_contexts'], 0, 10) as $ctx) {
        echo "  • {$ctx}\n";
    }
} else {
    echo "  ✗ NO ROOM CONTEXTS FOUND\n";
}
echo "\n";

echo "BATHROOM CONTEXTS:\n";
if (!empty($contexts['bathroom_contexts'])) {
    foreach (array_slice($contexts['bathroom_contexts'], 0, 10) as $ctx) {
        echo "  • {$ctx}\n";
    }
} else {
    echo "  ✗ NO BATHROOM CONTEXTS FOUND\n";
}
echo "\n";

echo "AGENT/AGENCY CONTEXTS:\n";
if (!empty($contexts['agent_contexts'])) {
    foreach (array_slice($contexts['agent_contexts'], 0, 10) as $ctx) {
        echo "  • {$ctx}\n";
    }
} else {
    echo "  ✗ NO AGENT CONTEXTS FOUND\n";
}
echo "\n";

echo "PHONE CONTEXTS:\n";
if (!empty($contexts['phone_contexts'])) {
    foreach (array_slice($contexts['phone_contexts'], 0, 10) as $ctx) {
        echo "  • {$ctx}\n";
    }
} else {
    echo "  ✗ NO PHONE CONTEXTS FOUND\n";
}
echo "\n";

echo "EMAIL CONTEXTS:\n";
if (!empty($contexts['email_contexts'])) {
    foreach (array_slice($contexts['email_contexts'], 0, 10) as $ctx) {
        echo "  • {$ctx}\n";
    }
} else {
    echo "  ✗ NO EMAIL CONTEXTS FOUND\n";
}
echo "\n";

echo "TEXT_RAW SAMPLE (first 500 chars):\n";
echo "  " . substr($text, 0, 500) . "...\n\n";

echo "================================================================================\n";
echo "DIAGNOSIS COMPLETE\n";
echo "================================================================================\n";
echo "\nIf a field shows '✗ NO CONTEXTS FOUND', the data is either:\n";
echo "  1. Not present in the HTML\n";
echo "  2. Loaded via JavaScript (check text_raw above)\n";
echo "  3. In a format the regex doesn't recognize\n";
echo "\nIf contexts ARE found but AI still misses them:\n";
echo "  1. Try a larger model (qwen2.5:14b or 32b)\n";
echo "  2. The contexts might be ambiguous\n";
echo "  3. Run with --debug to see what's sent to the model\n";
