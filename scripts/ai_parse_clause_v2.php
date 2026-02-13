<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

/*
2-Stage AI Parser optimized for Apple Silicon (M3)
Stage 1: Extract structured fields (price, sqm, rooms, etc)
Stage 2: Extract full description (separate call to avoid truncation)

Recommended models for M3 MacBook Pro:
- qwen2.5:14b-instruct (best balance, ~10GB RAM)
- llama3.1:8b-instruct (faster, good quality)
- mistral:7b-instruct-q4 (lighter, still decent)
*/

$OLLAMA_URL = 'http://localhost:11434/api/generate';
$MODEL = 'qwen2.5:14b-instruct';  // Default to 14B for M3
$LIMIT = 1;
$FORCE = false;
$RAW_ID = null;
$DEBUG = false;
$TWO_STAGE = true;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $LIMIT = max(1, (int)substr($arg, 8));
    } elseif (str_starts_with($arg, '--model=')) {
        $MODEL = trim((string)substr($arg, 8));
    } elseif (str_starts_with($arg, '--id=')) {
        $RAW_ID = max(1, (int)substr($arg, 5));
    } elseif ($arg === '--force') {
        $FORCE = true;
    } elseif ($arg === '--debug') {
        $DEBUG = true;
    } elseif ($arg === '--single-stage') {
        $TWO_STAGE = false;
    }
}

function scalar_text($v): ?string
{
    if ($v === null) return null;
    if (is_string($v) || is_int($v) || is_float($v) || is_bool($v)) {
        $t = trim((string)$v);
        return $t === '' ? null : $t;
    }
    if (is_array($v)) {
        $parts = [];
        foreach ($v as $item) {
            $s = scalar_text($item);
            if ($s !== null) $parts[] = $s;
        }
        return $parts ? trim(implode(', ', $parts)) : null;
    }
    return null;
}

function txt_or_none($v): string
{
    $s = scalar_text($v);
    return $s === null ? 'None' : $s;
}

function int_or_null($v): ?int
{
    $s = scalar_text($v);
    if ($s === null) return null;
    if (is_numeric($s)) return (int)$s;
    $n = preg_replace('/[^0-9]/', '', $s) ?? '';
    return $n === '' ? null : (int)$n;
}

function pick_first(array $source, array $keys)
{
    foreach ($keys as $k) {
        if (array_key_exists($k, $source) && $source[$k] !== null && $source[$k] !== '') {
            return $source[$k];
        }
    }
    return null;
}

/**
 * Extract JSON-LD schema.org data
 */
function extract_jsonld(?string $jsonldRaw): ?array
{
    if (!$jsonldRaw) return null;
    
    $data = json_decode($jsonldRaw, true);
    if (!is_array($data)) return null;
    
    // Handle @graph format
    if (isset($data['@graph']) && is_array($data['@graph'])) {
        foreach ($data['@graph'] as $item) {
            if (isset($item['@type'])) {
                $type = $item['@type'];
                if (is_string($type) && (
                    stripos($type, 'RealEstate') !== false || 
                    stripos($type, 'Accommodation') !== false ||
                    stripos($type, 'Apartment') !== false ||
                    stripos($type, 'House') !== false ||
                    $type === 'Product'
                )) {
                    return $item;
                }
            }
        }
    }
    
    // Direct format
    if (isset($data['@type'])) {
        $type = $data['@type'];
        if (is_string($type) && (
            stripos($type, 'RealEstate') !== false || 
            stripos($type, 'Accommodation') !== false ||
            stripos($type, 'Apartment') !== false ||
            stripos($type, 'House') !== false ||
            $type === 'Product'
        )) {
            return $data;
        }
    }
    
    return null;
}

/**
 * Smart HTML preprocessing - extract only relevant data
 */
