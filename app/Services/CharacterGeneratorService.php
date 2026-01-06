<?php

namespace App\Services;

use App\Models\Character;

class CharacterGeneratorService
{
    private $apiKey;
    private $apiUrl = 'https://api.openai.com/v1/chat/completions';
    private $imgApiUrl = 'https://api.openai.com/v1/images/generations';

    public function __construct()
    {
        // Try to get API key from environment or config
        $this->apiKey = getenv('OPENAI_API_KEY') ?: ($_ENV['OPENAI_API_KEY'] ?? null);
    }

    public function hasApiKey(): bool
    {
        return !empty($this->apiKey);
    }



    public function generateForBook($bookId, $title, $author, $synopsis)
    {
        // 1. Clear old characters
        Character::deleteAllByBookId($bookId);

        // 2. Gather External Context (Wikipedia, OpenLibrary) to help AI or Fallback
        $externalContext = $this->gatherExternalContext($title, $author);
        $fullContext = "Sinopsis: $synopsis\n\nInformación Adicional:\n$externalContext";

        // 3. If no API Key, use local extraction on the gathered text
        if (!$this->hasApiKey()) {
            return $this->generateFallbackCharacters($bookId, $title, $fullContext, $author);
        }

        try {
            // 4. Generate Character Profiles using AI with Rich Context
            $prompt = "Analyze the following information about the book '$title' by '$author'. 
            Extract exactly 3-5 main characters.
            
            INFORMATION:
            $fullContext
            
            Return ONLY valid JSON in this format:
            [
                {
                    \"name\": \"Name\",
                    \"role\": \"Role (Protagonist/Antagonist/Support)\",
                    \"description\": \"Visual description for image generation (physical traits, clothing, vibe).\",
                    \"traits\": [\"Trait1\", \"Trait2\"]
                }
            ]";

            $response = $this->callOpenAI($prompt);
            $content = $response['choices'][0]['message']['content'] ?? '';
            
            if (empty($content)) throw new \Exception('Respuesta vacía de IA');

            // Extract JSON
            $jsonStart = strpos($content, '[');
            $jsonEnd = strrpos($content, ']');
            if ($jsonStart !== false && $jsonEnd !== false) {
                $json = substr($content, $jsonStart, $jsonEnd - $jsonStart + 1);
                $characters = json_decode($json, true);
            } else {
                $characters = [];
            }

            if (empty($characters)) throw new \Exception('JSON inválido');

            $results = [];
            foreach ($characters as $char) {
                // Generate Image
                $imagePrompt = "Realistic digital art portrait of " . $char['name'] . " (" . $char['role'] . ") from " . $title . ". " . $char['description'] . ". Detailed face, cinematic lighting, 8k.";
                $imageUrl = $this->generateImage($imagePrompt);

                Character::create([
                    'book_id' => $bookId,
                    'name' => $char['name'],
                    'role' => $char['role'],
                    'description' => $char['description'],
                    'traits' => json_encode($char['traits'] ?? []),
                    'image_url' => $imageUrl,
                    'source' => 'AI + Web'
                ]);
                $results[] = $char;
            }

            return ['ok' => true, 'count' => count($results)];

        } catch (\Exception $e) {
            // If API fails, fallback to local extraction
            return $this->generateFallbackCharacters($bookId, $title, $fullContext, $author);
        }
    }

    private function gatherExternalContext($title, $author)
    {
        $context = "";

        // 1. Open Library
        $olData = $this->fetchOpenLibraryData($title, $author);
        if ($olData) {
            $context .= "OpenLibrary Data: " . $olData . "\n";
        }

        // 2. Wikipedia (ES then EN)
        $wikiData = $this->fetchWikipediaText($title, 'es');
        if (!$wikiData) {
            $wikiData = $this->fetchWikipediaText($title, 'en');
        }
        if ($wikiData) {
            $context .= "Wikipedia Extract: " . $wikiData . "\n";
        }

        return $context;
    }

    private function generateFallbackCharacters($bookId, $title, $text, $author = '')
    {
        // Use our improved extraction logic on the FULL text (Synopsis + Wiki)
        $extracted = $this->extractCharactersFromText($title, $text, $author);
        
        foreach ($extracted as $char) {
            Character::create([
                'book_id' => $bookId,
                'name' => $char['name'],
                'role' => $char['role'],
                'description' => $char['description'],
                'traits' => json_encode($char['traits']),
                'image_url' => $char['image_url'] ?? '',
                'source' => 'Web Scraping'
            ]);
        }

        return ['ok' => true, 'count' => count($extracted), 'source' => 'fallback'];
    }

