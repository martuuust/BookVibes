<?php

namespace App\Services;

class MoodAnalyzer
{
    private array $moodArtists = [
        'Romántico' => ['Taylor Swift','Olivia Rodrigo','Ed Sheeran','Ariana Grande','Lewis Capaldi','Shawn Mendes','Sam Smith','Lana Del Rey','Sabrina Carpenter','SZA'],
        'Intriga y Suspenso' => ['Billie Eilish','Halsey','The Weeknd','Lorde','Banks','Grimes','Florence + The Machine','Sevdaliza','Lana Del Rey','Aurora'],
        'Épico y Aventurero' => ['Imagine Dragons','Sia','The Weeknd','Coldplay','Zayn','OneRepublic','Linkin Park','Kendrick Lamar','Post Malone','Thirty Seconds To Mars'],
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

        $mood = 'Neutral';
        $playlistKeywords = ['pop', 'ambient'];

        if (str_contains($text, 'misterio') || str_contains($text, 'crimen') || str_contains($text, 'oscuro')) {
            $mood = 'Intriga y Suspenso';
            $playlistKeywords = ['jazz noir', 'dark ambient', 'classical tension'];
        } elseif (str_contains($text, 'amor') || str_contains($text, 'romance') || str_contains($text, 'pasión')) {
            $mood = 'Romántico';
            $playlistKeywords = ['acoustic', 'piano ballads', 'slow pop'];
        } elseif (str_contains($text, 'aventura') || str_contains($text, 'épico') || str_contains($text, 'viaje')) {
            $mood = 'Épico y Aventurero';
            $playlistKeywords = ['cinematic orchestral', 'folk rock', 'epic scores'];
        } elseif (str_contains($text, 'tristeza') || str_contains($text, 'drama') || str_contains($text, 'llorar')) {
            $mood = 'Melancólico';
            $playlistKeywords = ['indie folk', 'sad piano', 'ambient rain'];
        }

        $queries = [];
        $qBase = trim(($bookData['genre'] ?? '') . ' ' . $mood);
        foreach ($playlistKeywords as $k) {
            $queries[] = $qBase . ' ' . $k . ' official video';
            $queries[] = $qBase . ' ' . $k . ' lyrics';
        }
        $queries[] = ($bookData['title'] ?? '') . ' theme song';
        $queries[] = ($bookData['author'] ?? '') . ' playlist';
        $artists = $this->moodArtists[$mood] ?? $this->moodArtists['Neutral'];
        foreach ($artists as $artist) {
            $queries[] = $artist . ' ' . $mood . ' official video';
            $queries[] = $artist . ' ' . ($bookData['genre'] ?? '') . ' lyrics';
        }
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
            } elseif ($mood === 'Melancólico') {
                $extra = ['sad songs indie', 'melancholic ambient', 'piano melancholy'];
            } else {
                $extra = ['ambient study music', 'focus playlist', 'chill lofi'];
            }
            $moreQueries = [];
            foreach ($extra as $ex) {
                $moreQueries[] = $qBase . ' ' . $ex;
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
