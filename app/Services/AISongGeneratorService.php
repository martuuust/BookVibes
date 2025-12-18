<?php

namespace App\Services;

class AISongGeneratorService
{
    private $moodDescriptors = [
        'Melancolía' => ['piano', 'violonchelo', 'lento', 'suave', 'nostálgico'],
        'Alegría' => ['guitarra acústica', 'ukelele', 'rápido', 'optimista', 'brillante'],
        'Misterio' => ['sintetizador', 'cuerdas bajas', 'suspenso', 'enigmático', 'oscuro'],
        'Romance' => ['piano', 'violín', 'apasionado', 'suave', 'cálido'],
        'Aventura' => ['orquesta', 'percusión', 'épico', 'grandioso', 'dinámico'],
        'Terror' => ['ruido blanco', 'violines estridentes', 'tenso', 'aterrador', 'discordante'],
        'Fantasía' => ['arpa', 'flauta', 'mágico', 'etéreo', 'encantador'],
        'Ciencia Ficción' => ['sintetizador', 'theremin', 'futurista', 'espacial', 'electrónico']
    ];

    private $lyricTemplates = [
        'general' => [
            'verse' => [
                "En las sombras de {location}, {protagonist} camina con dudas.",
                "El eco de {theme} resuena en la noche eterna.",
                "Bajo el cielo de {mood}, el destino se escribe.",
                "Entre páginas de {theme}, se esconde la verdad.",
                "Los muros de {location} guardan secretos antiguos.",
                "Nadie sabe si {protagonist} encontrará {goal}."
            ],
            'chorus' => [
                "Oh, {title}, tu secreto vive en mí.",
                "Volando a través del tiempo, buscando {goal}.",
                "{mood} es el camino, {theme} es el final.",
                "Grita al viento, {protagonist}, tu historia no morirá.",
                "Todo gira en torno a {goal}, nada es lo que parece.",
                "La leyenda de {title} renace hoy."
            ],
            'bridge' => [
                "Y aunque el tiempo pase, la memoria queda.",
                "Un giro inesperado cambia el rumbo de todo.",
                "No hay vuelta atrás para {protagonist}."
            ],
            'outro' => [
                "Así termina el viaje en {location}...",
                "El silencio cae sobre {title}...",
                "Buscando {goal} hasta el fin."
            ]
        ],
        'Romance' => [
            'verse' => [
                "Suspiros en {location}, {protagonist} espera una señal.",
                "El corazón late fuerte por {theme}.",
                "Cada mirada en {location} cuenta una historia."
            ],
            'chorus' => [
                "Amor en {title}, pasión que no se apaga.",
                "Juntos buscando {goal} contra el mundo.",
                "Bajo la luna de {mood}, prometemos eternidad."
            ]
        ],
        'Terror' => [
            'verse' => [
                "Oscuridad en {location}, {protagonist} siente el miedo.",
                "Gritos ahogados por {theme} en la niebla.",
                "Algo acecha detrás de la sombra de {goal}."
            ],
            'chorus' => [
                "Pesadilla en {title}, no puedes escapar.",
                "El {mood} te consume, {theme} te atrapará.",
                "Corre, {protagonist}, antes de que sea tarde."
            ]
        ]
    ];

    public function generateSongs(array $bookData, int $count = 2): array
    {
        $songs = [];
        $mood = $bookData['mood'] ?? 'Misterio';
        $title = $bookData['title'] ?? 'Libro Desconocido';
        $author = $bookData['author'] ?? 'Autor Desconocido';
        $synopsis = $bookData['synopsis'] ?? '';

        // Extract keywords
        $keywords = $this->extractKeywords($synopsis);
        $protagonist = $keywords['protagonist'] ?? 'el protagonista';
        $location = $keywords['location'] ?? 'este mundo';
        $theme = $keywords['theme'] ?? 'el misterio';
        $goal = $keywords['goal'] ?? 'la verdad';

        for ($i = 0; $i < $count; $i++) {
            $variation = $i % 2;
            $songTitle = $this->generateTitle($title, $mood, $i);
            $melody = $this->generateMelodyDescription($mood, $variation);
            $lyrics = $this->generateFullLyrics($title, $mood, $protagonist, $location, $theme, $goal, $variation);

            $songs[] = [
                'title' => $songTitle,
                'artist' => 'BookVibes AI',
                'url' => '#',
                'is_ai_generated' => 1,
                'lyrics' => $lyrics,
                'melody_description' => $melody,
                'variation' => $variation
            ];
        }

        return $songs;
    }