function extract_relevant_data(string $html): array
{
    // Remove noise
    $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
    $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html) ?? $html;
    $html = preg_replace('/<nav\b[^>]*>.*?<\/nav>/is', '', $html) ?? $html;
    $html = preg_replace('/<footer\b[^>]*>.*?<\/footer>/is', '', $html) ?? $html;
    
    $result = [
        'title' => '',
        'meta' => [],
        'headers' => [],
        'price_contexts' => [],
        'area_contexts' => [],
        'room_contexts' => [],
        'main_content' => ''
    ];
    
    // Extract title
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
        $result['title'] = trim(strip_tags($m[1]));
    }
    
    // Extract meta tags
    if (preg_match_all('/<meta\s+(?:property|name)="([^"]+)"\s+content="([^"]+)"/i', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $result['meta'][$match[1]] = $match[2];
        }
    }
    
    // Extract h1, h2, h3
    if (preg_match_all('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $html, $matches)) {
        foreach ($matches[1] as $h) {
            $text = trim(strip_tags($h));
            if (strlen($text) > 3) {
                $result['headers'][] = $text;
            }
        }
    }
    
    // Find price contexts (look for € or EUR with surrounding text)
    if (preg_match_all('/([^<>]{0,50}(?:€|EUR|precio|price)[^<>]{0,50})/iu', $html, $matches)) {
        foreach ($matches[1] as $ctx) {
            $clean = trim(strip_tags($ctx));
            if (strlen($clean) > 5 && preg_match('/\d/', $clean)) {
                $result['price_contexts'][] = $clean;
            }
        }
    }
    
    // Find area contexts (m², sqm, superficie)
    if (preg_match_all('/([^<>]{0,60}(?:m²|m2|sqm|superficie|construidos|built)[^<>]{0,60})/iu', $html, $matches)) {
        foreach ($matches[1] as $ctx) {
            $clean = trim(strip_tags($ctx));
            if (strlen($clean) > 5 && preg_match('/\d/', $clean)) {
                $result['area_contexts'][] = $clean;
            }
        }
    }
    
    // Find room contexts (bedrooms, habitaciones, dormitorios)
    if (preg_match_all('/([^<>]{0,50}(?:bedroom|dormitorio|habitacion|room|hab\.|dorm\.)[^<>]{0,50})/iu', $html, $matches)) {
        foreach ($matches[1] as $ctx) {
            $clean = trim(strip_tags($ctx));
            if (strlen($clean) > 3 && preg_match('/\d/', $clean)) {
                $result['room_contexts'][] = $clean;
            }
        }
    }
    
    // Find bathroom contexts (baños, bathrooms)
    if (preg_match_all('/([^<>]{0,50}(?:bathroom|baño|bano|bath|aseo|wc)[^<>]{0,50})/iu', $html, $matches)) {
        foreach ($matches[1] as $ctx) {
            $clean = trim(strip_tags($ctx));
            if (strlen($clean) > 3 && preg_match('/\d/', $clean)) {
                $result['bathroom_contexts'][] = $clean;
            }
        }
    }
    
    // Find agent/agency info
    $result['agent_contexts'] = [];
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
    
    // Find phone numbers (various formats)
    $result['phone_contexts'] = [];
    // Match formats: +34 123 456 789, 123-456-789, (123) 456 789, etc
    if (preg_match_all('/(\+?\d{1,3}[\s\-\.]?\(?\d{2,3}\)?[\s\-\.]?\d{3}[\s\-\.]?\d{3,4})/u', $html, $matches)) {
        foreach ($matches[1] as $phone) {
            $clean = trim($phone);
            // Must have at least 9 digits
            if (preg_match_all('/\d/', $clean) >= 9) {
                $result['phone_contexts'][] = $clean;
            }
        }
    }
    
    // Find email addresses
    $result['email_contexts'] = [];
    if (preg_match_all('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/u', $html, $matches)) {
        foreach ($matches[1] as $email) {
            $email = strtolower(trim($email));
            // Filter out common fake/example emails
            if (!str_contains($email, 'example.') && 
                !str_contains($email, 'test@') &&
                !str_contains($email, 'noreply@')) {
                $result['email_contexts'][] = $email;
            }
        }
    }
    
    // Extract main content areas
    $contentPatterns = [
        '/<div[^>]*class="[^"]*(?:description|content|detail|property-info|features)[^"]*"[^>]*>(.*?)<\/div>/is',
        '/<article[^>]*>(.*?)<\/article>/is',
        '/<section[^>]*class="[^"]*(?:main|property|listing)[^"]*"[^>]*>(.*?)<\/section>/is',
    ];
    
    $contentParts = [];
    foreach ($contentPatterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $content) {
                $text = strip_tags($content);
                $text = preg_replace('/\s+/', ' ', $text);
                $text = trim($text);
                if (strlen($text) > 50) {
                    $contentParts[] = substr($text, 0, 1000);
                }
            }
        }
    }
    
    $result['main_content'] = implode("\n\n", array_slice($contentParts, 0, 3));
    
    // Deduplicate
    $result['price_contexts'] = array_unique(array_slice($result['price_contexts'], 0, 5));
    $result['area_contexts'] = array_unique(array_slice($result['area_contexts'], 0, 5));
    $result['room_contexts'] = array_unique(array_slice($result['room_contexts'], 0, 5));
    $result['bathroom_contexts'] = array_unique(array_slice($result['bathroom_contexts'] ?? [], 0, 5));
    $result['agent_contexts'] = array_unique(array_slice($result['agent_contexts'] ?? [], 0, 5));
    $result['phone_contexts'] = array_unique(array_slice($result['phone_contexts'] ?? [], 0, 3));
    $result['email_contexts'] = array_unique(array_slice($result['email_contexts'] ?? [], 0, 3));
    
    return $result;
}

