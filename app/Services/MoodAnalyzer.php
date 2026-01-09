<?php

namespace App\Services;

class MoodAnalyzer
{
    private $geminiKey;
    private $groqKey;
    
    // Primary: Gemini
    private $geminiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
    
    // Secondary: Groq
    private $groqUrl = 'https://api.groq.com/openai/v1/chat/completions';
    private $groqModel = 'llama-3.3-70b-versatile';

    private array $moodArtists = [
        'Romántico' => ['Taylor Swift', 'Ed Sheeran', 'Adele', 'Bruno Mars', 'John Legend', 'Sam Smith', 'Ariana Grande', 'Lana Del Rey', 'Rosalía', 'Camilo'],
        'Misterio' => ['Billie Eilish', 'The Weeknd', 'Lana Del Rey', 'Arctic Monkeys', 'The Neighbourhood', 'Twenty One Pilots', 'Chase Atlantic', 'Maneskin'],
        'Aventura' => ['Imagine Dragons', 'Coldplay', 'OneRepublic', 'Bastille', 'Fall Out Boy', 'Panic! At The Disco', 'The Killers', 'Florence + The Machine', 'Thirty Seconds To Mars'],
        'Fantasía' => ['Florence + The Machine', 'Aurora', 'Halsey', 'Grimes', 'Björk', 'Enya', 'Evanescence', 'Within Temptation', 'Nightwish', 'Lindsey Stirling', 'Howard Shore'],
        'Terror' => ['Marilyn Manson', 'Rob Zombie', 'Nine Inch Nails', 'The Cure', 'Slipknot', 'Ghost', 'Alice Cooper', 'My Chemical Romance', 'Muse'],
        'Comedia' => ['Smash Mouth', 'Weird Al Yankovic', 'The Lonely Island', 'Tenacious D', 'Bruno Mars', 'Katy Perry', 'DNCE', 'Meghan Trainor'],
        'Drama' => ['Adele', 'Lewis Capaldi', 'Billie Eilish', 'Sam Smith', 'Conan Gray', 'Joji', 'Kodaline', 'Birdy', 'Tom Odell', 'Bon Iver', 'Hozier'],
        'Neutral' => ['Dua Lipa', 'The Weeknd', 'Taylor Swift', 'Harry Styles', 'Rihanna', 'Katy Perry', 'Lady Gaga', 'Maroon 5', 'Ed Sheeran', 'Coldplay']
    ];