    private function generateTitle($bookTitle, $mood, $index): string
    {
        $prefixes = ['Ecos de', 'Sombras en', 'La Balada de', 'Sueños de', 'El Ritmo de', 'Memorias de', 'Voces de'];
        $suffixes = ['Eterno', 'Perdido', 'Final', 'Azul', 'Prohibido', 'Renacido', 'Oculto'];
        
        if ($index % 2 == 0) {
            $shortTitle = explode(' ', $bookTitle)[0];
            if (strlen($shortTitle) < 4 && isset(explode(' ', $bookTitle)[1])) {
                $shortTitle .= ' ' . explode(' ', $bookTitle)[1];
            }
            return $prefixes[array_rand($prefixes)] . ' ' . $shortTitle;
        } else {
            return $mood . ' ' . $suffixes[array_rand($suffixes)];
        }
    }

    private function generateMelodyDescription($mood, $variation = 0): string
    {
        $descriptors = $this->moodDescriptors[$mood] ?? $this->moodDescriptors['Misterio'];
        
        if ($variation === 1) {
             // Alternative Vibe: Different selection
             $instruments = array_reverse(array_slice($descriptors, 0, 3)); 
             $adjective = $descriptors[4] ?? 'profundo';
             
             return "Versión alternativa " . $adjective . " (1:30 min). " .
                   "Enfoque minimalista con " . $instruments[0] . 
                   ", explorando el lado más " . ($descriptors[2] ?? 'oscuro') . " de la historia.";
        }

        $instruments = array_slice($descriptors, 0, 2);
        $adjectives = array_slice($descriptors, 2);
        
        return "Una composición " . $adjectives[array_rand($adjectives)] . " de 1:30 min. " .
               "Estructura progresiva liderada por " . $instruments[array_rand($instruments)] . 
               ", con un clímax emocional que evoca " . $mood . ".";
    }

    private function generateFullLyrics($title, $mood, $protagonist, $location, $theme, $goal, $variation = 0): string
    {
        $structure = ['[Verso 1]', '[Verso 2]', '[Coro]', '[Verso 3]', '[Coro]', '[Puente]', '[Coro Final]', '[Outro]'];
        
        if ($variation === 1) {
            // Distinct structure for second song
            $structure = ['[Intro]', '[Verso 1]', '[Coro]', '[Verso 2]', '[Puente]', '[Coro]', '[Outro]'];
        }

        $fullLyrics = "";
        
        // Use mood-specific templates if available, else general
        $templates = isset($this->lyricTemplates[$mood]) ? 
                     array_merge_recursive($this->lyricTemplates['general'], $this->lyricTemplates[$mood]) : 
                     $this->lyricTemplates['general'];

        foreach ($structure as $part) {
            $fullLyrics .= "$part\n";
            $lines = [];
            
            if (strpos($part, 'Intro') !== false) {
                 // Intro uses a single verse line for atmosphere
                 $lines[] = $this->fillTemplate($templates['verse'][array_rand($templates['verse'])], $title, $mood, $protagonist, $location, $theme, $goal);
            } elseif (strpos($part, 'Verso') !== false) {
                $lines[] = $this->fillTemplate($templates['verse'][array_rand($templates['verse'])], $title, $mood, $protagonist, $location, $theme, $goal);
                $lines[] = $this->fillTemplate($templates['verse'][array_rand($templates['verse'])], $title, $mood, $protagonist, $location, $theme, $goal);
            } elseif (strpos($part, 'Coro') !== false) {
                $lines[] = $this->fillTemplate($templates['chorus'][array_rand($templates['chorus'])], $title, $mood, $protagonist, $location, $theme, $goal);
                $lines[] = $this->fillTemplate($templates['chorus'][array_rand($templates['chorus'])], $title, $mood, $protagonist, $location, $theme, $goal);
            } elseif (strpos($part, 'Puente') !== false) {
                 $lines[] = $this->fillTemplate($templates['bridge'][array_rand($templates['bridge'])], $title, $mood, $protagonist, $location, $theme, $goal);
            } elseif (strpos($part, 'Outro') !== false) {
                 $lines[] = $this->fillTemplate($templates['outro'][array_rand($templates['outro'])], $title, $mood, $protagonist, $location, $theme, $goal);
            }
            
            $fullLyrics .= implode("\n", $lines) . "\n\n";
        }

        return trim($fullLyrics);
    }

    private function fillTemplate($template, $title, $mood, $protagonist, $location, $theme, $goal) {
        return str_replace(
            ['{location}', '{protagonist}', '{theme}', '{mood}', '{title}', '{goal}'],
            [$location, $protagonist, $theme, strtolower($mood), $title, $goal],
            $template
        );
    }

    private function extractKeywords($synopsis): array
    {
        // Very basic extraction simulation
        $keywords = [];
        
        // Try to find capitalized words not at start of sentence (potential names)
        // This is a naive heuristic
        preg_match_all('/(?<=\s)[A-Z][a-z]+/', $synopsis, $matches);
        if (!empty($matches[0])) {
            $keywords['protagonist'] = $matches[0][0]; // Pick first found name
            if (count($matches[0]) > 1) $keywords['location'] = $matches[0][1];
        }

        $keywords['theme'] = 'el destino';
        $keywords['goal'] = 'la libertad';

        return $keywords;
    }
}