/**
 * Stage 1: Extract structured fields (NOT description)
 */
function extract_structured_fields(
    string $ollamaUrl,
    string $model,
    ?array $jsonld,
    array $htmlData,
    ?string $textRaw,
    bool $debug,
    ?string &$error = null
): ?array {
    $error = null;
    
    // Build focused input
    $input = "";
    
    if ($jsonld) {
        $input .= "=== JSON-LD STRUCTURED DATA ===\n";
        $input .= json_encode($jsonld, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    }
    
    $input .= "=== PAGE TITLE ===\n{$htmlData['title']}\n\n";
    
    if (!empty($htmlData['meta'])) {
        $input .= "=== META TAGS ===\n";
        foreach ($htmlData['meta'] as $name => $content) {
            if (stripos($name, 'description') !== false || 
                stripos($name, 'title') !== false ||
                stripos($name, 'property') !== false) {
                $input .= "{$name}: {$content}\n";
            }
        }
        $input .= "\n";
    }
    
    if (!empty($htmlData['headers'])) {
        $input .= "=== HEADINGS ===\n";
        foreach (array_slice($htmlData['headers'], 0, 5) as $h) {
            $input .= "{$h}\n";
        }
        $input .= "\n";
    }
    
    if (!empty($htmlData['price_contexts'])) {
        $input .= "=== PRICE CONTEXTS ===\n";
        foreach ($htmlData['price_contexts'] as $ctx) {
            $input .= "{$ctx}\n";
        }
        $input .= "\n";
    }
    
    if (!empty($htmlData['area_contexts'])) {
        $input .= "=== AREA CONTEXTS ===\n";
        foreach ($htmlData['area_contexts'] as $ctx) {
            $input .= "{$ctx}\n";
        }
        $input .= "\n";
    }
    
    if (!empty($htmlData['room_contexts'])) {
        $input .= "=== ROOM CONTEXTS ===\n";
        foreach ($htmlData['room_contexts'] as $ctx) {
            $input .= "{$ctx}\n";
        }
        $input .= "\n";
    }
    
    if (!empty($htmlData['bathroom_contexts'])) {
        $input .= "=== BATHROOM CONTEXTS ===\n";
        foreach ($htmlData['bathroom_contexts'] as $ctx) {
            $input .= "{$ctx}\n";
        }
        $input .= "\n";
    }
    
    if (!empty($htmlData['agent_contexts'])) {
        $input .= "=== AGENT/AGENCY INFO ===\n";
        foreach ($htmlData['agent_contexts'] as $ctx) {
            $input .= "{$ctx}\n";
        }
        $input .= "\n";
    }
    
    if (!empty($htmlData['phone_contexts'])) {
        $input .= "=== PHONE NUMBERS ===\n";
        foreach ($htmlData['phone_contexts'] as $ctx) {
            $input .= "{$ctx}\n";
        }
        $input .= "\n";
    }
    
    if (!empty($htmlData['email_contexts'])) {
        $input .= "=== EMAIL ADDRESSES ===\n";
        foreach ($htmlData['email_contexts'] as $ctx) {
            $input .= "{$ctx}\n";
        }
        $input .= "\n";
    }
    
    if ($textRaw && strlen($textRaw) > 100) {
        $input .= "=== CLEAN TEXT (first 2000 chars) ===\n";
        $input .= substr($textRaw, 0, 2000) . "\n\n";
    }

    $prompt = <<<PROMPT
Extract real estate property data from the provided sources.

CRITICAL RULES:
1. Use ONLY values explicitly stated in the sources
2. DO NOT invent, guess, or calculate any values
3. If a field is not found, return null
4. Return ONLY valid JSON, no markdown, no explanations

FIELD EXTRACTION GUIDELINES:

**price** (integer only, main listing price):
- Look for: "precio", "price", amount with €/EUR
- Extract ONLY the main sale/rent price
- Ignore: price per m², monthly fees, community fees
- Example: "655.000 €" → 655000
- Example: "Precio: 450000 EUR" → 450000

**sqm** (integer, built/constructed area only):
- Look for: "m² construidos", "built area", "superficie construida", "living space"
- DO NOT use plot/land/parcel area
- Example: "120 m² construidos" → 120
- Example: "Built: 95 sqm" → 95

**plot_sqm** (integer, land/plot area only):
- Look for: "parcela", "plot", "terreno", "land area", "m² de parcela"
- Example: "Parcela: 500 m²" → 500

**rooms** (integer, bedrooms only):
- Look for: "dormitorios", "bedrooms", "habitaciones", "dorm.", "hab."
- Count ONLY bedrooms (not living rooms, bathrooms, etc)
- Example: "3 dormitorios" → 3
- Example: "4 bed villa" → 4

**bathrooms** (integer):
- Look for: "baños", "bathrooms", "bath", "aseo", "cuarto de baño"
- Count ALL bathrooms (full baths + half baths)
- Example: "2 baños" → 2
- Example: "3 bathrooms" → 3

**property_type** (string):
- Common types: villa, apartment, house, penthouse, townhouse, finca, duplex, studio
- Example: "Villa independiente" → "villa"
- Example: "Apartamento" → "apartment"

**listing_type** (string: "sale" or "rent" only):
- "sale" for: venta, comprar, for sale, en venta, a la venta
- "rent" for: alquiler, rental, for rent, to let, en alquiler

**address** (string, location of property):
- Look for: street address, city, zone, area, neighborhood
- Prefer full address over just city
- Example: "Calle Mayor 123, Alicante"
- Example: "Playa San Juan, Alicante"

**reference_id** (string, listing reference):
- Look for: "ref", "referencia", "código", "reference", "property id", "ref."
- Usually alphanumeric code
- Example: "Ref: ABC-12345"
- Example: "Código: 2024-001"

**agent_name** (string):
- Agency name or agent name
- Look for: agency/agent sections, contact info headers
- Example: "Costa Blanca Properties"
- Example: "María García - RE/MAX"

**agent_phone** (string):
- Contact phone number
- Keep original format with spaces/dashes
- Example: "+34 965 123 456"
- Example: "965-123-456"

**agent_email** (string):
- Contact email address
- Example: "info@agency.com"

OUTPUT FORMAT (exact structure required):
{
  "title": null,
  "price": null,
  "sqm": null,
  "rooms": null,
  "bathrooms": null,
  "plot_sqm": null,
  "property_type": null,
  "listing_type": null,
  "address": null,
  "reference_id": null,
  "agent_name": null,
  "agent_phone": null,
  "agent_email": null
}

DATA TO EXTRACT FROM:
{$input}

Return the extracted JSON now:
PROMPT;

    if ($debug) {
        echo "\n=== STAGE 1 PROMPT (first 1500 chars) ===\n";
        echo substr($prompt, 0, 1500) . "...\n";
        echo "=== END PROMPT ===\n\n";
    }

    $payload = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false,
        'format' => 'json',
        'options' => [
            'temperature' => 0.0,
            'num_predict' => 512,
            'num_ctx' => 4096
        ]
    ];

    $ch = curl_init($ollamaUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = 'cURL error: ' . curl_error($ch);
        return null;
    }

    $env = json_decode($response, true);
    if (!is_array($env) || !isset($env['response'])) {
        $error = 'Invalid Ollama response';
        return null;
    }

    $rawText = (string)$env['response'];
    
    if ($debug) {
        echo "=== STAGE 1 RESPONSE ===\n{$rawText}\n=== END ===\n\n";
    }
    
    return parse_json_response($rawText, $error);
}