    public function __construct()
    {
        $this->geminiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? null);
        $this->groqKey = getenv('GROQ_API_KEY') ?: ($_ENV['GROQ_API_KEY'] ?? null);
    }
    
    public function getPreferredArtistsForMood(string $mood): array
    {
        return $this->moodArtists[$mood] ?? $this->moodArtists['Neutral'];
    }

    public function analyze(array $bookData): array
    {
        // Try AI Analysis (Primary + Secondary Fallback)
        if (!empty($this->geminiKey) || !empty($this->groqKey)) {
            try {
                return $this->analyzeWithAI($bookData);
            } catch (\Exception $e) {
                // error_log("All AI Services Failed: " . $e->getMessage());
                // Fallback silently to keywords
            }
        }

        // Local Keyword Logic (Tertiary Fallback)
        $text = ($bookData['synopsis'] ?? '') . ' ' . implode(' ', $bookData['keywords'] ?? []);
        $text = strtolower($text);

        $moodKeywords = [
            'Terror' => ['terror', 'miedo', 'horror', 'sangre', 'pesadilla', 'monstruo', 'oscuro', 'muerte', 'asesino', 'pánico', 'fantasmas', 'demonio', 'maldición'],
            'Fantasía' => ['fantasía', 'magia', 'hechicero', 'bruja', 'sobrenatural', 'imaginario', 'mítico', 'mundo mágico', 'elfo', 'dragón', 'poderes', 'leyenda', 'reino'],
            'Romántico' => ['amor', 'romance', 'pasión', 'corazón', 'enamorado', 'cita', 'beso', 'sentimientos', 'deseo', 'ternura', 'felicidad', 'pareja', 'boda', 'alma gemela'],
            'Misterio' => ['misterio', 'crimen', 'secreto', 'asesinato', 'investigación', 'peligro', 'sospecha', 'tensión', 'giro', 'conspiración', 'desaparición', 'detective', 'enigma', 'intriga', 'suspenso'],
            'Aventura' => ['aventura', 'épico', 'viaje', 'héroe', 'batalla', 'búsqueda', 'exploración', 'descubrimiento', 'desafío', 'guerra', 'profecía', 'conquista', 'acción', 'odisea'],
            'Comedia' => ['comedia', 'humor', 'divertido', 'risa', 'gracioso', 'broma', 'sarcasmo', 'sátira', 'chiste', 'ironía', 'parodia', 'absurdo'],
            'Drama' => ['tristeza', 'drama', 'llorar', 'pérdida', 'soledad', 'desesperación', 'nostalgia', 'dolor', 'pena', 'desamor', 'melancolía', 'sufrimiento', 'conmovedor'],
            'Neutral' => []
        ];

        $moodSpecificPlaylistKeywords = [
            'Romántico' => ['love songs lyrics', 'romantic pop audio', 'emotional ballads lyrics', 'popular love songs lyrics', 'wedding songs audio'],
            'Terror' => ['horror soundtrack', 'spooky songs', 'dark ambient', 'creepy music', 'suspenseful horror audio'],
            'Misterio' => ['dark pop lyrics', 'mystery vibe audio', 'suspenseful songs lyrics', 'noir soundtrack', 'intense pop lyrics'],
            'Fantasía' => ['ethereal pop lyrics', 'fantasy vibe lyrics', 'magical pop audio', 'epic fantasy soundtrack', 'mystical songs audio'],
            'Aventura' => ['epic pop rock lyrics', 'adventure songs lyrics', 'powerful anthems audio', 'heroic songs lyrics', 'motivational rock lyrics'],
            'Comedia' => ['funny songs', 'upbeat pop', 'happy vibes', 'comedy hits', 'feel good audio'],
            'Drama' => ['sad pop lyrics', 'emotional songs lyrics', 'breakup songs audio', 'sad ballads lyrics', 'depressing songs lyrics'],
            'Neutral' => ['pop hits lyrics', 'top hits audio', 'trending songs lyrics', 'viral songs audio', 'radio hits lyrics']
        ];

        $mood = 'Neutral';
        foreach ($moodKeywords as $m => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $mood = $m;
                    break 2;
                }
            }
        }

        $playlistKeywords = $moodSpecificPlaylistKeywords[$mood] ?? $moodSpecificPlaylistKeywords['Neutral'];
        
        $queries = [];
        $genre = $bookData['genre'] ?? '';
        $artists = $this->moodArtists[$mood] ?? $this->moodArtists['Neutral'];
        shuffle($artists);
        $selectedArtists = array_slice($artists, 0, 8);

        foreach ($selectedArtists as $artist) {
            $queries[] = "$artist official video";
            $queries[] = "$artist best songs";
            if ($mood !== 'Neutral') {
                 $queries[] = "$artist $mood songs";
            }
        }

        foreach ($playlistKeywords as $pk) {
            $queries[] = "$pk";
            if (!empty($genre)) $queries[] = "$genre $pk";
        }

        $queries = array_unique($queries);
        shuffle($queries);
        $queries = array_slice($queries, 0, 30);
    
        $yt = new YouTubeSearchService();
        $tracks = $yt->searchTracks($queries, 12, $artists);

        if (count($tracks) < 5) {
             $moreQueries = [];
             foreach ($selectedArtists as $artist) {
                $moreQueries[] = "$artist lyrics";
             }
             $more = $yt->searchTracks($moreQueries, 12, $artists);
             
             $seenKeys = [];
             foreach ($tracks as $t) { $seenKeys[mb_strtolower(($t['title'] ?? '').'|'.($t['artist'] ?? ''))] = true; }
             
             foreach ($more as $t) {
                $key = mb_strtolower(($t['title'] ?? '').'|'.($t['artist'] ?? ''));
                if (!isset($seenKeys[$key])) {
                    $tracks[] = $t;
                    $seenKeys[$key] = true;
                    if (count($tracks) >= 8) break;
                }
             }
        }

        return [
            'mood' => $mood,
            'keywords' => $playlistKeywords,
            'suggested_tracks' => $tracks
        ];
    }
    
    private function analyzeWithAI(array $bookData): array
    {
        $title = $bookData['title'] ?? 'Unknown Book';
        $author = $bookData['author'] ?? 'Unknown Author';
        $synopsis = $bookData['synopsis'] ?? '';
        $keywords = implode(', ', $bookData['keywords'] ?? []);
        
        $validMoods = ['Romántico', 'Misterio', 'Aventura', 'Fantasía', 'Terror', 'Drama', 'Comedia', 'Neutral'];
        $moodsStr = implode(', ', $validMoods);

        $prompt = "Analyze the book '$title' by '$author'. Synopsis: $synopsis. Keywords: $keywords.
        
        Task:
        1. Classify the Mood into exactly one of these: $moodsStr.
        2. Verify if the book involves magic, dragons, or supernatural worlds -> 'Fantasía'.
        3. Verify if the book involves horror, fear, monsters -> 'Terror'.
        4. Recommend 12 songs that perfectly match availability on YouTube and the specific scenes/tone/characters of this book.
        
        Return ONLY valid JSON:
        {
            \"mood\": \"MoodName\",
            \"songs\": [
                {\"title\": \"Song Title\", \"artist\": \"Artist Name\"},
                ...
            ]
        }";

        $content = '';
        $usedService = '';

        // 1. Primary: Gemini
        if (!empty($this->geminiKey)) {
            try {
                $response = $this->callGemini($prompt);
                $content = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $usedService = 'gemini';
            } catch (\Exception $e) {
                // Ignore, try fallback
            }
        }

        // 2. Secondary: Groq (if Gemini failed or no key)
        if (empty($content) && !empty($this->groqKey)) {
            try {
                $response = $this->callGroq($prompt);
                $content = $response['choices'][0]['message']['content'] ?? '';
                $usedService = 'groq';
            } catch (\Exception $e) {
               // Both failed
            }
        }
        
        if (empty($content)) {
            throw new \Exception("All AI services failed to return content");
        }

        // Sanitize JSON (remove markdown ticks if present)
        $content = preg_replace('/^```json\s*/', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        $content = trim($content);
        
        $data = json_decode($content, true);
        
        if (empty($data) || empty($data['songs'])) throw new \Exception("Invalid AI Response ($usedService)");

        $mood = in_array($data['mood'] ?? '', $validMoods) ? $data['mood'] : 'Neutral';
        $songs = $data['songs'];

        $queries = [];
        $artists = [];
        foreach ($songs as $s) {
            $t = $s['title'];
            $a = $s['artist'];
            $queries[] = "$t $a official audio";
            $queries[] = "$t $a lyrics";
            $artists[] = $a;
        }

        $yt = new YouTubeSearchService();
        $tracks = $yt->searchTracks(array_unique($queries), 12, array_unique($artists));
        
        return [
            'mood' => $mood,
            'keywords' => ['AI Curated', "Inspired by $title"],
            'suggested_tracks' => $tracks
        ];
    }

    private function callGemini($prompt)
    {
        $url = $this->geminiUrl . "?key=" . $this->geminiKey;
        $data = [
            "contents" => [
                ["parts" => [["text" => $prompt]]]
            ],
            "generationConfig" => ["response_mime_type" => "application/json"]
        ];

        return $this->executeCurl($url, $data, ['Content-Type: application/json']);
    }

    private function callGroq($prompt)
    {
        $data = [
            "model" => $this->groqModel,
            "messages" => [
                ["role" => "system", "content" => "You are a music and literary expert that outputs JSON only."],
                ["role" => "user", "content" => $prompt]
            ],
            "response_format" => ["type" => "json_object"], 
            "temperature" => 0.7
        ];

        return $this->executeCurl($this->groqUrl, $data, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->groqKey
        ]);
    }

    private function executeCurl(string $url, array $data, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \Exception("Curl Error: $err");
        }
        curl_close($ch);

        $json = json_decode($result, true);
        if ($httpCode >= 400 || !$json) {
            $msg = $json['error']['message'] ?? "HTTP $httpCode";
            throw new \Exception("API Error: $msg");
        }
        return $json;
    }

    public function analyzeMoodOnly(array $bookData): array
    {
        // Keep Local for speed in lists
        $text = ($bookData['synopsis'] ?? '') . ' ' . implode(' ', $bookData['keywords'] ?? []);
        $text = strtolower($text);

        $moodKeywords = [
            'Terror' => ['terror', 'miedo', 'horror', 'sangre', 'pesadilla', 'monstruo', 'oscuro', 'muerte', 'asesino', 'pánico', 'zombie'],
            'Fantasía' => ['fantasía', 'magia', 'hechicero', 'bruja', 'sobrenatural', 'imaginario', 'mítico', 'elfo', 'dragón', 'hadas'],
            'Romántico' => ['amor', 'romance', 'pasión', 'corazón', 'enamorado', 'cita', 'beso', 'pareja', 'boda'],
            'Misterio' => ['misterio', 'crimen', 'secreto', 'asesinato', 'investigación', 'peligro', 'sospecha', 'intriga'],
            'Aventura' => ['aventura', 'épico', 'viaje', 'héroe', 'batalla', 'búsqueda', 'acción', 'guerra', 'odisea'],
            'Comedia' => ['comedia', 'humor', 'divertido', 'risa', 'gracioso', 'broma', 'sátira'],
            'Drama' => ['tristeza', 'drama', 'llorar', 'pérdida', 'soledad', 'nostalgia', 'dolor', 'desamor'],
            'Neutral' => []
        ];

        $moodSpecificPlaylistKeywords = [
            'Romántico' => ['acoustic love songs', 'piano ballads', 'romantic pop', 'indie love'],
            'Terror' => ['dark ambient', 'horror soundtrack', 'creepy music', 'suspense score'],
            'Misterio' => ['jazz noir', 'mystery soundtrack', 'thriller score', 'dark pop'],
            'Fantasía' => ['fantasy soundtrack', 'epic aesthetic music', 'magical ambience', 'ethereal'],
            'Aventura' => ['epic music', 'adventure score', 'cinematic orchestral', 'heroic themes'],
            'Comedia' => ['happy songs', 'upbeat pop', 'comedy vibes', 'funky music'],
            'Drama' => ['sad piano', 'emotional soundtrack', 'melancholic cello', 'sad indie'],
            'Neutral' => ['study music', 'chill vibes', 'lofi hip hop', 'focus']
        ];

        $mood = 'Neutral';
        foreach ($moodKeywords as $m => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $mood = $m;
                    break 2;
                }
            }
        }

        $playlistKeywords = $moodSpecificPlaylistKeywords[$mood] ?? ['chill music'];

        return [
            'mood' => $mood,
            'keywords' => $playlistKeywords,
            'suggested_tracks' => []
        ];
    }
}
