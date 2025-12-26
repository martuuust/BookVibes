<?php

namespace App\Services;

/**
 * AI Song Generator Service
 * 
 * Generates unique, high-quality AI songs with vocals using Suno AI API
 * Each song is at least 2 minutes long and tailored to the book's theme and mood
 */
class AISongGeneratorService
{
    private $apiKey;
    private $apiEndpoint = 'https://api.sunoapi.org/api/v1'; // Using third-party Suno API
    
    // Mood to music style mapping for Suno AI
    private $moodToGenre = [
        'Melancolía' => ['indie folk', 'sad piano ballad', 'melancholic acoustic', 'emotional pop'],
        'Alegría' => ['upbeat pop', 'happy indie', 'cheerful acoustic', 'feel-good rock'],
        'Misterio' => ['dark ambient', 'mysterious electronic', 'suspenseful orchestral', 'noir jazz'],
        'Romance' => ['romantic ballad', 'love song', 'soft pop', 'acoustic romance'],
        'Aventura' => ['epic orchestral', 'adventure rock', 'cinematic', 'heroic anthem'],
        'Terror' => ['horror ambient', 'dark electronic', 'creepy orchestral', 'nightmare pop'],
        'Fantasía' => ['fantasy orchestral', 'magical folk', 'ethereal dream pop', 'mystical'],
        'Ciencia Ficción' => ['synthwave', 'futuristic electronic', 'sci-fi ambient', 'cyberpunk'],
        'Acción' => ['energetic rock', 'intense electronic', 'powerful orchestral', 'adrenaline'],
        'Drama' => ['emotional piano', 'dramatic orchestral', 'intense ballad', 'theatrical'],
        'Comedia' => ['quirky pop', 'playful indie', 'upbeat jazz', 'fun acoustic']
    ];

    // Voice gender selection based on book characteristics
    private $voiceGenders = ['male', 'female'];

    public function __construct()
    {
        // Load API key from environment or config
        $this->apiKey = getenv('SUNO_API_KEY') ?: '';
        
        // Alternative API endpoints (can be configured)
        $customEndpoint = getenv('SUNO_API_ENDPOINT');
        if ($customEndpoint) {
            $this->apiEndpoint = $customEndpoint;
        }
    }

    /**
     * Generate unique AI songs for a book
     * 
     * @param array $bookData Book information (title, author, synopsis, mood, genre)
     * @param int $count Number of songs to generate (default 2)
     * @return array Array of generated songs with metadata
     */
    public function generateSongs(array $bookData, int $count = 2): array
    {
        $songs = [];
        $mood = $bookData['mood'] ?? 'Misterio';
        $title = $bookData['title'] ?? 'Libro Desconocido';
        $author = $bookData['author'] ?? 'Autor Desconocido';
        $synopsis = $bookData['synopsis'] ?? '';
        $genre = $bookData['genre'] ?? '';

        // Extract meaningful keywords from synopsis
        $keywords = $this->extractKeywords($synopsis, $title);
        
        // Generate unique songs
        for ($i = 0; $i < $count; $i++) {
            $songData = $this->generateSingleSong($bookData, $keywords, $i, $count);
            if ($songData) {
                $songs[] = $songData;
            }
        }

        return $songs;
    }