/**
 * Stage 2: Extract full description separately
 */
function extract_description(
    string $ollamaUrl,
    string $model,
    ?array $jsonld,
    array $htmlData,
    ?string $textRaw,
    bool $debug,
    ?string &$error = null
): ?string {
    $error = null;
    
    $input = "";
    
    if ($jsonld && isset($jsonld['description'])) {
        $input .= "JSON-LD description: {$jsonld['description']}\n\n";
    }
    
    if (!empty($htmlData['meta']['description']) || !empty($htmlData['meta']['og:description'])) {
        $metaDesc = $htmlData['meta']['description'] ?? $htmlData['meta']['og:description'];
        $input .= "Meta description: {$metaDesc}\n\n";
    }
    
    if (!empty($htmlData['main_content'])) {
        $input .= "Main content:\n{$htmlData['main_content']}\n";
    } elseif ($textRaw) {
        $input .= "Page text:\n" . substr($textRaw, 0, 3000) . "\n";
    }

    $prompt = <<<PROMPT
Extract the complete property description from this real estate listing.

RULES:
1. Return the full property description as written (do not shorten or summarize)
2. Combine all description text found in the sources
3. Remove navigation text, form labels, and UI elements
4. Keep the description in its original language
5. If no description is found, return: null

Return ONLY the description text (or the word null), nothing else:

{$input}
PROMPT;

    if ($debug) {
        echo "\n=== STAGE 2 PROMPT (first 1000 chars) ===\n";
        echo substr($prompt, 0, 1000) . "...\n";
        echo "=== END PROMPT ===\n\n";
    }

    $payload = [
        'model' => $model,
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'temperature' => 0.0,
            'num_predict' => 2048,
            'num_ctx' => 8192
        ]
    ];

    $ch = curl_init($ollamaUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = 'cURL error: ' . curl_error($ch);
        return null;
    }

    $env = json_decode($response, true);
    if (!is_array($env) || !isset($env['response'])) {
        $error = 'Invalid Ollama response';
        return null;
    }

    $desc = trim((string)$env['response']);
    
    if ($debug) {
        echo "=== STAGE 2 RESPONSE (first 500 chars) ===\n";
        echo substr($desc, 0, 500) . "...\n=== END ===\n\n";
    }
    
    if (strtolower($desc) === 'null' || strlen($desc) < 10) {
        return null;
    }
    
    return $desc;
}

