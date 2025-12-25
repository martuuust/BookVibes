<?php

namespace App\Services;

class MoodAnalyzer
{
    private array $moodArtists = [
        'Romántico' => ['Taylor Swift','Olivia Rodrigo','Ed Sheeran','Ariana Grande','Lewis Capaldi','Shawn Mendes','Sam Smith','Lana Del Rey','Sabrina Carpenter','SZA'],
        'Intriga y Suspenso' => ['Billie Eilish','Halsey','The Weeknd','Lorde','Banks','Grimes','Florence + The Machine','Sevdaliza','Lana Del Rey','Aurora'],
        'Épico y Aventurero' => ['Imagine Dragons','Sia','The Weeknd','Coldplay','Zayn','OneRepublic','Linkin Park','Kendrick Lamar','Post Malone','Thirty Seconds To Mars'],
        'Fantasía' => ['Howard Shore','Hans Zimmer','Ramin Djawadi','Enya','Loreena McKennitt','Two Steps From Hell','Audiomachine','Jeremy Soule','Bear McCreary','John Williams'],
        'Melancólico' => ['Phoebe Bridgers','Bon Iver','Billie Eilish','The National','Hozier','James Blake','Daughter','Keaton Henson','Noah Kahan','Adele'],
        'Neutral' => ['Dua Lipa','Harry Styles','Doja Cat','Bad Bunny','Karol G','Rosalía','Feid','Peso Pluma','Rauw Alejandro','J Balvin']
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
                    break 2; // Break from both inner and outer loops
                }
            }
        }

        $playlistKeywords = $moodSpecificPlaylistKeywords[$mood] ?? ['ambient study music', 'focus playlist', 'chill lofi'];

        $queries = [];
        $title = $bookData['title'] ?? '';
        $author = $bookData['author'] ?? '';
        $genre = $bookData['genre'] ?? '';

        // Base queries combining mood, genre, title, author
        if (!empty($mood) && $mood !== 'Neutral') {
            foreach ($playlistKeywords as $pk) {
                $queries[] = "$mood $pk";
                if (!empty($title)) $queries[] = "$title $mood $pk";
                if (!empty($author)) $queries[] = "$author $mood $pk";
                if (!empty($genre)) $queries[] = "$genre $mood $pk";
            }
        }

        // More specific queries
        if (!empty($title)) {
            $queries[] = "$title soundtrack";
            $queries[] = "$title theme song";
            $queries[] = "$title music playlist";
        }
        if (!empty($author)) {
            $queries[] = "$author inspired playlist";
            $queries[] = "$author writing music";
        }
        if (!empty($genre)) {
            $queries[] = "$genre music playlist";
            $queries[] = "$genre ambient music";
        }

        // Add artists from the mood list
        $artists = $this->moodArtists[$mood] ?? $this->moodArtists['Neutral'];
        foreach ($artists as $artist) {
            $queries[] = "$artist $mood songs";
            $queries[] = "$artist $genre playlist";
            if (!empty($title)) $queries[] = "$artist inspired by $title";
        }

        // Ensure uniqueness and limit queries to avoid excessive API calls
        $queries = array_unique($queries);
        // Shuffle to get a good mix of queries
        shuffle($queries);
        // Limit the number of queries sent to YouTube to a reasonable amount, e.g., 20-30
        $queries = array_slice($queries, 0, 30);
    
        $yt = new YouTubeSearchService();
        $tracks = $yt->searchTracks($queries, 12, $artists);
        if (count($tracks) < 5) {
            $extra = [];
            if ($mood === 'Intriga y Suspenso') {
                $extra = ['mystery soundtrack', 'thriller score', 'noir jazz'];
            } elseif ($mood === 'Romántico') {
                $extra = ['romantic ballads', 'love songs acoustic', 'piano love themes'];
            } elseif ($mood === 'Épico y Aventurero') {
                $extra = ['epic orchestral', 'adventure soundtrack', 'heroic themes'];
            } elseif ($mood === 'Fantasía') {
                $extra = ['fantasy movie soundtrack', 'magical themes', 'epic fantasy score'];
            } elseif ($mood === 'Melancólico') {
                $extra = ['sad songs indie', 'melancholic ambient', 'piano melancholy'];
            } else {
                $extra = ['ambient study music', 'focus playlist', 'chill lofi'];
            }
            $moreQueries = [];
            foreach ($extra as $ex) {
                $moreQueries[] = $mood . ' ' . $ex;
            }
            foreach ($artists as $artist) {
                $moreQueries[] = $artist . ' ' . $mood . ' lyrics';
                $moreQueries[] = $artist . ' ' . $mood . ' official video';
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

    private function generateMockTracks(array $genres): array
    {
        $tracks = [];
        for ($i = 0; $i < 5; $i++) {
            $genre = $genres[array_rand($genres)];
            $tracks[] = [
                'title' => "Track #$i ($genre)",
                'artist' => "Artista de $genre",
                'url' => '#'
            ];
        }
        return $tracks;
    }
}