    /**
     * Generate a single unique song
     */
    private function generateSingleSong(array $bookData, array $keywords, int $index, int $total): array
    {
        $mood = $bookData['mood'] ?? 'Misterio';
        $title = $bookData['title'] ?? 'Libro Desconocido';
        $genre = $bookData['genre'] ?? '';
        
        // Determine voice gender (alternate between male and female)
        $voiceGender = $this->voiceGenders[$index % 2];
        
        // Generate unique song title
        $songTitle = $this->generateUniqueTitle($title, $mood, $keywords, $index);
        
        // Generate custom lyrics based on book content
        $lyrics = $this->generateCustomLyrics($bookData, $keywords, $index);
        
        // Determine music style
        $musicStyle = $this->determineMusicStyle($mood, $genre, $index);
        
        // Generate melody description
        $melodyDescription = $this->generateDetailedMelodyDescription($mood, $musicStyle, $voiceGender, $index);
        
        // If API key is configured, attempt to generate real song
        if (!empty($this->apiKey)) {
            $generatedSong = $this->callSunoAPI($songTitle, $lyrics, $musicStyle, $voiceGender);
            if ($generatedSong) {
                return [
                    'title' => $songTitle,
                    'artist' => 'BookVibes AI',
                    'url' => $generatedSong['audio_url'] ?? '#',
                    'is_ai_generated' => 1,
                    'lyrics' => $lyrics,
                    'melody_description' => $melodyDescription,
                    'duration' => $generatedSong['duration'] ?? 120, // At least 2 minutes
                    'voice_gender' => $voiceGender,
                    'music_style' => $musicStyle,
                    'generation_id' => $generatedSong['id'] ?? null
                ];
            }
        }
        
        // Fallback: Return metadata without actual audio (for display purposes)
        return [
            'title' => $songTitle,
            'artist' => 'BookVibes AI',
            'url' => '#', // Placeholder - would be replaced with actual audio URL
            'is_ai_generated' => 1,
            'lyrics' => $lyrics,
            'melody_description' => $melodyDescription,
            'duration' => 120 + ($index * 15), // 2+ minutes, varied
            'voice_gender' => $voiceGender,
            'music_style' => $musicStyle,
            'status' => 'pending_generation' // Indicates song needs to be generated
        ];
    }

    /**
     * Call Suno AI API to generate actual song
     */
    private function callSunoAPI(string $title, string $lyrics, string $style, string $voiceGender): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $payload = [
                'title' => $title,
                'lyrics' => $lyrics,
                'style' => $style,
                'voice' => $voiceGender,
                'duration' => 'extended', // Request 2+ minute songs
                'instrumental' => false, // We want vocals
                'wait_audio' => true // Wait for generation to complete
            ];

