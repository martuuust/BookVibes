<?php

namespace App\Services;

use App\Core\ErrorLogger;
use App\Core\Logger;

/**
 * BookMapService - Generates literary geography data using AI + Wikipedia enrichment
 * Creates interactive map markers for book locations
 */
class BookMapService
{
    private string $groqKey;
    private string $geminiKey;
    
    // Primary: Groq (Llama 3)
    private string $groqUrl = 'https://api.groq.com/openai/v1/chat/completions';
    private string $groqModel = 'llama3-70b-8192';

    // Secondary: Gemini (Flash)
    private string $geminiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    // Store last prompt for logging
    private string $lastPrompt = '';

    public function __construct()
    {
        $this->groqKey = getenv('GROQ_API_KEY') ?: '';
        $this->geminiKey = getenv('GEMINI_API_KEY') ?: '';
    }

    /**
     * Generate map data for a book
     * @param string $title Book title
     * @param string $author Book author
     * @param string $synopsis Optional book synopsis for better context
     */
    public function generateMapData(string $title, string $author, string $synopsis = ''): ?array
    {
        Logger::info("Generando mapa para libro", ['title' => $title, 'author' => $author]);
        
        // 1. Fetch context from external sources
        $externalContext = $this->fetchExternalContext($title, $author);
        
        // 2. Combine with book synopsis if available
        $fullContext = '';
        if (!empty($synopsis)) {
            $fullContext .= "SINOPSIS DEL LIBRO:\n" . substr(strip_tags($synopsis), 0, 1000) . "\n\n";
        }
        $fullContext .= $externalContext;
        
        // 3. Build enriched prompt
        $prompt = $this->buildPrompt($title, $author, $fullContext);
        $this->lastPrompt = $prompt;
        
        // 4. Primary Attempt: GROQ
        if (!empty($this->groqKey)) {
            try {
                $startTime = microtime(true);
                $response = $this->callGroq($prompt);
                $result = $this->parseResponse($response, 'groq');
                $elapsed = round(microtime(true) - $startTime, 2);
                
                if ($result) {
                    Logger::ai('Groq', 'success', $this->groqModel, [
                        'book' => $title,
                        'markers' => count($result['markers'] ?? []),
                        'time' => "{$elapsed}s"
                    ]);
                    return $result;
                } else {
                    Logger::ai('Groq', 'PARSE_ERROR', $this->groqModel, [
                        'book' => $title,
                        'error' => 'Invalid JSON structure'
                    ]);
                    ErrorLogger::logAiError(
                        provider: 'Groq',
                        model: $this->groqModel,
                        prompt: $prompt,
                        errorMessage: 'parseResponse returned null - invalid JSON structure',
                        httpStatus: 200
                    );
                }
            } catch (\Exception $e) {
                Logger::ai('Groq', 'FAILED', $this->groqModel, [
                    'book' => $title,
                    'error' => $e->getMessage()
                ]);
                ErrorLogger::logAiError(
                    provider: 'Groq',
                    model: $this->groqModel,
                    prompt: $prompt,
                    errorMessage: $e->getMessage(),
                    exception: $e
                );
            }
        } else {
            Logger::warning("GROQ_API_KEY no configurada");
        }

        // 5. Secondary Attempt: GEMINI
        if (!empty($this->geminiKey)) {
            try {
                $startTime = microtime(true);
                $response = $this->callGemini($prompt);
                $result = $this->parseResponse($response, 'gemini');
                $elapsed = round(microtime(true) - $startTime, 2);
                
                if ($result) {
                    Logger::ai('Gemini', 'success', 'gemini-2.0-flash', [
                        'book' => $title,
                        'markers' => count($result['markers'] ?? []),
                        'time' => "{$elapsed}s"
                    ]);
                    return $result;
                } else {
                    Logger::ai('Gemini', 'PARSE_ERROR', 'gemini-2.0-flash', [
                        'book' => $title,
                        'error' => 'Invalid JSON structure'
                    ]);
                    ErrorLogger::logAiError(
                        provider: 'Gemini',
                        model: 'gemini-2.0-flash',
                        prompt: $prompt,
                        errorMessage: 'parseResponse returned null - invalid JSON structure',
                        httpStatus: 200
                    );
                }
            } catch (\Exception $e) {
                Logger::ai('Gemini', 'FAILED', 'gemini-2.0-flash', [
                    'book' => $title,
                    'error' => $e->getMessage()
                ]);
                ErrorLogger::logAiError(
                    provider: 'Gemini',
                    model: 'gemini-2.0-flash',
                    prompt: $prompt,
                    errorMessage: $e->getMessage(),
                    exception: $e
                );
            }
        } else {
            Logger::warning("GEMINI_API_KEY no configurada");
        }

        // 6. UNIVERSAL FALLBACK
        Logger::warning("Usando fallback hardcoded para mapa", ['book' => $title]);
        return $this->getHardcodedFallback($title, $author);
    }

