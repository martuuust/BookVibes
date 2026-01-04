<?php

namespace App\Services;

class MoodAnalyzer
{
    private array $moodArtists = [
        'Romántico' => ['Taylor Swift', 'Ed Sheeran', 'Adele', 'Bruno Mars', 'John Legend', 'Sam Smith', 'Ariana Grande', 'Shawn Mendes', 'Olivia Rodrigo', 'Harry Styles', 'Lana Del Rey', 'Sabrina Carpenter', 'SZA', 'Rosalía', 'Camilo'],
        'Intriga y Suspenso' => ['Billie Eilish', 'The Weeknd', 'Lana Del Rey', 'Lorde', 'Hozier', 'Arctic Monkeys', 'The Neighbourhood', 'Halsey', 'Twenty One Pilots', 'Glass Animals', 'Banks', 'Sevdaliza', 'Chase Atlantic', 'Maneskin'],
        'Épico y Aventurero' => ['Imagine Dragons', 'Coldplay', 'OneRepublic', 'Bastille', 'The Script', 'Fall Out Boy', 'Panic! At The Disco', 'X Ambassadors', 'American Authors', 'The Killers', 'Sia', 'Florence + The Machine', 'Thirty Seconds To Mars', 'Linkin Park'],
        'Fantasía' => ['Florence + The Machine', 'Aurora', 'Halsey', 'Grimes', 'Björk', 'Lorde', 'Ellie Goulding', 'Sia', 'Of Monsters and Men', 'Enya', 'Evanescence', 'Within Temptation', 'Nightwish', 'Lindsey Stirling'],
        'Melancólico' => ['Adele', 'Lewis Capaldi', 'Billie Eilish', 'Sam Smith', 'Conan Gray', 'Joji', 'James Arthur', 'Kodaline', 'Birdy', 'Tom Odell', 'Phoebe Bridgers', 'Bon Iver', 'The National', 'Hozier', 'Noah Kahan'],
        'Neutral' => ['Dua Lipa', 'The Weeknd', 'Taylor Swift', 'Harry Styles', 'Rihanna', 'Katy Perry', 'Lady Gaga', 'Justin Bieber', 'Maroon 5', 'Ed Sheeran', 'Doja Cat', 'Bad Bunny', 'Karol G', 'Shakira', 'Bruno Mars']
    ];
    
    public function getPreferredArtistsForMood(string $mood): array
    {
        return $this->moodArtists[$mood] ?? $this->moodArtists['Neutral'];
    }