            $ch = curl_init($this->apiEndpoint . '/generate');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey
                ],
                CURLOPT_TIMEOUT => 120 // Allow up to 2 minutes for generation
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if ($data && isset($data['audio_url'])) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail - fallback to metadata only
            error_log("Suno API Error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Generate unique song title that won't repeat
     */
    private function generateUniqueTitle(string $bookTitle, string $mood, array $keywords, int $index): string
    {
        // Create a unique seed based on book title, index AND random component for variety
        $seed = crc32($bookTitle . $index . uniqid());
        
        $titlePatterns = [
            // Pattern 1: Keyword-based
            [
                'prefixes' => ['Ecos de', 'Sombras en', 'La Balada de', 'Sueños de', 'Voces de', 'Secretos de', 'Susurros de', 'El Canto de', 'Reflejos de', 'Huellas de'],
                'base' => $keywords['protagonist'] ?? $keywords['location'] ?? explode(' ', $bookTitle)[0]
            ],
            // Pattern 2: Mood-based
            [
                'prefixes' => ['El Ritmo del', 'Memorias de', 'Canción de', 'Historia de', 'El Latido de', 'Sinfonía de', 'Armonía de', 'Resonancia de'],
                'base' => $mood
            ],
            // Pattern 3: Theme-based
            [
                'prefixes' => ['Entre', 'Más Allá de', 'Dentro de', 'A Través de', 'Bajo', 'Sobre', 'Hacia', 'Desde'],
                'base' => $keywords['theme'] ?? $keywords['location'] ?? 'lo Desconocido'
            ],
            // Pattern 4: Direct book reference
            [
                'prefixes' => ['', 'La Esencia de', 'El Alma de', 'El Espíritu de', 'La Luz de', 'La Sombra de'],
                'base' => $this->getShortBookTitle($bookTitle)
            ],
            // Pattern 5: Emotional
            [
                'prefixes' => ['Cuando', 'Si', 'Mientras', 'Aunque', 'Porque'],
                'base' => $keywords['emotion'] . ' llega a ' . ($keywords['location'] ?? 'mi corazón')
            ],
            // Pattern 6: Poetic
            [
                'prefixes' => ['La Noche de', 'El Día de', 'El Amanecer de', 'El Ocaso de', 'La Luna de', 'El Sol de'],
                'base' => $keywords['protagonist'] ?? $mood
            ]
        ];
        
        // Use seed to select pattern and prefix deterministically but uniquely per book
        $pattern = $titlePatterns[$seed % count($titlePatterns)];
        $prefixIndex = ($seed >> 8) % count($pattern['prefixes']);
        $prefix = $pattern['prefixes'][$prefixIndex];
        
        return trim($prefix . ' ' . $pattern['base']);
    }

    /**
     * Generate custom lyrics based on book content
     */
    private function generateCustomLyrics(array $bookData, array $keywords, int $variation): string
    {
        $title = $bookData['title'] ?? 'Libro';
        $mood = $bookData['mood'] ?? 'Misterio';
        $synopsis = $bookData['synopsis'] ?? '';
        
        $protagonist = $keywords['protagonist'] ?? 'el protagonista';
        $location = $keywords['location'] ?? 'este mundo';
        $theme = $keywords['theme'] ?? 'el destino';
        $goal = $keywords['goal'] ?? 'la verdad';
        $emotion = $keywords['emotion'] ?? 'esperanza';

        // Create unique lyric structure for each variation
        $structures = [
            // Structure 1: Classic verse-chorus
            ['[Intro]', '[Verso 1]', '[Coro]', '[Verso 2]', '[Coro]', '[Puente]', '[Coro]', '[Verso 3]', '[Coro Final]', '[Outro]'],
            // Structure 2: Story-driven
            ['[Verso 1]', '[Pre-Coro]', '[Coro]', '[Verso 2]', '[Pre-Coro]', '[Coro]', '[Puente]', '[Coro]', '[Outro]'],
            // Structure 3: Emotional build
            ['[Intro]', '[Verso 1]', '[Verso 2]', '[Coro]', '[Verso 3]', '[Puente]', '[Coro]', '[Coro Final]', '[Outro]']
        ];
        
        $structure = $structures[$variation % count($structures)];
        
        // Mood-specific lyric templates
        $templates = $this->getLyricTemplates($mood);
        
        $fullLyrics = "";
        foreach ($structure as $part) {
            $fullLyrics .= "$part\n";
            $lines = $this->generateLyricsForPart($part, $templates, [
                'title' => $title,
                'mood' => $mood,
                'protagonist' => $protagonist,
                'location' => $location,
                'theme' => $theme,
                'goal' => $goal,
                'emotion' => $emotion
            ]);
            $fullLyrics .= $lines . "\n\n";
        }

        return trim($fullLyrics);
    }

    /**
     * Get lyric templates based on mood
     */
    private function getLyricTemplates(string $mood): array
    {
        $baseTemplates = [
            'intro' => [
                "En el silencio de {location}",
                "Cuando todo comenzó en {location}",
                "Bajo el cielo de {mood}",
                "Una historia nace en {location}",
                "El viento susurra en {location}",
                "Donde {protagonist} encontró su camino",
                "En las profundidades de {location}",
                "Cuando {theme} despertó"
            ],
            'verse' => [
                "En las sombras de {location}, {protagonist} camina solo",
                "El eco de {theme} resuena en la noche",
                "Entre páginas de {title}, se esconde {goal}",
                "Los muros de {location} guardan secretos",
                "{protagonist} busca {goal} sin cesar",
                "El destino de {protagonist} está escrito en {theme}",
                "Cada paso en {location} cuenta una historia",
                "Las estrellas brillan sobre {location}",
                "{protagonist} sueña con {goal}",
                "El tiempo se detiene en {location}",
                "Voces antiguas hablan de {theme}",
                "{protagonist} enfrenta su {emotion}",
                "En el corazón de {location}, late {theme}",
                "Las lágrimas de {protagonist} caen como lluvia",
                "El camino hacia {goal} es largo y oscuro",
                "Recuerdos de {location} persiguen a {protagonist}",
                "La verdad sobre {theme} se revela lentamente",
                "{protagonist} no puede olvidar {location}",
                "El {emotion} guía cada decisión",
                "Bajo la luna de {location}, {protagonist} espera",
                "Los secretos de {title} emergen",
                "Nadie entiende el dolor de {protagonist}",
                "{theme} es más fuerte que el miedo",
                "En {location}, todo es posible",
                "El pasado de {protagonist} regresa",
                "Las sombras ocultan {goal}",
                "{protagonist} lucha contra {theme}",
                "El silencio en {location} es ensordecedor",
                "Cada latido recuerda a {location}",
                "La esperanza vive en {protagonist}"
            ],
            'pre_chorus' => [
                "Y el {emotion} crece dentro",
                "No hay vuelta atrás ahora",
                "El tiempo se detiene aquí",
                "Todo cambia en este momento",
                "Las puertas se abren",
                "El destino llama",
                "Nada será igual",
                "La verdad emerge",
                "El mundo gira",
                "Las cadenas se rompen"
            ],
            'chorus' => [
                "Oh {title}, tu historia vive en mí",
                "Volando hacia {goal}, sin mirar atrás",
                "{mood} es el camino, {theme} es el final",
                "Grita al viento, {protagonist}, tu leyenda no morirá",
                "Todo gira en torno a {goal}",
                "La historia de {title} renace hoy",
                "En {location}, encontramos {goal}",
                "{protagonist}, tu nombre resuena eternamente",
                "El {emotion} nos guía a través de {theme}",
                "Nunca olvidaremos {location}",
                "{title}, eres parte de nosotros",
                "Corremos hacia {goal} con {emotion}",
                "Las páginas de {title} cobran vida",
                "{protagonist} vive en cada corazón",
                "El legado de {location} perdura",
                "{theme} nos une para siempre",
                "Cantamos la canción de {title}",
                "El espíritu de {protagonist} nos inspira",
                "{location} es nuestro hogar eterno",
                "Buscamos {goal} hasta el amanecer"
            ],
            'bridge' => [
                "Y aunque el tiempo pase, la memoria permanece",
                "Un giro inesperado cambia todo",
                "No hay vuelta atrás para {protagonist}",
                "El {emotion} ilumina el camino oscuro",
                "Las estrellas caen sobre {location}",
                "El silencio habla más que mil palabras",
                "Todo lo que conocíamos desaparece",
                "Una nueva era comienza en {location}",
                "El sacrificio de {protagonist} no fue en vano",
                "Las lágrimas se convierten en fuerza",
                "El pasado y el futuro se encuentran",
                "Nada puede detener {theme}",
                "El {emotion} trasciende el tiempo",
                "Las sombras retroceden ante la luz",
                "Un último suspiro en {location}"
            ],
            'outro' => [
                "Así termina el viaje en {location}",
                "El silencio cae sobre {title}",
                "Buscando {goal} hasta el fin",
                "Y la historia continúa...",
                "El eco de {protagonist} permanece",
                "En {location}, todo descansa",
                "La leyenda de {title} vive",
                "El {emotion} nunca muere",
                "Hasta que nos volvamos a encontrar",
                "El final es solo el comienzo"
            ]
        ];

        // Add mood-specific variations
        $moodSpecific = [
            'Romance' => [
                'verse' => [
                    "Suspiros en {location}, {protagonist} espera",
                    "El corazón late fuerte por {theme}",
                    "Cada mirada cuenta una historia de {emotion}",
                    "Los labios de {protagonist} susurran {goal}",
                    "En {location}, el amor florece",
                    "Las manos de {protagonist} tiemblan",
                    "Bajo las estrellas, promesas de {theme}",
                    "El {emotion} arde como fuego"
                ],
                'chorus' => [
                    "Amor en {title}, pasión que no se apaga",
                    "Juntos buscando {goal} contra el mundo",
                    "Bajo la luna, prometemos eternidad",
                    "Tu {emotion} es mi refugio",
                    "En {location}, nuestros corazones laten como uno",
                    "El amor de {title} trasciende el tiempo"
                ]
            ],
            'Terror' => [
                'verse' => [
                    "Oscuridad en {location}, {protagonist} siente el miedo",
                    "Gritos ahogados en la niebla de {theme}",
                    "Algo acecha en las sombras de {goal}",
                    "Los ojos de {protagonist} ven horrores",
                    "En {location}, la muerte espera",
                    "Sangre fría corre por las venas",
                    "El terror de {theme} paraliza",
                    "Nadie escapa de {location}"
                ],
                'chorus' => [
                    "Pesadilla en {title}, no puedes escapar",
                    "El {mood} te consume, {theme} te atrapa",
                    "Corre, {protagonist}, antes de que sea tarde",
                    "En {location}, el horror es real",
                    "Las sombras de {title} te persiguen",
                    "El {emotion} te devora lentamente"
                ]
            ],
            'Aventura' => [
                'verse' => [
                    "El viaje comienza en {location}",
                    "{protagonist} enfrenta lo desconocido",
                    "Cada paso hacia {goal} es una prueba",
                    "Montañas y valles en {location}",
                    "El mapa lleva a {theme}",
                    "Peligros acechan en cada esquina",
                    "{protagonist} no se rinde jamás",
                    "La aventura de {title} apenas empieza"
                ],
                'chorus' => [
                    "Adelante hacia {goal}, sin rendirse jamás",
                    "La aventura de {title} apenas comienza",
                    "{protagonist} conquista {theme} con valor",
                    "En {location}, somos invencibles",
                    "El {emotion} nos impulsa hacia adelante",
                    "Nada detiene a {protagonist}"
                ]
            ],
            'Melancolía' => [
                'verse' => [
                    "Lágrimas caen en {location}",
                    "{protagonist} recuerda tiempos mejores",
                    "La tristeza de {theme} pesa",
                    "Soledad en cada rincón de {location}",
                    "El {emotion} duele profundamente",
                    "Memorias de {goal} se desvanecen",
                    "{protagonist} camina solo en la lluvia",
                    "El pasado en {title} no vuelve"
                ],
                'chorus' => [
                    "Melancolía en {title}, un dolor eterno",
                    "{protagonist}, tu tristeza es mi canción",
                    "En {location}, lloramos juntos",
                    "El {emotion} nos consume lentamente",
                    "Las sombras de {theme} nos abrazan",
                    "Nunca olvidaremos lo perdido"
                ]
            ],
            'Alegría' => [
                'verse' => [
                    "Risas resuenan en {location}",
                    "{protagonist} baila bajo el sol",
                    "La felicidad de {theme} brilla",
                    "Colores vivos en {location}",
                    "El {emotion} llena cada momento",
                    "{protagonist} celebra {goal}",
                    "La vida en {title} es hermosa",
                    "Sonrisas iluminan {location}"
                ],
                'chorus' => [
                    "Alegría en {title}, un día perfecto",
                    "{protagonist}, tu risa es contagiosa",
                    "En {location}, celebramos la vida",
                    "El {emotion} nos eleva alto",
                    "{theme} trae luz a nuestros corazones",
                    "Bailamos hacia {goal} con júbilo"
                ]
            ]
        ];

        if (isset($moodSpecific[$mood])) {
            return array_merge_recursive($baseTemplates, $moodSpecific[$mood]);
        }

        return $baseTemplates;
    }

    /**
     * Generate lyrics for a specific song part
     */
    private function generateLyricsForPart(string $part, array $templates, array $vars): string
    {
        $lines = [];
        
        // Create seed from book title and part AND random component for variety
        $seed = crc32($vars['title'] . $part . uniqid());
        
        if (strpos($part, 'Intro') !== false) {
            $introTemplates = $templates['intro'];
            $template = $introTemplates[$seed % count($introTemplates)];
            $lines[] = $this->fillTemplate($template, $vars);
        } elseif (strpos($part, 'Verso') !== false) {
            $verseTemplates = $templates['verse'];
            $count = count($verseTemplates);
            // Use different parts of seed for each line
            $lines[] = $this->fillTemplate($verseTemplates[$seed % $count], $vars);
            $lines[] = $this->fillTemplate($verseTemplates[($seed >> 4) % $count], $vars);
            $lines[] = $this->fillTemplate($verseTemplates[($seed >> 8) % $count], $vars);
        } elseif (strpos($part, 'Pre-Coro') !== false) {
            $preChorusTemplates = $templates['pre_chorus'];
            $count = count($preChorusTemplates);
            $lines[] = $this->fillTemplate($preChorusTemplates[$seed % $count], $vars);
            $lines[] = $this->fillTemplate($preChorusTemplates[($seed >> 4) % $count], $vars);
        } elseif (strpos($part, 'Coro') !== false) {
            $chorusTemplates = $templates['chorus'];
            $count = count($chorusTemplates);
            $lines[] = $this->fillTemplate($chorusTemplates[$seed % $count], $vars);
            $lines[] = $this->fillTemplate($chorusTemplates[($seed >> 4) % $count], $vars);
            if (strpos($part, 'Final') !== false) {
                $lines[] = $this->fillTemplate($chorusTemplates[($seed >> 8) % $count], $vars);
            }
        } elseif (strpos($part, 'Puente') !== false) {
            $bridgeTemplates = $templates['bridge'];
            $count = count($bridgeTemplates);
            $lines[] = $this->fillTemplate($bridgeTemplates[$seed % $count], $vars);
            $lines[] = $this->fillTemplate($bridgeTemplates[($seed >> 4) % $count], $vars);
        } elseif (strpos($part, 'Outro') !== false) {
            $outroTemplates = $templates['outro'];
            $template = $outroTemplates[$seed % count($outroTemplates)];
            $lines[] = $this->fillTemplate($template, $vars);
        }
        
        return implode("\n", $lines);
    }

    /**
     * Fill template with variables
     */
    private function fillTemplate(string $template, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }

    /**
     * Determine music style based on mood and genre
     */
    private function determineMusicStyle(string $mood, string $genre, int $index): string
    {
        $styles = $this->moodToGenre[$mood] ?? $this->moodToGenre['Misterio'];
        
        // Add genre influence if available
        if (!empty($genre)) {
            $genreStyles = [
                'Fantasía' => ['fantasy', 'magical', 'ethereal'],
                'Ciencia Ficción' => ['sci-fi', 'futuristic', 'electronic'],
                'Romance' => ['romantic', 'love', 'emotional'],
                'Terror' => ['horror', 'dark', 'creepy'],
                'Thriller' => ['suspenseful', 'intense', 'mysterious']
            ];
            
            if (isset($genreStyles[$genre])) {
                $styles = array_merge($styles, $genreStyles[$genre]);
            }
        }
        
        // Use index to ensure different songs from same book get different styles
        return $styles[$index % count($styles)];
    }

    /**
     * Generate detailed melody description
     */
    private function generateDetailedMelodyDescription(string $mood, string $style, string $voiceGender, int $index): string
    {
        $voice = $voiceGender === 'male' ? 'voz masculina' : 'voz femenina';
        $duration = '2-3 minutos';
        
        // Variation 1 (Second song): Minimalistic / Alternate Vibe
        if (($index % 2) === 1) {
             // Invert descriptors or use alternate approach
             $adjectives = ['profundo', 'etéreo', 'minimalista', 'experimental'];
             $adj = $adjectives[$index % count($adjectives)];
             
             return "Versión alternativa {$adj} (1:30 min). Enfoque minimalista con {$voice}, explorando el lado más oculto de la historia. Estilo {$style} con arreglos inversos y atmósfera única.";
        }

        $descriptions = [
            "Canción completa de {$duration} con {$voice} expresiva. Estilo {$style} que captura la esencia de {$mood}. Producción profesional con instrumentación rica y arreglos dinámicos.",
            "Composición original de {$duration} interpretada por {$voice} emotiva. Género {$style} con elementos de {$mood}. Estructura completa con intro, versos, coros y puente.",
            "Track de {$duration} con {$voice} cautivadora en estilo {$style}. Atmósfera {$mood} con producción moderna y mezcla profesional.",
            "Canción única de {$duration} con {$voice} potente. Fusión de {$style} que evoca {$mood}. Arreglos complejos y progresión emocional."
        ];
        
        return $descriptions[$index % count($descriptions)];
    }

    /**
     * Extract meaningful keywords from synopsis
     */
    private function extractKeywords(string $synopsis, string $title): array
    {
        $keywords = [];
        
        // Extract capitalized words (potential character names and locations)
        preg_match_all('/\b[A-ZÁÉÍÓÚÑ][a-záéíóúñ]{2,}\b/', $synopsis, $matches);
        
        if (!empty($matches[0])) {
            // Filter out common words
            $commonWords = ['El', 'La', 'Los', 'Las', 'Un', 'Una', 'Unos', 'Unas', 'Este', 'Esta', 'Estos', 'Estas'];
            $filtered = array_diff($matches[0], $commonWords);
            $filtered = array_values($filtered);
            
            $keywords['protagonist'] = $filtered[0] ?? 'el protagonista';
            $keywords['location'] = $filtered[1] ?? 'este mundo';
        } else {
            $keywords['protagonist'] = 'el protagonista';
            $keywords['location'] = 'este mundo';
        }
        
        // Extract thematic keywords
        $themeWords = [
            'amor' => 'el amor', 'muerte' => 'la muerte', 'guerra' => 'la guerra',
            'paz' => 'la paz', 'venganza' => 'la venganza', 'justicia' => 'la justicia',
            'libertad' => 'la libertad', 'destino' => 'el destino', 'poder' => 'el poder',
            'verdad' => 'la verdad', 'secreto' => 'el secreto', 'misterio' => 'el misterio'
        ];
        
        $keywords['theme'] = 'el destino';
        $keywords['goal'] = 'la verdad';
        $keywords['emotion'] = 'esperanza';
        
        foreach ($themeWords as $word => $phrase) {
            if (stripos($synopsis, $word) !== false) {
                $keywords['theme'] = $phrase;
                break;
            }
        }
        
        // Extract emotional keywords
        $emotionWords = [
            'esperanza', 'miedo', 'amor', 'odio', 'tristeza', 'alegría',
            'nostalgia', 'pasión', 'dolor', 'felicidad'
        ];
        
        foreach ($emotionWords as $emotion) {
            if (stripos($synopsis, $emotion) !== false) {
                $keywords['emotion'] = $emotion;
                break;
            }
        }
        
        return $keywords;
    }

    /**
     * Get shortened version of book title
     */
    private function getShortBookTitle(string $title): string
    {
        $words = explode(' ', $title);
        if (count($words) <= 2) {
            return $title;
        }
        
        // Return first 2-3 significant words
        $short = $words[0];
        if (strlen($words[0]) < 4 && isset($words[1])) {
            $short .= ' ' . $words[1];
        }
        
        return $short;
    }
}
