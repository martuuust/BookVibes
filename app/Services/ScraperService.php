<?php

namespace App\Services;

class ScraperService
{
    public function scrapeBook(string $query): ?array
    {
        // 1. Try Google Books API (Public)
        $googleData = $this->scrapeGoogleBooks($query);
        if ($googleData) {
            return $googleData;
        }

        // 2. Try OpenLibrary API
        $olData = $this->scrapeOpenLibrary($query);
        if ($olData) {
            return $olData;
        }

        // 3. No official synopsis found: return null to avoid inventing information
        return null;
    }

    private function scrapeGoogleBooks(string $query): ?array
    {
        try {
            $tries = [
                'https://www.googleapis.com/books/v1/volumes?q=' . urlencode($query) . '&printType=books&maxResults=10&langRestrict=es',
                'https://www.googleapis.com/books/v1/volumes?q=' . urlencode($query) . '&printType=books&maxResults=10&langRestrict=en',
                'https://www.googleapis.com/books/v1/volumes?q=' . urlencode($query) . '&printType=books&maxResults=10'
            ];
            foreach ($tries as $url) {
                $json = $this->fetchUrl($url);
                if (!$json) continue;
                $data = json_decode($json, true);
                $items = $data['items'] ?? [];
                foreach ($items as $it) {
                    $item = $it['volumeInfo'] ?? [];
                    $desc = $item['description'] ?? null;
                    if (!$desc || strlen(strip_tags($desc)) < 30) continue;
                    $authors = $item['authors'] ?? [];
                    $images = $item['imageLinks'] ?? [];
                    $bestImg = $this->pickBestGoogleImage($images, $item);
                    return [
                        'title' => $item['title'] ?? $query,
                        'author' => $authors[0] ?? 'Autor Desconocido',
                        'synopsis' => strip_tags($desc),
                        'genre' => ($item['categories'][0] ?? '') ?: 'General',
                        'keywords' => $item['categories'] ?? [],
                        'image_url' => $bestImg,
                        'full_data' => [
                            'source' => 'GoogleBooks',
                            'raw' => $item
                        ]
                    ];
                }
            }
        } catch (\Exception $e) {
            // log error
        }
        return null;
    }

    private function scrapeOpenLibrary(string $query): ?array
    {
        try {
            // Search for the book key
            $searchUrl = "https://openlibrary.org/search.json?q=" . urlencode($query);
            $searchJson = $this->fetchUrl($searchUrl);
            $searchData = json_decode($searchJson, true);

            $docs = $searchData['docs'] ?? [];
            foreach ($docs as $doc) {
                if (empty($doc['key'])) continue;
                $key = $doc['key']; // e.g. /works/OL123W
                // Fetch Work Details
                $workUrl = "https://openlibrary.org{$key}.json";
                $workJson = $this->fetchUrl($workUrl);
                $workData = json_decode($workJson, true);
                $desc = 'Descripción no disponible.';
                if (isset($workData['description'])) {
                    if (is_string($workData['description'])) {
                        $desc = $workData['description'];
                    } elseif (is_array($workData['description']) && isset($workData['description']['value'])) {
                        $desc = $workData['description']['value'];
                    }
                }
                if ($desc !== 'Descripción no disponible.' && strlen(strip_tags($desc)) >= 30) {
                    $imgKey = $doc['cover_i'] ?? null;
                    $imgUrl = $imgKey ? $this->pickBestOpenLibraryImage($imgKey) : '';
                    return [
                        'title' => $doc['title'],
                        'author' => $doc['author_name'][0] ?? 'Desconocido',
                        'synopsis' => $desc,
                        'genre' => $doc['subject'][0] ?? 'General',
                        'keywords' => $doc['subject'] ?? [],
                        'image_url' => $imgUrl,
                        'full_data' => [
                            'source' => 'OpenLibrary',
                            'raw' => $doc,
                            'work_key' => $key
                        ]
                    ];
                }
            }
        } catch (\Exception $e) {
            // log error
        }
        return null;
    }

    private function fetchUrl($url)
    {
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: BookVibes/1.0 (Student Project)\r\n",
                "timeout" => 10
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
                "allow_self_signed" => true
            ]
        ];
        $context = stream_context_create($opts);
        return @file_get_contents($url, false, $context);
    }

    private function pickBestGoogleImage(array $images, array $item): string
    {
        $order = ['extraLarge','large','medium','thumbnail','small','smallThumbnail'];
        $chosen = '';
        foreach ($order as $k) {
            if (!empty($images[$k])) { $chosen = $images[$k]; break; }
        }
        if (!$chosen) {
            $isbn = '';
            if (!empty($item['industryIdentifiers']) && is_array($item['industryIdentifiers'])) {
                foreach ($item['industryIdentifiers'] as $id) {
                    if (!empty($id['identifier'])) {
                        $isbn = preg_replace('/[^0-9Xx]/', '', $id['identifier']);
                        if (strlen($isbn) >= 10) break;
                    }
                }
            }
            if ($isbn) {
                return "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg?default=false";
            }
            return '';
        }
        if (strpos($chosen, 'http://') === 0) $chosen = 'https://' . substr($chosen, 7);
        if (strpos($chosen, 'zoom=') !== false) {
            $chosen = preg_replace('/zoom=\\d+/', 'zoom=3', $chosen);
        } else {
            $sep = strpos($chosen, '?') !== false ? '&' : '?';
            $chosen .= $sep . 'zoom=3';
        }
        return $chosen;
    }

    private function pickBestOpenLibraryImage($coverId): string
    {
        return "https://covers.openlibrary.org/b/id/{$coverId}-L.jpg";
    }
}