/**
 * Parse JSON response from model
 */
function parse_json_response(string $responseText, ?string &$error = null): ?array
{
    $txt = trim($responseText);
    
    // Remove markdown
    $txt = preg_replace('/^```json\s*/i', '', $txt) ?? $txt;
    $txt = preg_replace('/^```\s*/', '', $txt) ?? $txt;
    $txt = preg_replace('/\s*```$/', '', $txt) ?? $txt;
    
    // Try direct decode
    $parsed = json_decode($txt, true);
    if (is_array($parsed)) {
        return $parsed;
    }
    
    // Try to find JSON object
    $start = strpos($txt, '{');
    $end = strrpos($txt, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $slice = substr($txt, $start, $end - $start + 1);
        $parsed = json_decode($slice, true);
        if (is_array($parsed)) {
            return $parsed;
        }
    }
    
    $error = 'Could not parse JSON from model response';
    return null;
}

// ============================================================================
// MAIN SCRIPT
// ============================================================================

echo "FirstListing AI Parser v2.0\n";
echo "Model: {$MODEL}\n";
echo "Mode: " . ($TWO_STAGE ? "2-stage (recommended)" : "single-stage") . "\n";
echo "----------------------------------------\n\n";

$pdo->exec('USE test2firstlisting');

$q = "SELECT rp.id, rp.url, rp.html_raw, rp.text_raw, rp.jsonld_raw
      FROM raw_pages rp
      LEFT JOIN ai_listings ai ON ai.id = rp.id";

