<?php

namespace App\Services;

class YouTubeSearchService
{
    public function __construct()
    {
    }

    public function searchTracks(array $queries, int $limit = 10, array $preferredArtists = []): array
    {
        set_time_limit(300);
        $seen = [];
        $candidates = [];
        foreach ($queries as $q) {
            $tracks = $this->searchOnce($q);
            foreach ($tracks as $t) {
                $key = mb_strtolower(trim(($t['title'] ?? '') . '|' . ($t['artist'] ?? '')));
                if ($key === '' || isset($seen[$key])) continue;
                $seen[$key] = true;
                $t['score'] = $this->scoreTrack($t, $preferredArtists);
                $candidates[] = $t;
            }
        }
        usort($candidates, function ($a, $b) {
            return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
        });
        $final = [];
        $artistCounts = [];
        foreach ($candidates as $t) {
            $artistKey = mb_strtolower(trim($t['artist'] ?? ''));
            if ($artistKey === '') $artistKey = 'desconocido';
            $count = $artistCounts[$artistKey] ?? 0;
            if ($count >= 2) continue;
            $final[] = $t;
            $artistCounts[$artistKey] = $count + 1;
            if (count($final) >= $limit) break;
        }
        return $final;
    }

    private function searchOnce(string $query): array
    {
        $url = 'https://www.youtube.com/results?search_query=' . urlencode($query);
        $html = $this->httpGet($url);
        if ($html === null) return [];
        $data = $this->extractInitialData($html);
        if (!$data) return [];
        $videos = $this->extractVideoRenderers($data);
        $out = [];
        foreach ($videos as $v) {
            $title = $this->getText($v['title']['runs'] ?? []);
            $artist = $this->getText($v['longBylineText']['runs'] ?? ($v['shortBylineText']['runs'] ?? []));
            $id = $v['videoId'] ?? null;
            if (!$id) continue;
            $ageText = $v['publishedTimeText']['simpleText'] ?? ($v['publishedTimeText']['runs'][0]['text'] ?? '');
            $item = [
                'title' => $title ?: 'Desconocido',
                'artist' => $artist ?: '',
                'url' => 'https://www.youtube.com/watch?v=' . $id,
                'age_text' => $ageText,
                'years_ago' => $this->extractYearsAgo($ageText)
            ];
            if ($this->isIrrelevant($title)) continue;
            $out[] = $item;
        }
        return $out;
    }
 
    private function httpGet(string $url): ?string
    {
        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9,en;q=0.8'
        ];
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 6
            ],
            'https' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers),
                'timeout' => 6
            ]
        ]);
        try {
            $resp = @file_get_contents($url, false, $context);
            if ($resp === false) return null;
            return $resp;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function extractInitialData(string $html): ?array
    {
        $m = [];
        if (!preg_match('/ytInitialData\s*=\s*(\{.*?\});/s', $html, $m)) {
            return null;
        }
        try {
            return json_decode($m[1], true);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function extractVideoRenderers(array $data): array
    {
        $root = $data['contents']['twoColumnSearchResultsRenderer']['primaryContents']['sectionListRenderer']['contents'] ?? [];
        $items = [];
        foreach ($root as $section) {
            $contents = $section['itemSectionRenderer']['contents'] ?? [];
            foreach ($contents as $c) {
                if (isset($c['videoRenderer'])) {
                    $items[] = $c['videoRenderer'];
                }
            }
        }
        return $items;
    }
 
    private function getText(array $runs): string
    {
        $t = '';
        foreach ($runs as $r) {
            $t .= $r['text'] ?? '';
        }
        return $t;
    }

    private function isIrrelevant(string $title): bool
    {
        $t = mb_strtolower($title);
        $bad = [
            'cover', 'karaoke', 'reaction', 'remix', 'sped up', 'slowed', '8d', 'nightcore',
            'live', 'tribute', 'fan made', 'mashup', 'mix', 'compilation',
            'full album', 'playlist', 'audio only', 'teaser', 'snippet',
            'instrumental', 'backing track', 'no vocals', 'tutorial', 'lesson',
            '1 hour', '10 hours', 'loop', 'ambient', 'study music', 'lofi',
            'soundtrack score', 'original score', 'ost full', 'theme extended'
        ];
        foreach ($bad as $b) {
            if (str_contains($t, $b)) return true;
        }
        return false;
    }

    private function scoreTrack(array $t, array $preferredArtists = []): int
    {
        $title = mb_strtolower($t['title'] ?? '');
        $artist = mb_strtolower($t['artist'] ?? '');
        $score = 0;
        
        // Positive signals for real songs
        if (str_contains($title, 'official video')) $score += 10;
        if (str_contains($title, 'music video')) $score += 8;
        if (str_contains($title, 'lyrics')) $score += 7;
        if (str_contains($title, 'official audio')) $score += 5;
        if (str_contains($title, 'visualizer')) $score += 2;
        
        // Artist verification
        if (str_contains($artist, 'vevo')) $score += 8;
        if (str_contains($artist, 'topic')) $score += 4;
        if (str_contains($artist, 'official') || str_contains($artist, 'records') || str_contains($artist, 'music')) $score += 3;
        if (strlen($artist) > 0) $score += 1;
        
        // Preferred mainstream artists boost
        foreach ($preferredArtists as $pa) {
            if ($pa && (str_contains($artist, mb_strtolower($pa)) || str_contains($title, mb_strtolower($pa)))) {
                $score += 15; // Huge boost for requested artists
                break;
            }
        }
        
        // Penalize unwanted formats
        if (str_contains($title, 'top ') || str_contains($title, 'playlist') || str_contains($title, 'full album')) $score -= 10;
        if (str_contains($title, 'instrumental') || str_contains($title, 'karaoke')) $score -= 20;
        if (str_contains($title, 'live') || str_contains($title, 'concert') || str_contains($title, 'acoustic version')) $score -= 5;
        if (str_contains($title, 'behind the scenes') || str_contains($title, 'making of')) $score -= 20;
        
        // Penalize age slightly, but good songs are timeless
        $years = (int)($t['years_ago'] ?? 0);
        if ($years >= 20) $score -= 2; // Classic hits are fine, just slight penalty
        
        return $score;
    }

    private function extractYearsAgo(string $ageText): int
    {
        $txt = mb_strtolower(trim($ageText));
        if ($txt === '') return 0;
        if (preg_match('/(\d+)\s*(years?|años)\s*ago/', $txt, $m)) {
            return (int)$m[1];
        }
        if (preg_match('/hace\s*(\d+)\s*años/', $txt, $m)) {
            return (int)$m[1];
        }
        return 0;
    }
}