    /**
     * Fetch setting/location info from multiple external sources
     */
    private function fetchExternalContext(string $title, string $author): string
    {
        $context = '';
        
        // 1. WIKIPEDIA EN - Book article
        $bookQuery = urlencode("$title novel");
        $wikiUrl = "https://en.wikipedia.org/api/rest_v1/page/summary/" . $bookQuery;
        
        $bookInfo = $this->fetchUrl($wikiUrl);
        if ($bookInfo && isset($bookInfo['extract'])) {
            $context .= "Wikipedia EN: " . $bookInfo['extract'] . "\n\n";
        }
        
        // 2. WIKIPEDIA ES - For Spanish books
        $esBookQuery = urlencode("$title libro");
        $esWikiUrl = "https://es.wikipedia.org/api/rest_v1/page/summary/" . $esBookQuery;
        $esInfo = $this->fetchUrl($esWikiUrl);
        if ($esInfo && isset($esInfo['extract'])) {
            $context .= "Wikipedia ES: " . $esInfo['extract'] . "\n\n";
        }
        
        // 3. OPEN LIBRARY - Good for descriptions and subjects
        $olSearchUrl = "https://openlibrary.org/search.json?title=" . urlencode($title) . "&limit=1";
        $olData = $this->fetchUrl($olSearchUrl);
        if ($olData && !empty($olData['docs'])) {
            $doc = $olData['docs'][0];
            
            // Get subjects (often contain location info)
            if (!empty($doc['subject'])) {
                $subjects = array_slice($doc['subject'], 0, 10);
                $context .= "Open Library Subjects: " . implode(', ', $subjects) . "\n";
            }
            
            // Get place info if available
            if (!empty($doc['place'])) {
                $places = array_slice($doc['place'], 0, 5);
                $context .= "Open Library Places: " . implode(', ', $places) . "\n";
            }
            
            // First sentence often mentions setting
            if (!empty($doc['first_sentence'])) {
                $firstSentence = is_array($doc['first_sentence']) ? $doc['first_sentence'][0] : $doc['first_sentence'];
                $context .= "First Sentence: " . $firstSentence . "\n";
            }
        }
        
        // 4. GOOGLE BOOKS - Often has good descriptions
        $gbUrl = "https://www.googleapis.com/books/v1/volumes?q=" . urlencode("intitle:$title+inauthor:$author") . "&maxResults=1";
        $gbData = $this->fetchUrl($gbUrl);
        if ($gbData && !empty($gbData['items'])) {
            $item = $gbData['items'][0]['volumeInfo'] ?? [];
            
            // Description often mentions setting
            if (!empty($item['description'])) {
                // Take first 500 chars to avoid too long context
                $desc = substr($item['description'], 0, 500);
                $context .= "\nGoogle Books Description: " . strip_tags($desc) . "\n";
            }
            
            // Categories can hint at location
            if (!empty($item['categories'])) {
                $context .= "Categories: " . implode(', ', $item['categories']) . "\n";
            }
        }
        
        // 5. WIKIPEDIA Search - for plot locations specifically
        $searchUrl = "https://en.wikipedia.org/w/api.php?action=query&list=search&srsearch=" 
            . urlencode("\"$title\" setting location city country") 
            . "&format=json&utf8=1&srlimit=3";
        
        $searchResults = $this->fetchUrl($searchUrl);
        if ($searchResults && isset($searchResults['query']['search'])) {
            foreach ($searchResults['query']['search'] as $result) {
                $snippet = strip_tags($result['snippet'] ?? '');
                if (strlen($snippet) > 50) {
                    $context .= "Wiki Search: " . $snippet . "\n";
                }
            }
        }
        
        return $context;
    }