    private function fetchOpenLibraryData($title, $author)
    {
        // Try precise search first
        $qTitle = urlencode($title);
        $qAuthor = urlencode($author);
        $url = "https://openlibrary.org/search.json?title=$qTitle&author=$qAuthor&fields=title,person&limit=1";
        $data = $this->makeRequest($url);
        
        if (!empty($data['docs']) && !empty($data['docs'][0]['person'])) {
            return "Personajes listados en OpenLibrary: " . implode(", ", array_slice($data['docs'][0]['person'], 0, 10));
        }

        // Fallback: General query
        $query = urlencode($title . ' ' . $author);
        $url = "https://openlibrary.org/search.json?q=$query&fields=title,person&limit=1";
        $data = $this->makeRequest($url);

        if (!empty($data['docs']) && !empty($data['docs'][0]['person'])) {
            return "Personajes listados en OpenLibrary: " . implode(", ", array_slice($data['docs'][0]['person'], 0, 10));
        }

        return "";
    }

    private function fetchWikipediaText($title, $lang)
    {
        $encoded = urlencode($title);
        $searchUrl = "https://$lang.wikipedia.org/w/api.php?action=query&list=search&srsearch=$encoded&format=json&utf8=1";
        $searchData = $this->makeRequest($searchUrl);
        
        if (empty($searchData['query']['search'])) return null;

        $pageId = $searchData['query']['search'][0]['pageid'];
        $contentUrl = "https://$lang.wikipedia.org/w/api.php?action=query&prop=extracts&pageids=$pageId&explaintext=1&format=json&utf8=1";
        $contentData = $this->makeRequest($contentUrl);
        
        return $contentData['query']['pages'][$pageId]['extract'] ?? null;
    }