$params = [];
if ($RAW_ID !== null) {
    $q .= " WHERE rp.id = :raw_id";
    $params[':raw_id'] = $RAW_ID;
} elseif (!$FORCE) {
    $q .= " WHERE ai.id IS NULL OR rp.fetched_at > ai.updated_at";
}

$q .= " ORDER BY rp.fetched_at DESC LIMIT :lim";

$st = $pdo->prepare($q);
foreach ($params as $k => $v) {
    $st->bindValue($k, $v, PDO::PARAM_INT);
}
$st->bindValue(':lim', $LIMIT, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "No new or updated rows to process.\n";
    exit(0);
}

echo "Processing " . count($rows) . " listing(s)...\n\n";

foreach ($rows as $idx => $row) {
    $id = (int)$row['id'];
    $listingUrl = (string)$row['url'];
    $html = (string)($row['html_raw'] ?? '');
    $textRaw = $row['text_raw'] ?? null;
    $jsonldRaw = $row['jsonld_raw'] ?? null;
    
    echo "[" . ($idx + 1) . "/" . count($rows) . "] Processing ID={$id}\n";
    echo "  URL: {$listingUrl}\n";
    
    // Extract JSON-LD
    $jsonld = extract_jsonld($jsonldRaw);
    if ($jsonld && $DEBUG) {
        echo "  ✓ Found JSON-LD data\n";
    }
    
    // Pre-process HTML
    $htmlData = extract_relevant_data($html);
    
    // Stage 1: Extract structured fields
    $err = null;
    $fields = extract_structured_fields($OLLAMA_URL, $MODEL, $jsonld, $htmlData, $textRaw, $DEBUG, $err);
    
    if (!is_array($fields)) {
        echo "  ✗ Stage 1 failed: {$err}\n\n";
        continue;
    }
    
    // Stage 2: Extract description (if 2-stage mode)
    $description = null;
    if ($TWO_STAGE) {
        $err2 = null;
        $description = extract_description($OLLAMA_URL, $MODEL, $jsonld, $htmlData, $textRaw, $DEBUG, $err2);
        if ($description === null && $err2) {
            echo "  ⚠ Stage 2 warning: {$err2}\n";
        }
    }
    
    // Normalize fields
    $title = txt_or_none(pick_first($fields, ['title']));
    $desc = $TWO_STAGE ? ($description ?? 'None') : txt_or_none(pick_first($fields, ['description']));
    $price = int_or_null(pick_first($fields, ['price']));
    $sqm = int_or_null(pick_first($fields, ['sqm']));
    $rooms = int_or_null(pick_first($fields, ['rooms', 'bedrooms']));
    $bathrooms = int_or_null(pick_first($fields, ['bathrooms']));
    $plotSqm = int_or_null(pick_first($fields, ['plot_sqm']));
    $propertyType = txt_or_none(pick_first($fields, ['property_type']));
    $listingType = txt_or_none(pick_first($fields, ['listing_type']));
    $address = txt_or_none(pick_first($fields, ['address']));
    $referenceId = txt_or_none(pick_first($fields, ['reference_id']));
    $agentName = txt_or_none(pick_first($fields, ['agent_name']));
    $agentPhone = txt_or_none(pick_first($fields, ['agent_phone']));
    $agentEmail = txt_or_none(pick_first($fields, ['agent_email']));
    
    // Display extracted data
    echo "  Extracted:\n";
    $extracted = [
        'Title' => $title !== 'None' ? '✓' : '✗',
        'Price' => $price !== null ? "✓ ({$price})" : '✗',
        'SQM' => $sqm !== null ? "✓ ({$sqm})" : '✗',
        'Rooms' => $rooms !== null ? "✓ ({$rooms})" : '✗',
        'Baths' => $bathrooms !== null ? "✓ ({$bathrooms})" : '✗',
        'Plot' => $plotSqm !== null ? "✓ ({$plotSqm})" : '✗',
        'Type' => $propertyType !== 'None' ? "✓ ({$propertyType})" : '✗',
        'Listing' => $listingType !== 'None' ? "✓ ({$listingType})" : '✗',
        'Address' => $address !== 'None' ? '✓' : '✗',
        'Ref' => $referenceId !== 'None' ? "✓ ({$referenceId})" : '✗',
        'Agent' => $agentName !== 'None' ? '✓' : '✗',
        'Description' => $desc !== 'None' ? '✓ (' . strlen($desc) . ' chars)' : '✗',
    ];
    
    foreach ($extracted as $field => $status) {
        echo "    {$field}: {$status}\n";
    }
    
    // Clean up duplicates
    $del = $pdo->prepare('DELETE FROM ai_listings WHERE raw_page_id = :rid_raw AND id <> :rid_id');
    $del->execute([':rid_raw' => $id, ':rid_id' => $id]);
    
    // Upsert to database
    $upsert = "INSERT INTO ai_listings
        (id, raw_page_id, title, description, price, sqm, rooms, bathrooms, plot_sqm,
         property_type, listing_type, address, reference_id, agent_name, agent_phone, agent_email,
         created_at, updated_at)
        VALUES
        (:id, :raw_page_id, :title, :description, :price, :sqm, :rooms, :bathrooms, :plot_sqm,
         :property_type, :listing_type, :address, :reference_id, :agent_name, :agent_phone, :agent_email,
         NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            title = VALUES(title),
            description = VALUES(description),
            price = VALUES(price),
            sqm = VALUES(sqm),
            rooms = VALUES(rooms),
            bathrooms = VALUES(bathrooms),
            plot_sqm = VALUES(plot_sqm),
            property_type = VALUES(property_type),
            listing_type = VALUES(listing_type),
            address = VALUES(address),
            reference_id = VALUES(reference_id),
            agent_name = VALUES(agent_name),
            agent_phone = VALUES(agent_phone),
            agent_email = VALUES(agent_email),
            updated_at = NOW()";
    
    try {
        $ins = $pdo->prepare($upsert);
        $ins->execute([
            ':id' => $id,
            ':raw_page_id' => $id,
            ':title' => $title,
            ':description' => $desc,
            ':price' => $price,
            ':sqm' => $sqm,
            ':rooms' => $rooms,
            ':bathrooms' => $bathrooms,
            ':plot_sqm' => $plotSqm,
            ':property_type' => $propertyType,
            ':listing_type' => $listingType,
            ':address' => $address,
            ':reference_id' => $referenceId,
            ':agent_name' => $agentName,
            ':agent_phone' => $agentPhone,
            ':agent_email' => $agentEmail,
        ]);
        echo "  ✓ Saved to database\n\n";
    } catch (Throwable $e) {
        echo "  ✗ Database error: " . $e->getMessage() . "\n\n";
    }
}

echo "Done! Processed " . count($rows) . " listing(s).\n";