    /**
     * Fetch URL content (JSON)
     */
    private function fetchUrl(string $url): ?array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BookVibes/1.0 (Literary Map Generator)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300 && $result) {
            return json_decode($result, true);
        }
        return null;
    }

    /**
     * Build the geography prompt with Wikipedia context
     */
    private function buildPrompt(string $title, string $author, string $wikiContext): string
    {
        $contextBlock = '';
        if (!empty($wikiContext)) {
            $contextBlock = <<<CONTEXT

CONTEXTO DE WIKIPEDIA Y OTRAS FUENTES (usa esta información para determinar ubicaciones reales):
---
$wikiContext
---

CONTEXT;
        }

        return <<<PROMPT
Eres un experto en geografía literaria y cartografía digital. Analiza el libro "$title" de $author y genera datos para un mapa interactivo.
$contextBlock
INSTRUCCIONES CRÍTICAS:
1. USA COORDENADAS REALES Y PRECISAS de lugares que existen en el mundo real.
2. Si el libro ocurre en un lugar ficticio (ej: Hogwarts, Tierra Media), usa las coordenadas del lugar de rodaje o inspiración real.
3. Busca en tu conocimiento los lugares ESPECÍFICOS donde ocurren escenas importantes del libro.
4. NO inventes coordenadas genéricas. Cada punto debe corresponder a un lugar real verificable.

EJEMPLOS DE PRECISIÓN ESPERADA:
- "El Gran Gatsby" → West Egg = Great Neck, NY (40.8007, -73.7285)
- "Harry Potter" → Hogwarts = Alnwick Castle, UK (55.4155, -1.7061)
- "Orgullo y Prejuicio" → Pemberley = Chatsworth House (53.2270, -1.6115)

Formato JSON OBLIGATORIO:
{
  "map_config": {
    "region_name": "Nombre de la región (ej: 'Nueva York, años 1920')",
    "center_coordinates": { "lat": XX.XXXX, "lng": XX.XXXX },
    "zoom_level": 12
  },
  "markers": [
    {
      "title": "Nombre del lugar específico",
      "coordinates": { "lat": XX.XXXX, "lng": XX.XXXX },
      "snippet": "Qué ocurre aquí en el libro (máx 120 caracteres)",
      "chapter_context": "Momento de la historia (ej: 'Capítulo 3', 'Clímax')",
      "location_type": "event|meetup|discovery|danger",
      "importance": "high|medium"
    }
  ]
}

GENERA 4-6 MARCADORES para los lugares más importantes de "$title".
PROMPT;
    }

    /**
     * Call Groq API
     */
    private function callGroq(string $prompt): array
    {
        $data = [
            "model" => $this->groqModel,
            "messages" => [
                ["role" => "system", "content" => "You are a literary geography expert. You MUST return valid JSON only. Use REAL coordinates from actual places."],
                ["role" => "user", "content" => $prompt]
            ],
            "response_format" => ["type" => "json_object"], 
            "temperature" => 0.3 // Lower for more factual responses
        ];

        return $this->executeCurl($this->groqUrl, $data, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->groqKey
        ]);
    }

    /**
     * Call Gemini API
     */
    private function callGemini(string $prompt): array
    {
        $url = $this->geminiUrl . "?key=" . $this->geminiKey;
        $data = [
            "contents" => [
                ["parts" => [["text" => $prompt]]]
            ],
            "generationConfig" => [
                "response_mime_type" => "application/json",
                "temperature" => 0.3
            ]
        ];

        return $this->executeCurl($url, $data, ['Content-Type: application/json']);
    }

    /**
     * Execute cURL request with retry logic for rate limiting
     */
    private function executeCurl(string $url, array $data, array $headers, int $retries = 3): array
    {
        $lastError = '';
        
        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Increased timeout
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $lastError = curl_error($ch);
                curl_close($ch);
                error_log("BookMapService cURL error (attempt $attempt): $lastError");
                sleep(1);
                continue;
            }
            curl_close($ch);

            $json = json_decode($result, true);
            
            // Handle rate limiting (429)
            if ($httpCode === 429) {
                $waitTime = $attempt * 10; // 10s, 20s, 30s
                error_log("BookMapService rate limited (attempt $attempt), waiting {$waitTime}s...");
                sleep($waitTime);
                continue;
            }
            
            // Handle other errors
            if ($httpCode >= 400 || !$json) {
                $msg = $json['error']['message'] ?? "HTTP $httpCode: " . substr($result, 0, 200);
                $lastError = $msg;
                error_log("BookMapService API error (attempt $attempt): $msg");
                
                // Don't retry on auth errors
                if ($httpCode === 401 || $httpCode === 403) {
                    throw new \Exception("API Auth Error: $msg");
                }
                
                sleep(2);
                continue;
            }
            
            // Success
            return $json;
        }
        
        throw new \Exception("API Error after $retries attempts: $lastError");
    }

    /**
     * Parse API response
     */
    private function parseResponse(array $response, string $apiType = 'groq'): ?array
    {
        $content = '';
        if ($apiType === 'groq') {
            $content = $response['choices'][0]['message']['content'] ?? '';
        } elseif ($apiType === 'gemini') {
            $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        }
        
        // Clean markdown
        $content = preg_replace('/^```json\s*/', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        $content = trim($content);
        
        $data = json_decode($content, true);
        
        if (empty($data) || empty($data['map_config']) || empty($data['markers'])) {
            return null;
        }

        // Validate coordinates exist
        if (!isset($data['map_config']['center_coordinates']['lat']) ||
            !isset($data['map_config']['center_coordinates']['lng'])) {
            return null;
        }

        // Ensure all markers have required fields
        foreach ($data['markers'] as &$marker) {
            $marker['location_type'] = $marker['location_type'] ?? 'event';
            $marker['importance'] = $marker['importance'] ?? 'medium';
            $marker['chapter_context'] = $marker['chapter_context'] ?? 'Escena clave';
        }

        return $data;
    }

    /**
     * Hardcoded fallback for common books
     */
    private function getHardcodedFallback(string $title, string $author): array
    {
        $titleLower = strtolower($title);
        
        // Harry Potter
        if (strpos($titleLower, 'harry potter') !== false || strpos($titleLower, 'hogwarts') !== false) {
            return [
                "map_config" => [
                    "region_name" => "Reino Unido (Locaciones de Harry Potter)",
                    "center_coordinates" => ["lat" => 51.5320, "lng" => -0.1770],
                    "zoom_level" => 6
                ],
                "markers" => [
                    ["title" => "King's Cross Station (Andén 9¾)", "coordinates" => ["lat" => 51.5320, "lng" => -0.1240], "snippet" => "Entrada al mundo mágico, donde Harry toma el Hogwarts Express.", "chapter_context" => "Inicio del viaje", "location_type" => "discovery", "importance" => "high"],
                    ["title" => "Alnwick Castle (Hogwarts)", "coordinates" => ["lat" => 55.4155, "lng" => -1.7061], "snippet" => "Castillo utilizado para las escenas exteriores de Hogwarts.", "chapter_context" => "Escuela de Magia", "location_type" => "event", "importance" => "high"],
                    ["title" => "Leadenhall Market (Callejón Diagon)", "coordinates" => ["lat" => 51.5129, "lng" => -0.0838], "snippet" => "Mercado victoriano que inspiró el Callejón Diagon.", "chapter_context" => "Compras mágicas", "location_type" => "discovery", "importance" => "medium"],
                    ["title" => "Glenfinnan Viaduct", "coordinates" => ["lat" => 56.8760, "lng" => -5.4319], "snippet" => "El famoso puente por donde pasa el Hogwarts Express.", "chapter_context" => "Viaje a Hogwarts", "location_type" => "event", "importance" => "medium"]
                ]
            ];
        }
        
        // El Gran Gatsby
        if (strpos($titleLower, 'gatsby') !== false) {
            return [
                "map_config" => [
                    "region_name" => "Long Island, Nueva York (Años 1920)",
                    "center_coordinates" => ["lat" => 40.7831, "lng" => -73.7654],
                    "zoom_level" => 11
                ],
                "markers" => [
                    ["title" => "Great Neck (West Egg)", "coordinates" => ["lat" => 40.8007, "lng" => -73.7285], "snippet" => "La mansión de Gatsby donde organiza sus legendarias fiestas.", "chapter_context" => "Fiestas de Gatsby", "location_type" => "event", "importance" => "high"],
                    ["title" => "Manhasset (East Egg)", "coordinates" => ["lat" => 40.7976, "lng" => -73.6996], "snippet" => "Hogar de Daisy y Tom Buchanan, la vieja aristocracia.", "chapter_context" => "Casa de los Buchanan", "location_type" => "meetup", "importance" => "high"],
                    ["title" => "Plaza Hotel NYC", "coordinates" => ["lat" => 40.7644, "lng" => -73.9745], "snippet" => "Escenario de la confrontación entre Gatsby y Tom.", "chapter_context" => "Clímax", "location_type" => "danger", "importance" => "high"],
                    ["title" => "Flushing (Valle de las Cenizas)", "coordinates" => ["lat" => 40.7654, "lng" => -73.8174], "snippet" => "Zona industrial donde viven los Wilson, símbolo de decadencia.", "chapter_context" => "Tragedia", "location_type" => "danger", "importance" => "medium"]
                ]
            ];
        }
        
        // Orgullo y Prejuicio
        if (strpos($titleLower, 'orgullo') !== false || strpos($titleLower, 'pride') !== false) {
            return [
                "map_config" => [
                    "region_name" => "Inglaterra, Época Regencia",
                    "center_coordinates" => ["lat" => 52.8500, "lng" => -1.5000],
                    "zoom_level" => 7
                ],
                "markers" => [
                    ["title" => "Chatsworth House (Pemberley)", "coordinates" => ["lat" => 53.2270, "lng" => -1.6115], "snippet" => "La majestuosa mansión de Mr. Darcy que impresiona a Elizabeth.", "chapter_context" => "Visita a Pemberley", "location_type" => "discovery", "importance" => "high"],
                    ["title" => "Lyme Park", "coordinates" => ["lat" => 53.3370, "lng" => -2.0550], "snippet" => "Otra locación usada como Pemberley en adaptaciones.", "chapter_context" => "Escenas exteriores", "location_type" => "event", "importance" => "medium"],
                    ["title" => "Bath", "coordinates" => ["lat" => 51.3811, "lng" => -2.3590], "snippet" => "Ciudad elegante de la época Regencia.", "chapter_context" => "Sociedad", "location_type" => "meetup", "importance" => "medium"]
                ]
            ];
        }
        
        // Binding 13 / Boys of Tommen (Chloe Walsh) - Irlanda
        if (strpos($titleLower, 'binding') !== false || strpos($titleLower, 'tommen') !== false || strpos($titleLower, 'kavanagh') !== false) {
            return [
                "map_config" => [
                    "region_name" => "Cork, Irlanda",
                    "center_coordinates" => ["lat" => 51.8985, "lng" => -8.4756],
                    "zoom_level" => 11
                ],
                "markers" => [
                    ["title" => "Tommen College (Inspiración)", "coordinates" => ["lat" => 51.8930, "lng" => -8.4920], "snippet" => "El prestigioso colegio privado donde estudian Johnny y Shannon.", "chapter_context" => "Inicio", "location_type" => "event", "importance" => "high"],
                    ["title" => "Estadio de Rugby Musgrave Park", "coordinates" => ["lat" => 51.8847, "lng" => -8.4985], "snippet" => "Estadio donde Johnny brilla como estrella del rugby irlandés.", "chapter_context" => "Partidos de Rugby", "location_type" => "event", "importance" => "high"],
                    ["title" => "Cork City Centre", "coordinates" => ["lat" => 51.8969, "lng" => -8.4863], "snippet" => "Centro de la ciudad donde transcurren escenas cotidianas.", "chapter_context" => "Vida diaria", "location_type" => "meetup", "importance" => "medium"],
                    ["title" => "Casa de los Kavanagh", "coordinates" => ["lat" => 51.9150, "lng" => -8.4500], "snippet" => "El hogar de la adinerada familia Kavanagh en las afueras.", "chapter_context" => "Familia de Johnny", "location_type" => "meetup", "importance" => "high"],
                    ["title" => "Ballylaggin (Zona residencial)", "coordinates" => ["lat" => 51.8750, "lng" => -8.5100], "snippet" => "El barrio donde vive Shannon, con un pasado difícil.", "chapter_context" => "Hogar de Shannon", "location_type" => "danger", "importance" => "high"],
                    ["title" => "Dublin - Aviva Stadium", "coordinates" => ["lat" => 53.3352, "lng" => -6.2286], "snippet" => "El estadio nacional donde Johnny sueña con jugar profesionalmente.", "chapter_context" => "Sueños de rugby", "location_type" => "discovery", "importance" => "medium"]
                ]
            ];
        }

        // Generic fallback - use London as default
        return [
            "map_config" => [
                "region_name" => "Ubicación Literaria",
                "center_coordinates" => ["lat" => 51.5074, "lng" => -0.1278],
                "zoom_level" => 10
            ],
            "markers" => [
                [
                    "title" => "Escenario Principal",
                    "coordinates" => ["lat" => 51.5074, "lng" => -0.1278],
                    "snippet" => "Ubicación central de '$title'.",
                    "chapter_context" => "Historia completa",
                    "location_type" => "event",
                    "importance" => "high"
                ]
            ]
        ];
    }
}