    public function analyze(array $bookData): array
    {
        $text = ($bookData['synopsis'] ?? '') . ' ' . implode(' ', $bookData['keywords'] ?? []);
        $text = strtolower($text);

        $moodKeywords = [
            'Romántico' => ['amor', 'romance', 'pasión', 'corazón', 'enamorado', 'cita', 'beso', 'sentimientos', 'deseo', 'ternura', 'felicidad', 'pareja', 'boda', 'destino', 'alma gemela'],
            'Intriga y Suspenso' => ['misterio', 'crimen', 'oscuro', 'secreto', 'asesinato', 'investigación', 'peligro', 'sospecha', 'terror', 'miedo', 'tensión', 'giro', 'conspiración', 'desaparición', 'persecución'],
            'Épico y Aventurero' => ['aventura', 'épico', 'viaje', 'héroe', 'batalla', 'reino', 'magia', 'dragón', 'búsqueda', 'exploración', 'descubrimiento', 'desafío', 'guerra', 'profecía', 'leyenda'],
            'Melancólico' => ['tristeza', 'drama', 'llorar', 'pérdida', 'soledad', 'desesperación', 'nostalgia', 'dolor', 'pena', 'desamor', 'melancolía', 'recuerdo', 'adiós', 'lágrimas', 'sufrimiento'],
            'Neutral' => [] // Default, will be filled if no specific mood is found
        ];

        $moodSpecificPlaylistKeywords = [
            'Romántico' => ['love songs hits', 'romantic pop songs', 'emotional ballads lyrics', 'popular love songs', 'wedding songs hits'],
            'Intriga y Suspenso' => ['dark pop hits', 'mystery vibe songs', 'suspenseful songs with lyrics', 'dark mood songs', 'intense pop songs'],
            'Épico y Aventurero' => ['epic pop rock', 'adventure songs with lyrics', 'powerful anthems', 'heroic songs with vocals', 'motivational rock hits'],
            'Fantasía' => ['ethereal pop songs', 'fantasy vibe songs with lyrics', 'magical pop hits', 'dreamy songs vocals', 'mystical songs'],
            'Melancólico' => ['sad pop hits', 'emotional songs with lyrics', 'breakup songs', 'sad ballads', 'depressing songs hits'],
            'Neutral' => ['pop hits', 'top hits', 'trending songs', 'viral songs', 'radio hits']
        ];

        $mood = 'Neutral';
        foreach ($moodKeywords as $m => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $mood = $m;
                    break 2; // Break from both inner and outer loops
                }
            }
        }

        $playlistKeywords = $moodSpecificPlaylistKeywords[$mood] ?? $moodSpecificPlaylistKeywords['Neutral'];

        $queries = [];
        $title = $bookData['title'] ?? '';
        $author = $bookData['author'] ?? '';
        $genre = $bookData['genre'] ?? '';

        // Add artists from the mood list (Randomize to avoid repetition)
        $artists = $this->moodArtists[$mood] ?? $this->moodArtists['Neutral'];
        shuffle($artists);
        $selectedArtists = array_slice($artists, 0, 8); // Select 8 random artists

        foreach ($selectedArtists as $artist) {
            $queries[] = "$artist best songs";
            $queries[] = "$artist hits";
            $queries[] = "$artist lyrics";
            if (!empty($mood) && $mood !== 'Neutral') {
                 $queries[] = "$artist $mood songs";
            }
        }

        // Base queries combining mood, genre
        foreach ($playlistKeywords as $pk) {
            $queries[] = "$pk";
            if (!empty($genre)) $queries[] = "$genre $pk";
        }

        // Ensure uniqueness and limit queries to avoid excessive API calls
        $queries = array_unique($queries);
        // Shuffle to get a good mix of queries
        shuffle($queries);
        // Limit the number of queries sent to YouTube to a reasonable amount, e.g., 20-30
        $queries = array_slice($queries, 0, 30);
    
        $yt = new YouTubeSearchService();
        $tracks = $yt->searchTracks($queries, 12, $artists);

        // Fallback strategies if few tracks found
        if (count($tracks) < 5) {
             // Try broader searches
             $moreQueries = [];
             foreach ($selectedArtists as $artist) {
                $moreQueries[] = "$artist official video";
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

    public function analyzeMoodOnly(array $bookData): array
    {
        $text = ($bookData['synopsis'] ?? '') . ' ' . implode(' ', $bookData['keywords'] ?? []);
        $text = strtolower($text);

        $moodKeywords = [
            'Romántico' => ['amor', 'romance', 'pasión', 'corazón', 'enamorado', 'cita', 'beso', 'sentimientos', 'deseo', 'ternura', 'felicidad', 'pareja', 'boda', 'destino', 'alma gemela'],
            'Intriga y Suspenso' => ['misterio', 'crimen', 'oscuro', 'secreto', 'asesinato', 'investigación', 'peligro', 'sospecha', 'terror', 'miedo', 'tensión', 'giro', 'conspiración', 'desaparición', 'persecución'],
            'Épico y Aventurero' => ['aventura', 'épico', 'viaje', 'héroe', 'batalla', 'reino', 'magia', 'dragón', 'búsqueda', 'exploración', 'descubrimiento', 'desafío', 'guerra', 'profecía', 'leyenda'],
            'Melancólico' => ['tristeza', 'drama', 'llorar', 'pérdida', 'soledad', 'desesperación', 'nostalgia', 'dolor', 'pena', 'desamor', 'melancolía', 'recuerdo', 'adiós', 'lágrimas', 'sufrimiento'],
            'Neutral' => []
        ];

        $moodSpecificPlaylistKeywords = [
            'Romántico' => ['acoustic love songs', 'piano ballads', 'romantic pop', 'indie love', 'soft rock romance'],
            'Intriga y Suspenso' => ['jazz noir', 'dark ambient', 'classical tension', 'mystery soundtrack', 'thriller score'],
            'Épico y Aventurero' => ['cinematic orchestral', 'folk rock adventure', 'epic scores', 'heroic themes', 'fantasy soundtrack'],
            'Melancólico' => ['indie folk sad', 'sad piano instrumental', 'ambient rain music', 'melancholic acoustic', 'heartbreak songs']
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

        $playlistKeywords = $moodSpecificPlaylistKeywords[$mood] ?? ['ambient study music', 'focus playlist', 'chill lofi'];

        return [
            'mood' => $mood,
            'keywords' => $playlistKeywords,
            'suggested_tracks' => []
        ];
    }

    // generateMockTracks removed

}
