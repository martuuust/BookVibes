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
    
    // Primary: Groq (Llama 3.3)
    private string $groqUrl = 'https://api.groq.com/openai/v1/chat/completions';
    private string $groqModel = 'llama-3.3-70b-versatile';

    // Secondary: Gemini (1.5 Flash)
    private string $geminiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

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
                    // Refine coordinates
                    $result['markers'] = $this->refineLocations($result['markers']);

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
                    // Refine coordinates
                    $result['markers'] = $this->refineLocations($result['markers']);

                    Logger::ai('Gemini', 'success', 'gemini-1.5-flash', [
                        'book' => $title,
                        'markers' => count($result['markers'] ?? []),
                        'time' => "{$elapsed}s"
                    ]);
                    return $result;
                } else {
                    Logger::ai('Gemini', 'PARSE_ERROR', 'gemini-1.5-flash', [
                        'book' => $title,
                        'error' => 'Invalid JSON structure'
                    ]);
                    ErrorLogger::logAiError(
                        provider: 'Gemini',
                        model: 'gemini-1.5-flash',
                        prompt: $prompt,
                        errorMessage: 'parseResponse returned null - invalid JSON structure',
                        httpStatus: 200
                    );
                }
            } catch (\Exception $e) {
                Logger::ai('Gemini', 'FAILED', 'gemini-1.5-flash', [
                    'book' => $title,
                    'error' => $e->getMessage()
                ]);
                ErrorLogger::logAiError(
                    provider: 'Gemini',
                    model: 'gemini-1.5-flash',
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
INSTRUCCIONES CRÍTICAS DE VERACIDAD GEOGRÁFICA:
1. **NO INVENTES NADA.** Si el lugar específico es ficticio, usa la ciudad o región real de inspiración.
2. **DIVERSIDAD GEOGRÁFICA:** Si el libro implica viajes (ej: "Nosotros en la luna", "Comer, Rezar, Amar"), **DEBES** incluir marcadores en los diferentes países/ciudades mencionados, no te limites a una sola ciudad.
3. **PRIORIDAD DE BÚSQUEDA:**
   - Si es un lugar real -> Usa ese nombre exacto.
   - Si es un lugar ficticio en una ciudad real -> Usa la CIUDAD.
   - Si todo es ficticio -> Usa el lugar de rodaje o el país de inspiración.

EJEMPLOS CORRECTOS:
- "Nosotros en la luna": París (Francia), Londres (UK), Byron Bay (Australia).
- "Harry Potter": King's Cross (Londres), Alnwick Castle (UK).

Formato JSON OBLIGATORIO:
{
  "map_config": {
    "region_name": "Nombre de la región principal o 'Mundo' si hay viajes",
    "center_coordinates": { "lat": XX.XXXX, "lng": XX.XXXX },
    "zoom_level": 5  // Usa 4-5 si hay viajes internacionales, 10-12 si es una ciudad
  },
  "markers": [
    {
      "title": "Nombre del lugar (Ciudad/País si es viaje)",
      "real_world_location": "Nombre REAL buscable",
      "is_fictional": boolean,
      "coordinates": { "lat": XX.XXXX, "lng": XX.XXXX },
      "snippet": "Contexto breve (máx 120 caracteres)",
      "book_excerpt": "Cita textual o narrativa descriptiva del momento en el libro (máx 300 caracteres)",
      "chapter_context": "Contexto narrativo",
      "location_type": "event|meetup|discovery|danger",
      "importance": "high|medium"
    }
  ]
}

GENERA 5-10 MARCADORES cubriendo TODAS las ubicaciones geográficas importantes del libro. Asegúrate de incluir Ciudad y País para cada uno, y un FRAGMENTO NARRATIVO (book_excerpt) evocador.
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
            
            // Auto-calculate center from markers if missing
            $latSum = 0;
            $lngSum = 0;
            $count = 0;
            
            foreach ($data['markers'] as $m) {
                if (isset($m['coordinates']['lat']) && isset($m['coordinates']['lng']) &&
                    $m['coordinates']['lat'] != 0 && $m['coordinates']['lng'] != 0) {
                    $latSum += $m['coordinates']['lat'];
                    $lngSum += $m['coordinates']['lng'];
                    $count++;
                }
            }
            
            if ($count > 0) {
                $data['map_config']['center_coordinates'] = [
                    'lat' => $latSum / $count,
                    'lng' => $lngSum / $count
                ];
                Logger::info("Calculated map center from $count markers");
            } else {
                return null;
            }
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
                    [
                        "title" => "King's Cross Station (Andén 9¾)", 
                        "coordinates" => ["lat" => 51.5320, "lng" => -0.1240], 
                        "snippet" => "Entrada al mundo mágico, donde Harry toma el Hogwarts Express.", 
                        "book_excerpt" => "Todo lo que Harry tuvo que hacer fue caminar directamente hacia la barrera entre los andenes nueve y diez. No chocó... estaba parado al lado de un tren de vapor escarlata.",
                        "chapter_context" => "El Viaje Comienza", 
                        "location_type" => "discovery", 
                        "importance" => "high"
                    ],
                    [
                        "title" => "Alnwick Castle (Hogwarts)", 
                        "coordinates" => ["lat" => 55.4155, "lng" => -1.7061], 
                        "snippet" => "Castillo utilizado para las escenas exteriores de Hogwarts.", 
                        "book_excerpt" => "El castillo era una enorme construcción con muchas torres y torrecillas... encaramado sobre una montaña, con sus ventanas brillando bajo el cielo estrellado.",
                        "chapter_context" => "Llegada a Hogwarts", 
                        "location_type" => "event", 
                        "importance" => "high"
                    ],
                    [
                        "title" => "Leadenhall Market (Callejón Diagon)", 
                        "coordinates" => ["lat" => 51.5129, "lng" => -0.0838], 
                        "snippet" => "Mercado victoriano que inspiró el Callejón Diagon.", 
                        "book_excerpt" => "Harry deseó tener ocho ojos más... Había tiendas que vendían túnicas, telescopios y extraños instrumentos de plata que Harry nunca había visto antes.",
                        "chapter_context" => "Compras mágicas", 
                        "location_type" => "discovery", 
                        "importance" => "medium"
                    ],
                    [
                        "title" => "Glenfinnan Viaduct", 
                        "coordinates" => ["lat" => 56.8760, "lng" => -5.4319], 
                        "snippet" => "El famoso puente por donde pasa el Hogwarts Express.", 
                        "book_excerpt" => "El tren aceleró hacia el norte... Vieron montañas y bosques, y el cielo se volvió de un púrpura profundo a medida que se ponía el sol.",
                        "chapter_context" => "Viaje a Hogwarts", 
                        "location_type" => "event", 
                        "importance" => "medium"
                    ]
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
                    [
                        "title" => "Great Neck (West Egg)", 
                        "coordinates" => ["lat" => 40.8007, "lng" => -73.7285], 
                        "snippet" => "La mansión de Gatsby donde organiza sus legendarias fiestas.", 
                        "book_excerpt" => "En sus jardines azules, hombres y mujeres iban y venían como polillas entre los susurros y el champán y las estrellas.",
                        "chapter_context" => "Fiestas de Gatsby", 
                        "location_type" => "event", 
                        "importance" => "high"
                    ],
                    [
                        "title" => "Manhasset (East Egg)", 
                        "coordinates" => ["lat" => 40.7976, "lng" => -73.6996], 
                        "snippet" => "Hogar de Daisy y Tom Buchanan, la vieja aristocracia.", 
                        "book_excerpt" => "Al otro lado de la bahía, los palacios blancos de moda de East Egg brillaban a lo largo del agua.",
                        "chapter_context" => "Casa de los Buchanan", 
                        "location_type" => "meetup", 
                        "importance" => "high"
                    ],
                    [
                        "title" => "Plaza Hotel NYC", 
                        "coordinates" => ["lat" => 40.7644, "lng" => -73.9745], 
                        "snippet" => "Escenario de la confrontación entre Gatsby y Tom.", 
                        "book_excerpt" => "La habitación era grande y sofocante... Tom rompió el tenso silencio: '¿Qué clase de lío es este que intentas causar en mi casa?'",
                        "chapter_context" => "Clímax", 
                        "location_type" => "danger", 
                        "importance" => "high"
                    ],
                    [
                        "title" => "Flushing (Valle de las Cenizas)", 
                        "coordinates" => ["lat" => 40.7654, "lng" => -73.8174], 
                        "snippet" => "Zona industrial donde viven los Wilson, símbolo de decadencia.", 
                        "book_excerpt" => "Este es el valle de las cenizas: una granja fantástica donde las cenizas crecen como trigo en crestas y colinas y jardines grotescos.",
                        "chapter_context" => "Tragedia", 
                        "location_type" => "danger", 
                        "importance" => "medium"
                    ]
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
                    [
                        "title" => "Chatsworth House (Pemberley)", 
                        "coordinates" => ["lat" => 53.2270, "lng" => -1.6115], 
                        "snippet" => "La majestuosa mansión de Mr. Darcy que impresiona a Elizabeth.", 
                        "book_excerpt" => "Elizabeth estaba encantada. Nunca había visto un lugar tan favorecido por la naturaleza, ni donde la belleza natural hubiera sido tan poco contrarrestada por un gusto torpe.",
                        "chapter_context" => "Visita a Pemberley", 
                        "location_type" => "discovery", 
                        "importance" => "high"
                    ],
                    [
                        "title" => "Lyme Park", 
                        "coordinates" => ["lat" => 53.3370, "lng" => -2.0550], 
                        "snippet" => "Otra locación usada como Pemberley en adaptaciones.", 
                        "book_excerpt" => "Era un edificio grande y hermoso, situado en un terreno elevado... y frente a él, un arroyo de cierta importancia se hinchaba.",
                        "chapter_context" => "Escenas exteriores", 
                        "location_type" => "event", 
                        "importance" => "medium"
                    ],
                    [
                        "title" => "Bath", 
                        "coordinates" => ["lat" => 51.3811, "lng" => -2.3590], 
                        "snippet" => "Ciudad elegante de la época Regencia.", 
                        "book_excerpt" => "Llegaron a Bath. Catherine estaba toda ojos ansiosos... Estaba impaciente por ver todo.",
                        "chapter_context" => "Sociedad", 
                        "location_type" => "meetup", 
                        "importance" => "medium"
                    ]
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
                    [
                        "title" => "Tommen College (Inspiración)", 
                        "real_world_location" => "University College Cork",
                        "is_fictional" => true,
                        "coordinates" => ["lat" => 51.8930, "lng" => -8.4920], 
                        "snippet" => "El prestigioso colegio privado donde estudian Johnny y Shannon.", 
                        "book_excerpt" => "Las puertas de hierro forjado de Tommen College se alzaban ante mí, intimidantes y grandiosas, prometiendo un mundo al que no pertenecía.",
                        "chapter_context" => "Inicio", 
                        "location_type" => "event", 
                        "importance" => "high"
                    ],
                    [
                        "title" => "Estadio de Rugby Musgrave Park", 
                        "real_world_location" => "Musgrave Park, Cork",
                        "is_fictional" => false,
                        "coordinates" => ["lat" => 51.8847, "lng" => -8.4985], 
                        "snippet" => "Estadio donde Johnny brilla como estrella del rugby irlandés.", 
                        "book_excerpt" => "El rugido de la multitud era ensordecedor. Sentí el césped bajo mis botas y el peso de la camiseta número 13 sobre mis hombros.",
                        "chapter_context" => "Partidos de Rugby", 
                        "location_type" => "event", 
                        "importance" => "high"
                    ],
                    [
                        "title" => "Cork City Centre", 
                        "coordinates" => ["lat" => 51.8969, "lng" => -8.4863], 
                        "snippet" => "Centro de la ciudad donde transcurren escenas cotidianas.", 
                        "book_excerpt" => "Caminamos por St. Patrick's Street bajo la lluvia típica de Cork, las luces de las tiendas reflejándose en el pavimento mojado.",
                        "chapter_context" => "Vida diaria", 
                        "location_type" => "meetup", 
                        "importance" => "medium"
                    ],
                    [
                        "title" => "Casa de los Kavanagh", 
                        "coordinates" => ["lat" => 51.9150, "lng" => -8.4500], 
                        "snippet" => "El hogar de la adinerada familia Kavanagh en las afueras.", 
                        "book_excerpt" => "La casa era inmensa, moderna y fría. Todo lo contrario a lo que yo llamaba hogar, pero llena de secretos que Johnny intentaba ocultar.",
                        "chapter_context" => "Familia de Johnny", 
                        "location_type" => "meetup", 
                        "importance" => "high"
                    ],
                    [
                        "title" => "Ballylaggin (Zona residencial)", 
                        "coordinates" => ["lat" => 51.8750, "lng" => -8.5100], 
                        "snippet" => "El barrio donde vive Shannon, con un pasado difícil.", 
                        "book_excerpt" => "Regresar a casa siempre sentía como contener la respiración. Las calles familiares de Ballylaggin guardaban demasiados recuerdos dolorosos.",
                        "chapter_context" => "Hogar de Shannon", 
                        "location_type" => "danger", 
                        "importance" => "high"
                    ],
                    [
                        "title" => "Dublin - Aviva Stadium", 
                        "coordinates" => ["lat" => 53.3352, "lng" => -6.2286], 
                        "snippet" => "El estadio nacional donde Johnny sueña con jugar profesionalmente.", 
                        "book_excerpt" => "Miré hacia las gradas vacías del Aviva, imaginándolas llenas, coreando mi nombre. Era más que un juego; era mi boleto de salida.",
                        "chapter_context" => "Sueños de rugby", 
                        "location_type" => "discovery", 
                        "importance" => "medium"
                    ]
                ]
            ];
        }

        // Alas de Sangre / Fourth Wing (Rebecca Yarros) - Basgiath / Navarre
        if (strpos($titleLower, 'alas de sangre') !== false || strpos($titleLower, 'fourth wing') !== false || strpos($titleLower, 'yarros') !== false) {
            return [
                "map_config" => [
                    "region_name" => "Navarre (Inspiración: Snowdonia, Gales)",
                    "center_coordinates" => ["lat" => 53.0685, "lng" => -4.0762],
                    "zoom_level" => 9
                ],
                "markers" => [
                    [
                        "title" => "Basgiath War College", 
                        "real_world_location" => "Snowdonia National Park", 
                        "is_fictional" => true,
                        "coordinates" => ["lat" => 53.0685, "lng" => -4.0762], 
                        "snippet" => "La brutal academia de jinetes de dragones construida en la montaña.", 
                        "book_excerpt" => "Un jinete de dragón sin su dragón está muerto. Bienvenidos al Cuadrante de Jinetes. Bienvenidos a Basgiath.",
                        "chapter_context" => "Llegada al Colegio", 
                        "location_type" => "danger", 
                        "importance" => "high"
                    ],
                    [
                        "title" => "Valle de Lucine (Vuelo)", 
                        "real_world_location" => "Mach Loop, Wales", 
                        "is_fictional" => true,
                        "coordinates" => ["lat" => 52.7066, "lng" => -3.8561], 
                        "snippet" => "Valle traicionero donde se realizan maniobras de vuelo de alta velocidad.", 
                        "book_excerpt" => "Tairn se lanzó en picado hacia el valle, el viento rugiendo en mis oídos mientras la gravedad intentaba arrancarme de la silla.",
                        "chapter_context" => "Entrenamiento de Vuelo", 
                        "location_type" => "event", 
                        "importance" => "high"
                    ],
                    [
                        "title" => "La Torre del Homenaje (Interrogatorios)", 
                        "real_world_location" => "Dolbadarn Castle", 
                        "is_fictional" => true,
                        "coordinates" => ["lat" => 53.1164, "lng" => -4.1186], 
                        "snippet" => "Lugar oscuro dentro de Basgiath donde ocurren eventos secretos.", 
                        "book_excerpt" => "Las piedras de la torre parecían absorber la luz. Nadie quería ser convocado allí después del toque de queda.",
                        "chapter_context" => "Secretos", 
                        "location_type" => "danger", 
                        "importance" => "medium"
                    ],
                    [
                        "title" => "Aretia (Ciudad Rebelde)", 
                        "real_world_location" => "Conwy", 
                        "is_fictional" => true,
                        "coordinates" => ["lat" => 53.2829, "lng" => -3.8295], 
                        "snippet" => "Ciudad fortificada clave para la resistencia.", 
                        "book_excerpt" => "Las murallas de Aretia se alzaban desafiantes contra el cielo, un bastión de esperanza que creíamos perdido.",
                        "chapter_context" => "Revelaciones", 
                        "location_type" => "meetup", 
                        "importance" => "high"
                    ]
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
                    "book_excerpt" => "El escenario principal de la historia, donde los destinos de los personajes se entrelazan en esta narrativa.",
                    "chapter_context" => "Historia completa",
                    "location_type" => "event",
                    "importance" => "high"
                ]
            ]
        ];
    }
    /**
     * Refine marker locations by verifying with Nominatim Search API
     * CASCADE MODE: Try specific place -> city -> country -> AI coordinates
     */
    private function refineLocations(array $markers): array
    {
        $refinedMarkers = [];
        
        foreach ($markers as $marker) {
            $realCoords = null;
            $usedSource = 'ai_generated';
            $successQuery = '';

            // 1. Construct search queries in order of specificity
            $queries = [];
            
            // Query A: Full provided real_world_location
            if (!empty($marker['real_world_location'])) {
                $queries[] = $marker['real_world_location'];
            }
            
            // Query B: Constructed from structured data (Place, City, Country)
            if (!empty($marker['place_name']) && !empty($marker['city_or_region'])) {
                $queries[] = $marker['place_name'] . ', ' . $marker['city_or_region'];
            }
            
            // Query C: City + Country (High confidence fallback)
            if (!empty($marker['city_or_region'])) {
                $cityQuery = $marker['city_or_region'];
                if (!empty($marker['country'])) {
                    $cityQuery .= ', ' . $marker['country'];
                }
                $queries[] = $cityQuery;
            }

            // Query D: Just Title (cleaned) if nothing else
            $cleanTitle = preg_replace('/\s*\(.*?\)\s*/', '', $marker['title']);
            if (strlen($cleanTitle) > 3) {
                $queries[] = $cleanTitle;
            }

            // Deduplicate queries
            $queries = array_unique($queries);

            // 2. Try each query with Nominatim
            foreach ($queries as $query) {
                if (empty($query)) continue;
                
                $realCoords = $this->verifyLocationWithNominatim($query);
                usleep(1100000); // Respect rate limit (1 req/sec)

                if ($realCoords) {
                    $usedSource = 'nominatim';
                    $successQuery = $query;
                    break; // Found it! Stop searching.
                }
            }

            // 3. Assign coordinates
            if ($realCoords) {
                $marker['coordinates'] = $realCoords;
                $marker['source'] = 'nominatim';
                $marker['geocoded_query'] = $successQuery;
                $refinedMarkers[] = $marker;
            } elseif (
                isset($marker['coordinates']['lat']) && 
                isset($marker['coordinates']['lng']) &&
                $marker['coordinates']['lat'] != 0 &&
                $marker['coordinates']['lng'] != 0
            ) {
                // Fallback to AI coordinates
                $marker['source'] = 'ai_generated';
                $refinedMarkers[] = $marker;
                Logger::info("Usando coordenadas IA para marcador", ['title' => $marker['title']]);
            } else {
                Logger::warning("Marcador descartado: Sin coordenadas válidas", ['title' => $marker['title']]);
            }
        }
        
        // If ALL markers were filtered out, try global fallback using synopsis or title
        if (empty($refinedMarkers) && !empty($markers)) {
            $baseMarker = $markers[0];
            // Try structured data first for fallback
            $fallbackQuery = $baseMarker['city_or_region'] ?? $baseMarker['real_world_location'] ?? null;
            
            if (!$fallbackQuery) {
                 // Clean title as last resort
                 $fallbackQuery = preg_replace('/\s*\(.*?\)\s*/', '', $baseMarker['title']);
            }
            
            // Try to find coordinates for the general region/city
            $fallbackCoords = $this->verifyLocationWithNominatim($fallbackQuery);
            
            if ($fallbackCoords) {
                // Create a general "Atmosphere" marker
                $refinedMarkers[] = [
                    'title' => "Escenario Principal (Inspiración)",
                    'real_world_location' => $fallbackQuery,
                    'is_fictional' => true,
                    'coordinates' => $fallbackCoords,
                    'snippet' => "Ubicación general aproximada para la ambientación del libro.",
                    'chapter_context' => "Contexto General",
                    'location_type' => "discovery",
                    'importance' => "high",
                    'source' => 'fallback_region'
                ];
                
                Logger::info("All specific markers discarded. Created fallback inspiration marker.", ['location' => $fallbackQuery]);
            }
        }
        
        return $refinedMarkers;
    }

    /**
     * Query Nominatim API for real coordinates
     */
    private function verifyLocationWithNominatim(string $query): ?array
    {
        $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($query) . "&format=json&limit=1&addressdetails=1";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BookVibesApp/1.0 (Educational Project; contact@example.com)');
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                return [
                    'lat' => (float)$data[0]['lat'],
                    'lng' => (float)$data[0]['lon']
                ];
            }
        }
        
        return null;
    }
}