    private function makeRequest($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BookVibes/1.0 (Educational Project)');
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true);
    }

    private function extractCharactersFromText($title, $synopsis, $author = '')
    {
        $candidates = [];
        $priorityNames = [];

        // 0. Extract Priority Names from OpenLibrary/Wiki Lists
        // Look for our specific marker "Personajes listados en OpenLibrary:"
        if (preg_match('/Personajes listados en OpenLibrary: (.*?)(?:\n|$)/', $synopsis, $matches)) {
            $list = explode(',', $matches[1]);
            foreach ($list as $name) {
                $name = trim($name);
                if (strlen($name) > 2) {
                    $priorityNames[$name] = 100; // High score
                }
            }
        }

        // 1. Pre-process: Split into sentences to identify "Start of Sentence" words
        // Simple split by . ! ? followed by space
        $sentences = preg_split('/(?<=[.!?])\s+/', $synopsis);
        
        $sentenceStarters = [];
        $midSentenceCaps = [];
        $allCaps = [];

        // Pre-calculate Title Occurrences for filtering
        $cleanTitle = trim(preg_replace('/[^\w\s]/u', ' ', $title));
        // Count loose matches of the full title (approximate)
        // actually simpler: count matches of the main title words sequence?
        // Let's just count the full title string occurrences case-insensitive
        $titleCount = preg_match_all('/' . preg_quote($cleanTitle, '/') . '/ui', $synopsis);
        if ($titleCount < 1) $titleCount = 1; // Assume at least 1 (the book itself)
        
        $titleParts = explode(' ', $cleanTitle);
        $titleParts = array_filter($titleParts, function($p) { return strlen($p) > 2; });

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;

            // Remove punctuation for cleaner word analysis
            $cleanSentence = preg_replace('/[^\w\sà-ÿ]/u', '', $sentence);
            $words = explode(' ', $cleanSentence);
            
            foreach ($words as $index => $word) {
                if (empty($word)) continue;
                
                // Check if starts with Upper Case
                if (preg_match('/^[A-ZÁÉÍÓÚÑ][a-zà-ÿ]+$/u', $word)) {
                    $allCaps[] = $word;
                    if ($index === 0) {
                        $sentenceStarters[] = $word;
                    } else {
                        $midSentenceCaps[] = $word;
                    }
                }
            }
        }

        // 2. Identify Multi-Word Names (e.g., "Sherlock Holmes", "Harry Potter")
        // Look for consecutive capitalized words in the full synopsis
        preg_match_all('/\b([A-ZÁÉÍÓÚÑ][a-zà-ÿ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-zà-ÿ]+)+)\b/u', $synopsis, $multiMatches);
        $multiNames = $multiMatches[0] ?? [];

        // 3. Count Frequencies
        $nameCounts = array_count_values($allCaps);
        $midCounts = array_count_values($midSentenceCaps);
        $multiCounts = array_count_values($multiNames);

        // 4. Filter Candidates
        $stopWords = [
            // Common English
            'The', 'A', 'An', 'In', 'On', 'At', 'To', 'For', 'Of', 'With', 'By', 'From', 'And', 'But', 'Or', 'Yet', 'So', 'It', 'This', 'That', 'He', 'She', 'They', 'We', 'You', 'I', 'My', 'Your', 'His', 'Her', 'Their', 'Our', 'Be', 'Is', 'Are', 'Was', 'Were', 'Have', 'Has', 'Had', 'Do', 'Does', 'Did', 'Can', 'Could', 'Will', 'Would', 'Should', 'May', 'Might', 'Must',
            // Common Spanish
            'El', 'La', 'Los', 'Las', 'Un', 'Una', 'Unos', 'Unas', 'En', 'A', 'De', 'Del', 'Al', 'Por', 'Para', 'Con', 'Sin', 'Sobre', 'Entre', 'Tras', 'Y', 'E', 'O', 'U', 'Pero', 'Aunque', 'Mas', 'Si', 'No', 'Ni', 'Que', 'Cual', 'Quien', 'Como', 'Cuando', 'Donde', 'Porque', 'Este', 'Esta', 'Ese', 'Esa', 'Aquel', 'Aquella', 'Ello', 'Ella', 'Ellos', 'Ellas', 'Nosotros', 'Vosotros', 'Usted', 'Ustedes', 'Mi', 'Tu', 'Su', 'Nuestro', 'Vuestro', 'Sus', 'Mis', 'Tus', 'Muy', 'Mas', 'Menos',
            // Verbs / Aux
            'Era', 'Fue', 'Habia', 'Hay', 'Son', 'Eran', 'Fueron', 'Ser', 'Estar', 'Tener', 'Hacer', 'Ir', 'Ver', 'Dar', 'Decir', 'Poder', 'Querer',
            // Editorial / Meta
            'Editorial', 'Edicion', 'Libro', 'Pagina', 'Capitulo', 'Titulo', 'Autor', 'Escritor', 'Novela', 'Saga', 'Serie', 'Trilogia', 'Volumen', 'Coleccion', 'Tapa', 'Blanda', 'Dura', 'Bolsillo', 'Ebook', 'Kindle', 'Amazon', 'Wikipedia', 'Texto', 'Sinopsis', 'Resumen', 'Argumento', 'Ficcion', 'Historia', 'Copyright', 'Rights', 'Reserved', 'All', 'Inc', 'Ltd', 'Press', 'Publishing', 'Books', 'Edition', 'Page', 'Chapter', 'Title', 'Author', 'Novel', 'Series', 'Volume', 'Collection', 'Cover', 'Plot', 'Summary', 'Synopsis', 'Fiction',
            // Places / Institutions / Titles (Crucial for filtering "Instituto", "Universidad", etc.)
            'Instituto', 'Academia', 'Escuela', 'Colegio', 'Universidad', 'Facultad', 'Campus', 'Hospital', 'Clinica', 'Centro', 'Departamento', 'Oficina', 'Agencia', 'Compañia', 'Empresa', 'Corporacion', 'Grupo', 'Banda', 'Equipo', 'Club', 'Asociacion', 'Fundacion', 'Organizacion', 'Sociedad', 'Comunidad', 'Familia', 'Casa', 'Hogar', 'Residencia', 'Edificio', 'Torre', 'Calle', 'Avenida', 'Plaza', 'Parque', 'Jardin', 'Bosque', 'Rio', 'Lago', 'Mar', 'Océano', 'Montaña', 'Valle', 'Ciudad', 'Pueblo', 'Villa', 'Aldea', 'Pais', 'Nacion', 'Estado', 'Provincia', 'Region', 'Zona', 'Area', 'Lugar', 'Sitio', 'Mundo', 'Universo', 'Planeta',
            // Abstract / Descriptive
            'Informacion', 'Adicional', 'Dato', 'Datos', 'Detalle', 'Detalles', 'Nota', 'Notas', 'Palabra', 'Palabras', 'Frase', 'Frases', 'Oracion', 'Parrafo', 'Ejemplo', 'Caso', 'Tipo', 'Clase', 'Forma', 'Manera', 'Modo', 'Estilo', 'Genero', 'Tema', 'Asunto', 'Cosa', 'Objeto', 'Hecho', 'Realidad', 'Verdad', 'Mentira', 'Bien', 'Mal', 'Vida', 'Muerte', 'Amor', 'Odio', 'Guerra', 'Paz', 'Tiempo', 'Destino', 'Suerte', 'Dia', 'Noche', 'Mañana', 'Tarde', 'Hoy', 'Ayer', 'Siempre', 'Nunca', 'Todo', 'Nada', 'Algo', 'Alguien', 'Nadie',
            // Titles / Roles
            'Señor', 'Señora', 'Don', 'Doña', 'Miss', 'Mr', 'Mrs', 'Dr', 'Prof', 'General', 'Capitan', 'Sargento', 'Teniente', 'Soldado', 'Policia', 'Agente', 'Detective', 'Inspector', 'Juez', 'Abogado', 'Presidente', 'Ministro', 'Rey', 'Reina', 'Principe', 'Princesa'
        ];

        // Process Single Words
        foreach ($nameCounts as $word => $count) {
            // Rule A: Must not be a stop word
            if (in_array($word, $stopWords)) continue;
            
            // Rule B: Length > 2
            if (strlen($word) <= 2) continue;

            // Rule C: Filter Author Name parts
            if ($author && stripos($author, $word) !== false) continue;
            
            // Rule C2: Filter Title parts if they don't appear independently
            foreach ($titleParts as $part) {
                if (strcasecmp($part, $word) === 0) {
                    // It matches a title part.
                    // If this word appears roughly the same amount as the Title, it's likely ONLY in the title.
                    // If it appears more (e.g. 1.5x), it's likely used as a name independently.
                    if ($count <= $titleCount * 1.5) { 
                        continue 2; // Skip this word
                    }
                }
            }

            // Rule D: If it appears mainly as a sentence starter, discard it
            $isMostlyStarter = false;
            if (isset($midCounts[$word])) {
                // Appears mid-sentence at least once
            } else {
                // NEVER appears mid-sentence. 
                if ($count < 3) $isMostlyStarter = true;
            }

            if (!$isMostlyStarter) {
                $candidates[$word] = $count;
            }
        }

        // Process Multi Words (Give them higher weight/priority)
        foreach ($multiCounts as $name => $count) {
             // Basic stop word check for parts
             $parts = explode(' ', $name);
             if (in_array($parts[0], $stopWords)) continue;
             
             // Filter Author
              if ($author && stripos($name, $author) !== false) continue;

             // Filter Title (Exact match or very close)
             if (mb_strtoupper($name, 'UTF-8') === mb_strtoupper($title, 'UTF-8')) continue;
             
             // Filter if Title starts with Name (e.g. "Harry Potter" in "Harry Potter and...") -> Allow it.
             // Filter if Name contains Title? Unlikely.
             // Filter if Name is a common phrase?
 
              $candidates[$name] = ($candidates[$name] ?? 0) + ($count * 5); // Boost score
        }

        // Merge Priority Names (OpenLibrary)
        foreach ($priorityNames as $name => $score) {
            $candidates[$name] = ($candidates[$name] ?? 0) + $score;
        }

        arsort($candidates);
        $topNames = array_slice(array_keys($candidates), 0, 6); // Take top 6

        // Remove subsets (e.g. remove "Harry" if "Harry Potter" is selected)
        $finalNames = [];
        foreach ($topNames as $name) {
            $isSubset = false;
            foreach ($finalNames as $existing) {
                if (strpos($existing, $name) !== false) {
                    $isSubset = true;
                    break;
                }
            }
            if (!$isSubset) {
                // Also check if this new name contains an existing one (replace the shorter one)
                foreach ($finalNames as $key => $existing) {
                    if (strpos($name, $existing) !== false) {
                        unset($finalNames[$key]); // Remove shorter version
                    }
                }
                $finalNames[] = $name;
            }
        }
        
        // Re-index
        $finalNames = array_values($finalNames);
        $finalNames = array_slice($finalNames, 0, 5); // Limit to 5

        // Fallback if empty
        if (empty($finalNames)) {
             return [
                [
                    'name' => 'Protagonista',
                    'role' => 'Principal',
                    'description' => 'El personaje central de la historia.',
                    'traits' => [],
                    'image_url' => ''
                ]
            ];
        }

        $results = [];
        $roles = ['Protagonista', 'Co-Protagonista', 'Antagonista', 'Personaje Clave', 'Secundario'];
        $i = 0;

        foreach ($finalNames as $name) {
            $role = $roles[$i] ?? 'Personaje';
            
            // Extract description from text
            $description = $this->extractContextDescription($name, $synopsis);
            if (empty($description)) {
                $description = "Un personaje clave en la historia de $title.";
            }

            // Extract traits
            $traits = $this->extractTraitsFromDescription($description);

            // Generate character image using AI (Pollinations/DiceBear)
            // Ensures consistent style and no real actors/photos
            $imageUrl = $this->generateFallbackImage($name, $role, $description, $traits, $title);

            $results[] = [
                'name' => $name,
                'role' => $role,
                'description' => $description,
                'traits' => $traits, 
                'image_url' => $imageUrl
            ];
            $i++;
        }

        return $results;
    }

    private function extractContextDescription($name, $text)
    {
        // Split into sentences
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $bestSentence = "";
        $maxScore = -1;

        // Split name into parts to allow partial matching (e.g. "Harry" in "Harry Potter")
        $nameParts = explode(' ', $name);
        $mainName = $nameParts[0];

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (empty($sentence)) continue;

            // Check if name appears (full or main part)
            if (stripos($sentence, $name) !== false || (strlen($mainName) > 3 && stripos($sentence, $mainName) !== false)) {
                // Score the sentence
                $score = 0;
                $len = strlen($sentence);
                
                // Huge boost if sentence STARTS with the name (Subject)
                if (stripos($sentence, $name) === 0) $score += 20;
                elseif (stripos($sentence, $mainName) === 0) $score += 15;

                // Prefer sentences that define the character
                if (stripos($sentence, ' es ') !== false) $score += 5;
                if (stripos($sentence, ' era ') !== false) $score += 5;
                if (stripos($sentence, ' fue ') !== false) $score += 5;
                if (stripos($sentence, ' hijo de ') !== false) $score += 3;
                if (stripos($sentence, ' amigo de ') !== false) $score += 3;
                
                // Penalize very short or very long
                if ($len < 20) $score -= 10;
                if ($len > 300) $score -= 5;
                
                if ($score > $maxScore) {
                    $maxScore = $score;
                    $bestSentence = $sentence;
                }
            }
        }
        
        return trim($bestSentence);
    }

    private function extractTraitsFromDescription($text)
    {
        $traits = [];
        $commonTraits = [
            'valiente', 'astuto', 'inteligente', 'fuerte', 'joven', 'viejo', 'anciano', 
            'hermoso', 'bello', 'feo', 'rico', 'pobre', 'misterioso', 'oscuro', 'luz',
            'mago', 'guerrero', 'estudiante', 'profesor', 'detective', 'asesino', 'amigo',
            'enemigo', 'líder', 'jefe', 'rey', 'reina', 'príncipe', 'princesa',
            'ambicioso', 'leal', 'traidor', 'inocente', 'culpable', 'triste', 'feliz',
            'aventurero', 'curioso', 'poderoso', 'débil', 'humilde', 'orgulloso'
        ];
        
        foreach ($commonTraits as $trait) {
            if (stripos($text, $trait) !== false) {
                $traits[] = ucfirst($trait);
            }
        }
        
        if (empty($traits)) {
            $traits[] = 'Importante';
        }
        
        return array_slice($traits, 0, 3);
    }

    private function generateMockCharacters($bookId)
    {
        // Fallback for demo without API Key
        $mocks = [
            [
                'name' => 'Personaje Principal',
                'role' => 'Protagonista',
                'description' => 'Un personaje misterioso con rasgos definidos, cabello oscuro y mirada intensa. Su presencia domina la escena.',
                'traits' => ['Valiente', 'Misterioso', 'Líder'],
                'image_url' => '' // Empty to trigger placeholder in view
            ],
            [
                'name' => 'Antagonista Sombra',
                'role' => 'Antagonista',
                'description' => 'Una figura enigmática que opera desde las sombras. Rasgos afilados y una sonrisa calculadora.',
                'traits' => ['Astuto', 'Manipulador', 'Cruel'],
                'image_url' => ''
            ],
            [
                'name' => 'Aliado Leal',
                'role' => 'Secundario',
                'description' => 'Siempre dispuesto a ayudar, con una expresión amable y ojos vivaces.',
                'traits' => ['Leal', 'Optimista', 'Hábil'],
                'image_url' => ''
            ]
        ];

        foreach ($mocks as $mock) {
            Character::create([
                'book_id' => $bookId,
                'name' => $mock['name'],
                'role' => $mock['role'],
                'description' => $mock['description'],
                'traits' => json_encode($mock['traits']),
                'image_url' => $mock['image_url'],
                'source' => 'Mock'
            ]);
        }

        return ['ok' => true, 'count' => count($mocks), 'mock' => true];
    }


    private function callOpenAI($prompt)
    {
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant that outputs strict JSON.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for local SSL error
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($result, true);

        if ($httpCode !== 200) {
            $errorMsg = $json['error']['message'] ?? 'Error desconocido de OpenAI';
            throw new \Exception("OpenAI Error ($httpCode): $errorMsg");
        }

        return $json;
    }

    private function generateImage($prompt)
    {
        $data = [
            'model' => 'dall-e-2', // or dall-e-3
            'prompt' => $prompt,
            'n' => 1,
            'size' => '512x512' // 1024x1024 for DALL-E 3
        ];

        $ch = curl_init($this->imgApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix for local SSL error
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return ''; // Fail gracefully
        }
        curl_close($ch);

        $json = json_decode($result, true);
        return $json['data'][0]['url'] ?? '';
    }

    private function fetchCharacterImageFromWeb($name, $title) {
        // Simple Wikipedia/MediaWiki API search
        // Try to find a page for the character or the book
        // Note: This often returns actors if there's a movie.
        
        $queries = [
            $name . " " . $title . " character",
            $name . " character",
            $name
        ];

        foreach ($queries as $query) {
            $url = "https://en.wikipedia.org/w/api.php?action=query&generator=search&gsrsearch=" . urlencode($query) . "&gsrlimit=1&prop=pageimages&pithumbsize=500&format=json";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'BookVibes/1.0 (marty@example.com)');
            // Follow redirects
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($ch);
            curl_close($ch);

            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['query']['pages'])) {
                    $page = reset($data['query']['pages']);
                    if (isset($page['thumbnail']['source'])) {
                        return $page['thumbnail']['source'];
                    }
                }
            }
        }
        return null;
    }

    private function generateFallbackImage($name, $role, $description, $traits, $title)
    {
        // Option 1: Pollinations.ai (Artistic/Consistent) - Requires API Key
        // Get key from https://enter.pollinations.ai/
        $apiKey = getenv('POLLINATIONS_API_KEY') ?: ($_ENV['POLLINATIONS_API_KEY'] ?? null);

        if (!empty($apiKey)) {
            $traitsStr = !empty($traits) ? implode(", ", $traits) : "Distinctive features";
            
            // New Prompt Strategy:
            // 1. "Digital concept art" style for consistency and non-photorealism (no actors).
            // 2. Explicit "Character design" instruction.
            // 3. Setting/Atmosphere context.
            // Update: User requested "Humanoid" and "Look like people". 
            // We shift slightly towards Photorealism but keep it artistic to avoid "Uncanny Valley" or bad fakes.
            $prompt = "Photorealistic portrait of $name ($role) from the book $title. " .
                      "Style: Cinematic lighting, highly detailed, realistic texture, 8k resolution. " .
                      "Appearance: $description. Traits: $traitsStr. " .
                      "Context: Authentic to the era and setting of $title. " .
                      "Negative: Cartoon, drawing, sketch, text, watermark, blur, distorted face.";
            
            $encodedPrompt = rawurlencode($prompt);
            $seed = rand(1, 9999);
            // Use 'flux' model for best prompt adherence
            // Increased height for portrait
            return "https://gen.pollinations.ai/image/$encodedPrompt?width=512&height=768&model=flux&nologo=true&seed=$seed&key=$apiKey";
        }

        // Option 2: Wikipedia (Realistic/Humanoid) - Fallback if no Key
        // The user explicitly requested "Humanoid" and "Look like people".
        // Wikipedia often provides images of actors or realistic illustrations.
        $wikiImage = $this->fetchCharacterImageFromWeb($name, $title);
        if ($wikiImage) {
            return $wikiImage;
        }

        // Option 3: DiceBear (Avatar) - Last Resort
        // 'lorelei' is good, but 'notionists' might be more 'artistic'?
        // Stick to 'lorelei' as it's safe.
        $seed = rawurlencode($name . " " . $title);
        return "https://api.dicebear.com/9.x/lorelei/svg?seed=$seed&backgroundColor=b6e3f4,c0aede,d1d4f9";
    }
}