<?php

namespace App\Services;

class YouTubeSearchService
{
    public function __construct()
    {
    }

    public function searchTracks(array $queries, int $limit = 10, array $preferredArtists = []): array
    {
        set_time_limit(30); // Reduced from 300
        
        // Limit queries to avoid overload
        $queries = array_slice($queries, 0, 6);
        
        // Enforce "lyrics" in queries to find embeddable versions
        $enhancedQueries = [];
        foreach ($queries as $q) {
            $enhancedQueries[] = $q;
            if (!str_contains(strtolower($q), 'lyrics') && !str_contains(strtolower($q), 'audio')) {
                $enhancedQueries[] = $q . ' lyrics';
            }
        }
        $queries = array_unique($enhancedQueries);

        $seen = [];
        $candidates = [];

        // Parallel fetch
        $urls = [];
        foreach ($queries as $q) {
            $urls[$q] = 'https://www.youtube.com/results?search_query=' . urlencode($q);
        }
        
        $responses = $this->multiFetchUrl($urls);

        foreach ($queries as $q) {
            $url = $urls[$q];
            if (empty($responses[$url])) continue;
            
            $html = $responses[$url];
            $data = $this->extractInitialData($html);
            if (!$data) continue;
            
            $videos = $this->extractVideoRenderers($data);
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
                
                $key = mb_strtolower(trim(($item['title'] ?? '') . '|' . ($item['artist'] ?? '')));
                if ($key === '' || isset($seen[$key])) continue;
                
                $seen[$key] = true;
                $item['score'] = $this->scoreTrack($item, $preferredArtists);
                $candidates[] = $item;
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

    private function multiFetchUrl(array $urls): array
    {
        $mh = curl_multi_init();
        $handles = [];
        $results = [];

        $headers = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9,en;q=0.8'
        ];

        foreach ($urls as $key => $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Strict 5s timeout
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            
            curl_multi_add_handle($mh, $ch);
            $handles[$url] = $ch;
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        foreach ($handles as $url => $ch) {
            $results[$url] = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        
        curl_multi_close($mh);
        return $results;
    }


    // searchOnce and httpGet removed


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
            'reaction', 'remix', 'sped up', 'slowed', 'nightcore',
            'live', 'tribute', 'fan made', 'mashup', 'mix', 'compilation',
            'teaser', 'snippet', 'preview',
            'tutorial', 'lesson',
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
        
        // Prioritize Lyrics/Audio for better embedding support
        if (str_contains($title, 'lyrics') || str_contains($title, 'letra')) $score += 40;
        if (str_contains($title, 'official audio') || str_contains($title, 'audio oficial')) $score += 30;
        
        // Give some points to official video but less than lyrics to avoid embedding blocks
        if (str_contains($title, 'official video') || str_contains($title, 'video oficial')) $score += 10;
        
        if (str_contains($artist, 'vevo')) $score += 5; 
        if (str_contains($artist, 'topic')) $score += 5;
        
        if (str_contains($artist, 'official') || str_contains($artist, 'records') || str_contains($artist, 'music')) $score += 5;
        if (strlen($artist) > 0) $score += 1;
        
        // Preferred mainstream artists boost
        foreach ($preferredArtists as $pa) {
            if ($pa && (str_contains($artist, mb_strtolower($pa)) || str_contains($title, mb_strtolower($pa)))) {
                $score += 15; // Huge boost for requested artists
                break;
            }
        }
        
        // Penalize unwanted formats
        if (str_contains($title, 'top ') || str_contains($title, 'playlist') || str_contains($title, 'full album')) $score -= 20;
        if (str_contains($title, 'instrumental') || str_contains($title, 'karaoke')) $score -= 20;
        if (str_contains($title, 'reaction') || str_contains($title, 'review')) $score -= 50;
        if (str_contains($title, 'behind the scenes') || str_contains($title, 'making of')) $score -= 20;
        
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
