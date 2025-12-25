<?php

namespace App\Services;

class CharacterGenerator
{
    private $timeboxStart = 0.0;
    public function getCharacterList(array $bookData): array
    {
        // echo "[DEBUG] getCharacterList called for title: " . ($bookData['title'] ?? 'unknown') . "\n";
        set_time_limit(300);
        $title = $bookData['title'] ?? '';
        if (!$title) return [];
        
        $this->timeboxStart = microtime(true);
        // We can cache the list of names too if we want, but let's keep it fresh for now or use a different cache key
        $cacheKey = "char_list_" . $this->cacheKey($title, $bookData['author'] ?? '');
        $cached = $this->cacheGet($cacheKey);
        if ($cached) {
            // echo "[DEBUG] Returning cached data\n";
            return $cached;
        }

        // echo "[DEBUG] Extracting from Wikipedia...\n";
        $characters = $this->extractCharactersFromWikipedia($title, $bookData['author'] ?? '');
        // echo "[DEBUG] Wikipedia found " . count($characters) . " characters.\n";

        if (count($characters) < 5) {
            // echo "[DEBUG] Extracting from External Sources...\n";
            $external = $this->extractCharactersFromExternalSources($title, $bookData['author'] ?? '');
            $characters = $this->mergeCharacterLists($characters, $external);
            if (count($characters) < 3) {
                $wd = $this->extractCharactersFromWikidata($title);
                $characters = $this->mergeCharacterLists($characters, $wd);
            }
        }
        
        if (empty($characters)) return [];

        $characters = $this->normalizeCharactersList($characters);
        
        // We fetch details to have good descriptions for the selection
        $detailsWiki = $this->wikiFetchDetailsBatch($characters);
        $detailsPages = $this->genericFetchDetailsBatch($characters, ['fandom','wikia','sparknotes','litcharts','shmoop','bookanalysis','supersummary','cliffsnotes','enotes','penguinrandomhouse','harpercollins','macmillan']);
        
        $enriched = [];
        foreach ($characters as $c) {
            $desc = $detailsWiki[$c['name']] ?? ($detailsPages[$c['name']] ?? ($c['description'] ?? ''));
            $enriched[] = [
                'name' => $c['name'],
                'description' => $desc,
                'url' => $c['url'] ?? '',
                'lang' => $c['lang'] ?? '',
                'source_count' => $c['source_count'] ?? 1
            ];
        }
        
        // Filter but keep a bit more than just "Main" ones so user has choice
        // $main = $this->filterMainCharacters($enriched);
        // if (empty($main)) $main = $enriched;
        
        // Let's just return the enriched list, sorted by relevance/source_count
        usort($enriched, function($a, $b) {
            return $b['source_count'] <=> $a['source_count'];
        });

        // Cache the list
        $this->cacheSet($cacheKey, $enriched);
        
        return $enriched;
    }

    public function generateSingleCharacter(string $bookTitle, array $characterData): array
    {
        $name = $characterData['name'];
        $desc = $characterData['description'] ?? '';
        $traits = $this->extractTraits($desc);
        $prompt = $this->buildPrompt($bookTitle, $name, $traits);
        $imageUrl = "https://image.pollinations.ai/prompt/" . urlencode($prompt);
        
        return [
            'name' => $name,
            'description' => $desc,
            'traits' => $traits,
            'image_url' => $imageUrl
        ];
    }

    public function generateCharacters(array $bookData): array
    {
        set_time_limit(300); // Aumentar el tiempo máximo de ejecución a 5 minutos
        $title = $bookData['title'] ?? '';
        if (!$title) return [];
        $this->timeboxStart = microtime(true);
        $cacheKey = $this->cacheKey($title, $bookData['author'] ?? '');
        $cached = $this->cacheGet($cacheKey);
        if ($cached) {
            return $cached;
        }
        $characters = $this->extractCharactersFromWikipedia($title, $bookData['author'] ?? '');
        if (count($characters) < 3) {
            $external = $this->extractCharactersFromExternalSources($title, $bookData['author'] ?? '');
            $characters = $this->mergeCharacterLists($characters, $external);
            if (count($characters) < 3) {
                $wd = $this->extractCharactersFromWikidata($title);
                $characters = $this->mergeCharacterLists($characters, $wd);
            }
        }
        if (empty($characters)) {
            return [];
        }
        $characters = $this->normalizeCharactersList($characters);
        $detailsWiki = $this->wikiFetchDetailsBatch($characters);
        $detailsPages = $this->genericFetchDetailsBatch($characters, ['fandom','wikia','sparknotes','litcharts','shmoop','bookanalysis','supersummary','cliffsnotes','enotes','penguinrandomhouse','harpercollins','macmillan']);
        $enriched = [];
        foreach ($characters as $c) {
            $desc = $detailsWiki[$c['name']] ?? ($detailsPages[$c['name']] ?? ($c['description'] ?? ''));
            $enriched[] = [
                'name' => $c['name'],
                'description' => $desc,
                'url' => $c['url'] ?? '',
                'lang' => $c['lang'] ?? '',
                'source_count' => $c['source_count'] ?? 1
            ];
        }
        $main = $this->filterMainCharacters($enriched);
        if (empty($main)) $main = $enriched;
        $result = [];
        foreach ($main as $c) {
            $traits = $this->extractTraits($c['description']);
            $prompt = $this->buildPrompt($title, $c['name'], $traits);
            $imageUrl = "https://image.pollinations.ai/prompt/" . urlencode($prompt);
            $result[] = [
                'name' => $c['name'],
                'description' => $c['description'],
                'traits' => $traits,
                'image_url' => $imageUrl
            ];
        }
        $validForCache = true;
        foreach ($result as $rc) {
            $n = trim($rc['name'] ?? '');
            if ($n === '' || $n === 'Personaje principal') { $validForCache = false; break; }
        }
        if ($validForCache && !empty($result)) {
            $this->cacheSet($cacheKey, $result);
        }
        return $result;
    }

    private function extractCharactersFromWikipedia(string $title, string $author = ''): array
    {
        $page = $this->wikiFindPage($title, 'es', $author);
        if (!$page) {
             $page = $this->wikiFindPage($title, 'en', $author);
        }
        if (!$page) {
             return [];
        }
        $chars = $this->wikiGetCharactersSection($page['title'], $page['lang']);
        if (!empty($chars)) return $chars;
        
        // Try dedicated list pages if main article has no character section
        $listPage = $this->wikiFindCharactersListPage($title, $page['lang']);
        if ($listPage) {
            $chars = $this->wikiExtractCharactersFromPageHTML($listPage['title'], $listPage['lang']);
        }
        return $chars;
    }

    private function wikiFindPage(string $title, string $lang, string $author = ''): ?array
    {
        $queries = [];
        if ($author) {
            // High confidence queries
            $queries[] = $title . " " . $author;
            if ($lang === 'es') {
                $queries[] = $title . " " . $author . " novela";
            } else {
                $queries[] = $title . " " . $author . " novel";
            }
        }
        
        // Medium confidence
        if ($lang === 'es') {
             $queries[] = $title . " (novela)";
             $queries[] = $title . " (libro)";
        } else {
             $queries[] = $title . " (novel)";
             $queries[] = $title . " (book)";
        }
        
        // Low confidence (just title)
        $queries[] = $title;

        foreach ($queries as $q) {
            $url = "https://{$lang}.wikipedia.org/w/api.php?action=query&list=search&srsearch=" . urlencode($q) . "&format=json&srlimit=5";
            $json = $this->fetchUrl($url);
            if (!$json) continue;
            
            $data = json_decode($json, true);
            if (!empty($data['query']['search'])) {
                foreach ($data['query']['search'] as $item) {
                    $t = $item['title'];
                    
                    // Skip if title is just the author's name
                    if ($author && (stripos($t, $author) !== false) && strlen($t) < strlen($author) + 5) {
                        continue;
                    }

                    // Check for specific keywords indicating a book/novel
                    if (preg_match('/\(novel|\(book|\(libro|\(novela/i', $t)) {
                        // verify similarity to avoid false positives (e.g. searching "Shadow" getting "Chocolat (novela)")
                        $cleanT = preg_replace('/\(novel|\(book|\(libro|\(novela|\)/i', '', $t);
                        $cleanT = trim($cleanT);
                        
                        similar_text(mb_strtolower($title), mb_strtolower($cleanT), $sim);
                         if ($sim > 70 || stripos($cleanT, $title) !== false || stripos($title, $cleanT) !== false) {
                              return ['title' => $t, 'lang' => $lang];
                         }
                    }
                    
                    // If the result title contains the book title
                    if (stripos($t, $title) !== false) {
                        return ['title' => $t, 'lang' => $lang];
                    }
                    
                    // Fuzzy match (similarity > 80%)
                    similar_text(mb_strtolower($title), mb_strtolower($t), $percent);
                    if ($percent > 80) {
                         return ['title' => $t, 'lang' => $lang];
                    }
                }
                
                // If query was specific (e.g. "Title (novel)"), and we haven't found a match yet, 
                // we might want to return the top result IF it's not the author AND it looks similar
                if (strpos($q, '(') !== false) {
                     $top = $data['query']['search'][0]['title'];
                     // Clean both title and top result for comparison
                     $cleanTop = preg_replace('/\(novel|\(book|\(libro|\(novela|\)/i', '', $top);
                     $cleanTop = trim($cleanTop);
                     
                     if (!$author || stripos($top, $author) === false) {
                         // Check similarity
                         similar_text(mb_strtolower($title), mb_strtolower($cleanTop), $sim);
                         if ($sim > 70 || stripos($cleanTop, $title) !== false || stripos($title, $cleanTop) !== false) {
                             return ['title' => $top, 'lang' => $lang];
                         }
                     }
                }
            }
        }
        return null;
    }

    private function wikiFindCharactersListPage(string $title, string $lang): ?array
    {
        $queries = [
            "lista de personajes " . $title,
            $title . " personajes",
            "List of " . $title . " characters",
            $title . " characters"
        ];
        foreach ($queries as $q) {
            $url = "https://{$lang}.wikipedia.org/w/api.php?action=query&list=search&srsearch=" . urlencode($q) . "&format=json";
            $json = $this->fetchUrl($url);
            if (!$json) continue;
            $data = json_decode($json, true);
            if (!empty($data['query']['search'][0]['title'])) {
                return ['title' => $data['query']['search'][0]['title'], 'lang' => $lang];
            }
        }
        return null;
    }

    private function extractCharactersFromExternalSources(string $title, string $author): array
    {
        // echo "[DEBUG] extractCharactersFromExternalSources called.\n";
        $out = [];
        
        // 1. Try direct Fandom discovery first (very fast)
        // echo "[DEBUG] Trying direct Fandom discovery...\n";
        $fandomChars = $this->extractCharactersFromDirectSources($title);
        $out = $this->mergeCharacterLists($out, $fandomChars);
        // echo "[DEBUG] Direct Fandom found " . count($out) . " characters.\n";
        if (count($out) > 15) return $out;

        // 2. Parallel Search Strategy
        // Instead of sequential searches, we fire a few targeted searches in parallel or one broad search
        $searchQueries = [
            "\"{$title}\" characters site:fandom.com OR site:wikia.org",
            "\"{$title}\" characters site:goodreads.com OR site:lecturalia.com",
            "\"{$title}\" characters site:litcharts.com OR site:sparknotes.com",
            "\"{$title}\" characters site:bookcompanion.com OR site:personality-database.com"
        ];
        
        // We'll just do one broad search on Bing/DDG to save time
        $combinedQuery = "\"{$title}\" characters (site:fandom.com OR site:goodreads.com OR site:lecturalia.com OR site:litcharts.com OR site:sparknotes.com OR site:bookcompanion.com OR site:personality-database.com)";
        
        // echo "[DEBUG] Searching DuckDuckGo with query: $combinedQuery\n";
        $urls = $this->searchDuckDuckGo($combinedQuery);
        // echo "[DEBUG] DuckDuckGo returned " . count($urls) . " URLs.\n";
        
        // Fallback: Looser search if strict search fails
        if (empty($urls)) {
            $combinedQueryLoose = "{$title} characters (site:fandom.com OR site:goodreads.com OR site:lecturalia.com OR site:litcharts.com OR site:sparknotes.com OR site:bookcompanion.com OR site:personality-database.com)";
            $urls = $this->searchDuckDuckGo($combinedQueryLoose);
        }

        // Fallback 2: Title + Author if still empty
        if (empty($urls) && !empty($author)) {
             $q = "{$title} {$author} characters";
             $urls = $this->searchDuckDuckGo($q);
        }
        
        // Filter and prioritize URLs
        $targetUrls = [];
        $domains = [];
        foreach ($urls as $u) {
            $host = parse_url($u, PHP_URL_HOST);
            // Limit to 1 URL per domain to maximize diversity
            if (isset($domains[$host])) continue;
            
            if (strpos($u, 'lecturalia.com') !== false ||
                strpos($u, 'bookcompanion.com') !== false ||
                strpos($u, 'goodreads.com') !== false ||
                strpos($u, 'litcharts.com') !== false ||
                strpos($u, 'sparknotes.com') !== false ||
                strpos($u, 'personality-database.com') !== false ||
                strpos($u, 'fandom.com') !== false) {
                
                $targetUrls[] = $u;
                $domains[$host] = true;
            }
            if (count($targetUrls) >= 5) break; // Limit to top 5 sources
        }

        // 3. Parallel Fetch of Target Pages
        if (!empty($targetUrls)) {
            $pagesContent = $this->multiFetchUrl($targetUrls);
            
            foreach ($pagesContent as $url => $html) {
                if (!$html) continue;
                $list = [];
                
                if (strpos($url, 'lecturalia.com') !== false) {
                    $list = $this->parseLecturalia($html);
                } elseif (strpos($url, 'bookcompanion.com') !== false) {
                    $list = $this->parseBookCompanion($html);
                } elseif (strpos($url, 'personality-database.com') !== false) {
                    $list = $this->parsePersonalityDatabase($html);
                } elseif (strpos($url, 'sparknotes.com') !== false) {
                    $list = $this->parseSparkNotes($url, $html);
                } elseif (strpos($url, 'litcharts.com') !== false) {
                    $list = $this->parseLitCharts($url, $html);
                } elseif (strpos($url, 'fandom') !== false || strpos($url, 'wikia') !== false) {
                    $list = $this->parseFandomCharacters($url, $html); // Ensure this method accepts HTML arg
                } elseif (strpos($url, 'goodreads.com') !== false) {
                    $list = $this->parseGenericCharacterListPage($url, $html);
                } else {
                    $list = $this->parseGenericCharacterListPage($url, $html);
                }
                
                $out = $this->mergeCharacterLists($out, $list);
            }
        }

        return $out;
    }

    private function multiFetchUrl(array $urls): array {
        // echo "[DEBUG] multiFetchUrl called with " . count($urls) . " URLs\n";
        $mh = curl_multi_init();
        $handles = [];
        $results = [];

        foreach ($urls as $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8); // Increased to 8s
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Language: es-ES,es;q=0.9,en;q=0.8"
            ]);
            
            curl_multi_add_handle($mh, $ch);
            $handles[(int)$ch] = ['ch' => $ch, 'url' => $url];
        }

        $running = null;
        $start = microtime(true);
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running > 0) {
                curl_multi_select($mh, 0.1);
            }
            if (microtime(true) - $start > 9.0) break; // Global safety timeout
        } while ($running > 0 && $status === CURLM_OK);

        foreach ($handles as $h) {
            $ch = $h['ch'];
            $url = $h['url'];
            $content = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            
            // echo "[Trace] $url -> Code: $httpCode, Len: " . strlen($content ?? '') . ", Err: $err\n";
            // if (strlen($content ?? '') < 5000) {
            //    echo "[Trace] Content Preview: " . substr($content ?? '', 0, 200) . "...\n";
            // }
            
            if ($httpCode >= 200 && $httpCode < 400 && !empty($content)) {
                $results[$url] = $content;
            }
            
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        
        curl_multi_close($mh);
        return $results;
    }

    private function parseLecturalia(string $html): array {
        $out = [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        
        // Strategy: Look for strong tags inside .personajes
        $nodes = $xpath->query('//div[@class="personajes"]//strong');
        foreach ($nodes as $node) {
            $name = trim($node->textContent);
            $desc = '';
            
            // Try to get text after the strong tag
            $next = $node->nextSibling;
            while ($next) {
                if ($next->nodeType === XML_TEXT_NODE) {
                    $desc .= $next->textContent;
                } elseif ($next->nodeName === 'br') {
                     // continue or break depending on structure? Let's just append.
                } else {
                     $desc .= $next->textContent;
                }
                $next = $next->nextSibling;
                if ($next && ($next->nodeName === 'strong' || $next->nodeName === 'h2' || $next->nodeName === 'h3')) break; 
                if (strlen($desc) > 300) break; 
            }
            
            $desc = trim($desc, " :-\t\n\r");
            
            if (strlen($name) > 2 && $this->isValidCharacterName($name)) {
                $out[] = ['name' => $name, 'description' => $desc, 'url' => '', 'lang' => 'es'];
            }
        }
        
        if (empty($out)) {
             // Fallback to h3 links or just links in main content
             $nodes = $xpath->query('//div[@id="content"]//a | //h3/a');
             foreach ($nodes as $node) {
                 $name = trim($node->textContent);
                 // Heuristic: Character names usually 2+ words, capitalized
                 if ($this->isValidCharacterName($name) && strpos($name, ' ') !== false) {
                     $out[] = ['name' => $name, 'description' => '', 'url' => '', 'lang' => 'es'];
                 }
             }
        }
        
        return $out;
    }

    private function parseBookCompanion(string $html): array {
        $out = [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        
        // Look for character lists in tables or bold items
        // Strategy: h3 or strong, followed by p or text
        $nodes = $xpath->query('//div[contains(@class, "character")]//h3 | //div[contains(@class, "character")]//strong | //div[@id="content"]//ul/li/strong');
        
        foreach ($nodes as $node) {
            $name = trim($node->textContent);
            $desc = '';
            
            // If it's H3, look for next P
            if ($node->nodeName === 'h3') {
                $next = $node->nextSibling;
                while ($next && $next->nodeName !== 'p' && $next->nodeName !== 'h3') {
                    $next = $next->nextSibling;
                }
                if ($next && $next->nodeName === 'p') {
                    $desc = trim($next->textContent);
                }
            } else {
                 // Inline description (strong inside li or p)
                 // Get parent text and remove name
                 $parent = $node->parentNode;
                 $desc = trim(str_replace($name, '', $parent->textContent));
                 $desc = trim($desc, " :-\t\n\r");
            }
            
            if (strlen($name) > 2 && $this->isValidCharacterName($name)) {
                $out[] = ['name' => $name, 'description' => $desc, 'url' => '', 'lang' => 'en'];
            }
        }
        return $out;
    }

    private function parsePersonalityDatabase(string $html): array {
        // PDB is often JS rendered, but sometimes we catch titles in meta or json-ld
        $out = [];
        
        // 1. JSON-LD Strategy
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        
        $scripts = $xpath->query('//script[@type="application/ld+json"]');
        foreach ($scripts as $s) {
            $json = json_decode($s->textContent, true);
            if (!$json) continue;
            
            // Check for ItemList
            if (isset($json['@type']) && $json['@type'] === 'ItemList' && isset($json['itemListElement'])) {
                foreach ($json['itemListElement'] as $item) {
                    $name = $item['name'] ?? ($item['item']['name'] ?? '');
                    if ($name && $this->isValidCharacterName($name)) {
                         $out[] = ['name' => $name, 'description' => '', 'url' => $item['url'] ?? '', 'lang' => 'en'];
                    }
                }
            }
        }
        
        if (!empty($out)) return $out;

        // 2. Regex Fallback
        if (preg_match_all('/"name":"([^"]+)"/i', $html, $matches)) {
            foreach ($matches[1] as $m) {
                if (strlen($m) > 2 && strlen($m) < 40 && !strpos($m, 'http') && $this->isValidCharacterName($m)) {
                    $out[] = ['name' => $m, 'description' => '', 'url' => '', 'lang' => 'en'];
                }
            }
        }
        
        return array_slice($out, 0, 15);
    }
    
    private function parseSparkNotes(string $url, string $html = null): array {
        if (!$html) $html = $this->fetchUrl($url);
        if (!$html) return [];
        
        $out = [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        
        // SparkNotes character list
        // Strategy 1: .character-card or .character-list-item
        $cards = $xpath->query('//div[contains(@class, "character-card")] | //div[contains(@class, "character-list-item")]');
        if ($cards->length > 0) {
            foreach ($cards as $card) {
                $nameNode = $xpath->query('.//h3', $card)->item(0);
                if (!$nameNode) $nameNode = $xpath->query('.//*[contains(@class, "character-name")]', $card)->item(0);
                
                if ($nameNode) {
                    $name = trim($nameNode->textContent);
                    $desc = '';
                    $descNode = $xpath->query('.//*[contains(@class, "character-description")]', $card)->item(0);
                    if ($descNode) {
                        $desc = trim($descNode->textContent);
                    } else {
                        // Try p tag inside card
                        $p = $xpath->query('.//p', $card)->item(0);
                        if ($p) $desc = trim($p->textContent);
                    }
                    
                    if ($this->isValidCharacterName($name)) {
                        $out[] = ['name' => $name, 'description' => $desc, 'url' => $url, 'lang' => 'en'];
                    }
                }
            }
        }
        
        if (!empty($out)) return $out;

        // Strategy 2: h3 followed by p
        $nodes = $xpath->query('//div[contains(@class, "character-list")]//h3');
        foreach ($nodes as $node) {
            $name = trim($node->textContent);
            $desc = '';
            
            $next = $node->nextSibling;
            while ($next && $next->nodeName !== 'p' && $next->nodeName !== 'h3') {
                $next = $next->nextSibling;
            }
            if ($next && $next->nodeName === 'p') {
                $desc = trim($next->textContent);
            }
            
            if (strlen($name) > 2 && $this->isValidCharacterName($name)) {
                $out[] = ['name' => $name, 'description' => $desc, 'url' => $url, 'lang' => 'en'];
            }
        }
        
        // Fallback: Just links
        if (empty($out)) {
             $nodes = $xpath->query('//a[contains(@class, "character-name")]');
             foreach ($nodes as $node) {
                 $name = trim($node->textContent);
                 if (strlen($name) > 2 && $this->isValidCharacterName($name)) {
                     $out[] = ['name' => $name, 'description' => '', 'url' => $url, 'lang' => 'en'];
                 }
             }
        }
        
        return $out;
    }

    private function parseLitCharts(string $url, string $html = null): array {
        if (!$html) $html = $this->fetchUrl($url);
        if (!$html) return [];

        $out = [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        
        // LitCharts character names
        // Strategy: .character-entry or similar container
        $entries = $xpath->query('//div[contains(@class, "character-entry")] | //div[contains(@class, "character-list-entry")]');
        
        if ($entries->length > 0) {
            foreach ($entries as $entry) {
                $nameNode = $xpath->query('.//*[contains(@class, "character-name")]', $entry)->item(0);
                if ($nameNode) {
                    $name = trim($nameNode->textContent);
                    $desc = '';
                    $descNode = $xpath->query('.//*[contains(@class, "character-description")]', $entry)->item(0);
                    if ($descNode) {
                         $desc = trim($descNode->textContent);
                    }
                    
                    if ($this->isValidCharacterName($name)) {
                         $out[] = ['name' => $name, 'description' => $desc, 'url' => $url, 'lang' => 'en'];
                    }
                }
            }
        }
        
        if (!empty($out)) return $out;

        // Fallback
        $nodes = $xpath->query('//div[contains(@class, "character-name")] | //span[contains(@class, "character-name")]');
        foreach ($nodes as $node) {
            $name = trim($node->textContent);
            if (strlen($name) > 2 && $this->isValidCharacterName($name)) {
                $out[] = ['name' => $name, 'description' => '', 'url' => $url, 'lang' => 'en'];
            }
        }
        return $out;
    }

    private function extractCharactersFromDirectSources(string $title): array
    {
        $out = [];
        $slugs = [];
        $cleanTitle = strtolower(preg_replace('/[^a-zA-Z0-9 ]/', '', $title));
        $cleanTitleNoSpaces = str_replace(' ', '', $cleanTitle);
        $cleanTitleUnderscore = str_replace(' ', '_', $title); // Keep case for Wikipedia? No, usually Capitalized.
        // Wikipedia uses underscores and case sensitivity. "Harry Potter and the Philosopher's Stone"
        $wikiTitle = str_replace(' ', '_', ucwords($title));
        
        // 1. Basic: "Boys of Tommen" -> "boysoftommen"
        $slugs[] = $cleanTitleNoSpaces;
        
        // 2. Without articles: "The Hunger Games" -> "hungergames"
        $withoutArticles = str_replace(['the ', 'a ', 'an '], '', $cleanTitle . ' ');
        $withoutArticles = str_replace(' ', '', trim($withoutArticles));
        if ($withoutArticles !== $cleanTitleNoSpaces) {
            $slugs[] = $withoutArticles;
        }

        // 3. First two words: "Harry Potter and..." -> "harrypotter"
        $words = explode(' ', $cleanTitle);
        if (count($words) >= 2) {
            $slugs[] = $words[0] . $words[1];
        }
        
        // 4. First word only if it's unique enough (length > 5)
        if (strlen($words[0]) > 5) {
            $slugs[] = $words[0];
        }
        
        $slugs = array_unique($slugs);
        $candidateUrls = [];
        
        // Fandom URLs
        foreach ($slugs as $slug) {
            if (strlen($slug) < 3) continue;
            $candidateUrls[] = "https://{$slug}.fandom.com/wiki/Category:Characters";
            $candidateUrls[] = "https://{$slug}.fandom.com/wiki/Characters";
            $candidateUrls[] = "https://{$slug}.fandom.com/wiki/List_of_characters";
            $candidateUrls[] = "https://{$slug}.fandom.com/wiki/Category:Main_characters";
        }
        
        // Wikipedia URLs (English and Spanish)
         $candidateUrls[] = "https://en.wikipedia.org/wiki/" . $wikiTitle;
         $candidateUrls[] = "https://en.wikipedia.org/wiki/List_of_" . $wikiTitle . "_characters";
         $candidateUrls[] = "https://es.wikipedia.org/wiki/" . $wikiTitle;
         $candidateUrls[] = "https://es.wikipedia.org/wiki/Personajes_de_" . $wikiTitle;
         
         // Try "List of [First 2 Words] characters" for series (e.g. Harry Potter)
         if (count($words) >= 2 && strlen($words[0]) > 3 && strlen($words[1]) > 3) {
             $shortTitle = ucfirst($words[0]) . '_' . ucfirst($words[1]);
             $candidateUrls[] = "https://en.wikipedia.org/wiki/List_of_" . $shortTitle . "_characters";
             $candidateUrls[] = "https://es.wikipedia.org/wiki/Personajes_de_" . $shortTitle;
         }

         // Limit to reasonable number of requests
        $candidateUrls = array_slice(array_unique($candidateUrls), 0, 25);
        
        if (empty($candidateUrls)) return [];
        
        // Parallel fetch with strict timeout
        $responses = $this->multiFetchUrl($candidateUrls);
        
        foreach ($responses as $url => $html) {
             if (!$html) continue;
             
             $list = [];
              if (strpos($url, 'fandom.com') !== false) {
                  $list = $this->parseFandomCharacters($url, $html);
              } elseif (strpos($url, 'wikipedia.org') !== false) {
                  $list = $this->parseWikipediaCharacterList($url, $html);
              }
              
              if (!empty($list)) {
                 $out = $this->mergeCharacterLists($out, $list);
             }
        }
        
        return $out;
    }

    private function searchDuckDuckGo(string $query): array
    {
        // echo "[DEBUG] Searching DuckDuckGo with query: $query\n";
        
        // Try Lite version first (lighter, faster, less likely to hang)
        $urlLite = "https://lite.duckduckgo.com/lite/?q=" . urlencode($query);
        $htmlLite = $this->fetchUrl($urlLite, 5); // 5s timeout
        $out = [];
        
        if ($htmlLite) {
            // echo "[DEBUG] Parsing DDG Lite result (Len: " . strlen($htmlLite) . ")...\n";
            $out = $this->parseDuckDuckGoLite($htmlLite);
            // echo "[DEBUG] DDG Lite found " . count($out) . " URLs.\n";
        }
        
        // If Lite version failed or returned few results, try HTML version
        if (empty($out)) {
            // echo "[DEBUG] DDG Lite returned nothing, trying HTML version...\n";
            $url = "https://duckduckgo.com/html/?q=" . urlencode($query);
            $html = $this->fetchUrl($url, 5); // 5s timeout
            if ($html) {
                // echo "[DEBUG] Parsing DDG HTML result (Len: " . strlen($html) . ")...\n";
                $out = $this->parseDuckDuckGoHtml($html);
                // echo "[DEBUG] DDG HTML found " . count($out) . " URLs.\n";
            }
        }

        // If still empty, try Bing as last resort
        if (empty($out)) {
            // echo "[DEBUG] DDG failed, trying Bing...\n";
            $out = $this->searchBing($query);
        }

        return array_values(array_unique($out));
    }

    private function searchBing(string $query): array
    {
        $url = "https://www.bing.com/search?q=" . urlencode($query);
        $html = $this->fetchUrl($url);
        if (!$html) return [];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        
        // Bing main results are usually in <li class="b_algo"><h2><a href="...">
        // But to be safe, we grab all links inside h2 tags or just all links and filter
        $nodes = $xpath->query('//li[@class="b_algo"]//h2/a[@href]');
        // echo "[DEBUG] Bing XPath 1 found " . $nodes->length . " nodes.\n";
        $out = [];
        
        if ($nodes->length === 0) {
            // Fallback: grab all links and filter aggressively
            $nodes = $xpath->query('//a[@href]');
            // echo "[DEBUG] Bing XPath 2 (fallback) found " . $nodes->length . " nodes.\n";
        }

        // If DOM found very few nodes, try Regex as a safety net
        $regexLinks = [];
        if ($nodes->length < 10) {
            // echo "[DEBUG] DOM parsing yielded few results. Trying Regex...\n";
            if (preg_match_all('/href=["\'](https?:\/\/[^"\']+)["\']/i', $html, $matches)) {
                $regexLinks = $matches[1];
                // echo "[DEBUG] Regex found " . count($regexLinks) . " links.\n";
                // Debug first few links
                for ($i = 0; $i < min(5, count($regexLinks)); $i++) {
                    // echo "[DEBUG] Regex link example: " . $regexLinks[$i] . "\n";
                }
            }
        }

        $allLinks = [];
        foreach ($nodes as $a) {
            $allLinks[] = $a->getAttribute('href');
        }
        $allLinks = array_merge($allLinks, $regexLinks);
        $allLinks = array_unique($allLinks);

        foreach ($allLinks as $href) {
            // echo "[DEBUG] Checking link: $href\n"; // Commented out to reduce noise
            if (strpos($href, 'http') !== 0) continue;
            if (strpos($href, 'microsoft.com') !== false) continue;
            // if (strpos($href, 'bing.com') !== false) continue; // Allow Bing links for inspection
            
            // Prioritize target domains
            if (strpos($href, 'fandom.com') !== false || 
                strpos($href, 'wikipedia.org') !== false || 
                strpos($href, 'goodreads.com') !== false ||
                strpos($href, 'lecturalia.com') !== false ||
                strpos($href, 'litcharts.com') !== false ||
                strpos($href, 'sparknotes.com') !== false ||
                strpos($href, 'bookcompanion.com') !== false ||
                strpos($href, 'personality-database.com') !== false) {
                $out[] = $href;
            }
        }
        
        return array_slice(array_unique($out), 0, 8);
    }

    private function parseDuckDuckGoHtml($html): array {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//a[@href]');
        $out = [];
        foreach ($nodes as $a) {
            $href = $a->getAttribute('href');
            $out = array_merge($out, $this->extractUrlFromDDGLink($href));
            if (count($out) >= 8) break;
        }
        return $out;
    }

    private function parseDuckDuckGoLite($html): array {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        // Lite version usually has links in table rows
        $nodes = $xpath->query('//a[@href]');
        $out = [];
        foreach ($nodes as $a) {
            $href = $a->getAttribute('href');
            $out = array_merge($out, $this->extractUrlFromDDGLink($href));
            if (count($out) >= 8) break;
        }
        return $out;
    }

    private function extractUrlFromDDGLink($href): array {
        $out = [];
        // Handle DDG redirect links
        if (strpos($href, '/l/?') !== false || strpos($href, 'duckduckgo.com/l/?') !== false) {
            $queryPart = parse_url($href, PHP_URL_QUERY);
            if ($queryPart) {
                parse_str($queryPart, $params);
                if (!empty($params['uddg'])) {
                    $target = $params['uddg'];
                    if (strpos($target, 'http') === 0) {
                        $out[] = $target;
                    }
                }
            }
        }
        // Handle direct links
        elseif (strpos($href, 'http') === 0) {
            // Skip duckduckgo internal links and ads
            if (strpos($href, 'duckduckgo.com') !== false) return [];
            if (strpos($href, 'yandex') !== false) return [];
            $out[] = $href;
        }
        return $out;
    }

    private function extractCharactersFromWikidata(string $title): array
    {
        $itemIds = $this->wikidataFindItemIds($title);
        foreach ($itemIds as $itemId) {
            $chars = $this->wikidataGetCharacters($itemId);
            if (!empty($chars)) return $chars;
        }
        return [];
    }

    private function wikidataFindItemIds(string $title): array
    {
        $ids = [];
        // Try searching specifically for book/novel first
        $queries = [
            $title . " book",
            $title . " novel",
            $title . " novela",
            $title . " libro",
            $title
        ];
        
        foreach (['en', 'es'] as $lang) {
            // Only use the title for search to get broader matches, filter by description later if needed
            // Actually, querying with "novel" helps ranking.
            $q = $title; 
            $url = "https://www.wikidata.org/w/api.php?action=wbsearchentities&search=" . urlencode($q) . "&language={$lang}&format=json&limit=5";
            $json = $this->fetchUrl($url);
            if (!$json) continue;
            $data = json_decode($json, true);
            if (!empty($data['search'])) {
                foreach ($data['search'] as $item) {
                    $ids[] = $item['id'];
                }
            }
        }
        return array_unique($ids);
    }

    private function wikidataGetCharacters(string $itemId): array
    {
        $url = "https://www.wikidata.org/wiki/Special:EntityData/{$itemId}.json";
        $json = $this->fetchUrl($url);
        if (!$json) return [];
        $data = json_decode($json, true);
        $entity = $data['entities'][$itemId] ?? null;
        if (!$entity) return [];
        $claims = $entity['claims'] ?? [];
        $out = [];
        if (!empty($claims['P674'])) {
            foreach ($claims['P674'] as $claim) {
                $snak = $claim['mainsnak'] ?? null;
                $val = $snak['datavalue']['value']['id'] ?? null;
                if (!$val) continue;
                $info = $this->wikidataGetEntityInfo($val);
                if (!$info) continue;
                $name = $info['label'] ?? '';
                if (!$name || !$this->isValidCharacterName($name)) continue;
                $desc = $info['description'] ?? '';
                $out[] = ['name' => $name, 'description' => $desc, 'url' => "https://www.wikidata.org/wiki/{$val}", 'lang' => $info['lang'] ?? 'en'];
                if (count($out) >= 12) break;
            }
        }
        return $out;
    }

    private function wikidataGetEntityInfo(string $id): ?array
    {
        $url = "https://www.wikidata.org/w/api.php?action=wbgetentities&ids=" . urlencode($id) . "&props=labels|descriptions&languages=es|en&format=json";
        $json = $this->fetchUrl($url);
        if (!$json) return null;
        $data = json_decode($json, true);
        $ent = $data['entities'][$id] ?? null;
        if (!$ent) return null;
        $label = $ent['labels']['es']['value'] ?? ($ent['labels']['en']['value'] ?? '');
        $desc = $ent['descriptions']['es']['value'] ?? ($ent['descriptions']['en']['value'] ?? '');
        $lang = $ent['labels']['es'] ? 'es' : 'en';
        return ['label' => $label, 'description' => $desc, 'lang' => $lang];
    }

    private function parseFandomCharacters(string $url, string $html = null): array
    {
        if (!$html) $html = $this->fetchUrl($url);
        if (!$html) return [];
        
        $out = [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        
        // Fandom usually has lists in <ul> or tables, often under "Characters" section
        // Or specific category pages with class "category-page__member-link"
        
        // 1. Category page links
        $nodes = $xpath->query('//a[contains(@class, "category-page__member-link")]');
        foreach ($nodes as $node) {
            $name = trim($node->textContent);
            $href = $node->getAttribute('href');
            if ($name && $this->isValidCharacterName($name)) {
                 $fullUrl = (strpos($href, 'http') === 0) ? $href : parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . $href;
                 $out[] = ['name' => $name, 'description' => '', 'url' => $fullUrl, 'lang' => 'en'];
            }
        }
        
        if (!empty($out)) return $out;

        // 2. Standard list page
        // Look for <b>Name</b> or links inside lists
        $nodes = $xpath->query('//div[contains(@class, "mw-parser-output")]//ul/li/b | //div[contains(@class, "mw-parser-output")]//ul/li/a');
        foreach ($nodes as $node) {
             $name = trim($node->textContent);
             if ($name && $this->isValidCharacterName($name) && strlen($name) > 2) {
                 $href = ($node->nodeName === 'a') ? $node->getAttribute('href') : '';
                 $fullUrl = ($href && strpos($href, 'http') !== 0) ? parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . $href : $href;
                 $out[] = ['name' => $name, 'description' => '', 'url' => $fullUrl, 'lang' => 'en'];
             }
        }
        
        return array_slice($out, 0, 20);
    }

    private function parseWikipediaCharacterList(string $url, string $html = null): array
    {
        if (!$html) $html = $this->fetchUrl($url);
        if (!$html) return [];
        
        $out = [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        // Strategy 1: Look for "Characters" section or similar lists
        // Typically: <ul><li><b>Name</b>: Description...</li></ul> or <ul><li><a href="...">Name</a>: ...</li></ul>
        $nodes = $xpath->query('//div[contains(@class,"mw-parser-output")]//ul/li');
        
        foreach ($nodes as $node) {
            $name = '';
            $desc = '';
            
            // Check for bold tag at start
            $bold = $xpath->query('.//b', $node)->item(0);
            if ($bold) {
                $name = trim($bold->textContent);
                $desc = trim(str_replace($name, '', $node->textContent));
                // Remove colon if present
                $desc = ltrim($desc, ":- \t\n\r\0\x0B");
            } else {
                // Check for anchor tag at start
                $anchor = $xpath->query('.//a', $node)->item(0);
                if ($anchor) {
                    $name = trim($anchor->textContent);
                    $desc = trim(str_replace($name, '', $node->textContent));
                    $desc = ltrim($desc, ":- \t\n\r\0\x0B");
                }
            }
            
            // Validate name
            if ($name && $this->isValidCharacterName($name) && strlen($name) > 2) {
                // If description is too short, maybe it's just a link
                $out[] = ['name' => $name, 'description' => $desc, 'url' => $url, 'lang' => 'en'];
            }
        }
        
        // If few results, fallback to generic parser
        if (count($out) < 3) {
            return $this->parseGenericCharacterListPage($url, $html);
        }
        
        return $out;
    }

    private function parseGenericCharacterListPage(string $url, string $html = null): array
    {
        if (!$html) $html = $this->fetchUrl($url);
        if (!$html) return [];
        $names = $this->parseNamesFromHtml($html);
        $out = [];
        foreach ($names as $name) {
            $out[] = ['name' => $name, 'description' => '', 'url' => $url, 'lang' => 'en'];
        }
        return $out;
    }

    private function parseNamesFromUrl(string $url): array
    {
        $html = $this->fetchUrl($url);
        if (!$html) return [];
        return $this->parseNamesFromHtml($html);
    }

    private function parseNamesFromHtml(string $html): array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $texts = [];
        foreach (['//p','//li','//h2','//h3'] as $q) {
            $nodes = $xpath->query($q);
            foreach ($nodes as $n) {
                $t = trim($n->textContent);
                if ($t) $texts[] = $t;
            }
        }
        $cands = [];
        foreach ($texts as $t) {
            preg_match_all('/\\b[A-ZÁÉÍÓÚÑÜ][\\p{L}\\-]+(?:\\s+y\\s+[A-ZÁÉÍÓÚÑÜ][\\p{L}\\-]+|\\s+[A-ZÁÉÍÓÚÑÜ][\\p{L}\\-]+){0,2}\\b/u', $t, $m);
            foreach ($m[0] as $name) {
                if ($this->isValidCharacterName($name)) {
                    $cands[] = trim($name);
                }
            }
        }
        $uniq = array_values(array_unique($cands));
        return array_slice($uniq, 0, 8);
    }

    private function mergeCharacterLists(array $a, array $b): array
    {
        $byName = [];
        $add = function($c) use (&$byName) {
            $key = $this->mbLower(trim($c['name']));
            if (!isset($byName[$key])) {
                $byName[$key] = $c;
                $byName[$key]['source_count'] = isset($c['source_count']) ? (int)$c['source_count'] : 1;
            } else {
                // Merge descriptions
                $d1 = $byName[$key]['description'] ?? '';
                $d2 = $c['description'] ?? '';
                $byName[$key]['description'] = $this->coherentMerge($d1, $d2);
                // Prefer URL if missing
                if (empty($byName[$key]['url']) && !empty($c['url'])) $byName[$key]['url'] = $c['url'];
                if (empty($byName[$key]['lang']) && !empty($c['lang'])) $byName[$key]['lang'] = $c['lang'];
                $byName[$key]['source_count'] = ($byName[$key]['source_count'] ?? 1) + 1;
            }
        };
        foreach ($a as $c) $add($c);
        foreach ($b as $c) $add($c);
        return array_values($byName);
    }

    private function coherentMerge(string $a, string $b): string
    {
        $a = trim($a); $b = trim($b);
        if (!$a) return $b;
        if (!$b) return $a;
        if ($a === $b) return $a;
        // Simple coherence: join sentences avoiding duplicates
        $sentences = array_unique(array_filter(preg_split('/[\\.\\!\\?]+\\s*/', $a . '. ' . $b)));
        $merged = implode('. ', array_slice($sentences, 0, 4));
        return $merged;
    }

    private function fallbackMinimalCharacters(array $bookData): array
    {
        return [];
    }

    private function filterMainCharacters(array $chars): array
    {
        $scores = [];
        foreach ($chars as $idx => $c) {
            $text = $this->mbLower($c['description'] ?? '');
            $roleWords = [
                'protagonist','main character','lead','hero','heroine','antagonist','villain',
                'protagonista','personaje principal','principal','héroe','heroína','antagonista','villano'
            ];
            $score = 0;
            foreach ($roleWords as $w) {
                if (strpos($text, $w) !== false) $score += 2;
            }
            $traits = $this->extractTraits($c['description'] ?? '');
            foreach (['protagonist','protagonista','hero','héroe','heroine','heroína','antagonist','antagonista','villain','villano'] as $t) {
                if (in_array($t, $traits, true)) $score += 2;
            }
            if (!empty($c['url'])) $score += 1;
            $score += (int)($c['source_count'] ?? 1);
            $scores[$idx] = $score;
        }
        arsort($scores);
        $sorted = [];
        foreach ($scores as $i => $s) $sorted[] = $chars[$i];
        $top = [];
        $max = min(8, count($sorted));
        for ($i = 0; $i < $max; $i++) {
            $top[] = $sorted[$i];
        }
        return $top;
    }

    private function normalizeCharactersList(array $chars): array
    {
        // 1. Exact Match Deduplication
        $map = [];
        foreach ($chars as $c) {
            $name = isset($c['name']) ? trim($c['name']) : '';
            if ($name === '' || !$this->isValidCharacterName($name)) continue;
            
            // Cleanup common prefixes
            $nameClean = preg_replace('/^(Characters in|List of|Personajes de|Lista de)\s+/i', '', $name);
            $nameClean = trim($nameClean, " \t\n\r\0\x0B-–—:");

            $key = $this->mbLower($nameClean);
            if (!isset($map[$key])) {
                $map[$key] = [
                    'name' => $nameClean,
                    'description' => $c['description'] ?? '',
                    'url' => $c['url'] ?? '',
                    'lang' => $c['lang'] ?? '',
                    'source_count' => isset($c['source_count']) ? (int)$c['source_count'] : 1
                ];
            } else {
                $map[$key]['description'] = $this->coherentMerge($map[$key]['description'] ?? '', $c['description'] ?? '');
                if (empty($map[$key]['url']) && !empty($c['url'])) $map[$key]['url'] = $c['url'];
                if (empty($map[$key]['lang']) && !empty($c['lang'])) $map[$key]['lang'] = $c['lang'];
                $map[$key]['source_count'] = ($map[$key]['source_count'] ?? 1) + 1;
            }
        }
        
        // 2. Fuzzy Deduplication (Substrings)
        // Sort by name length descending to prioritize full names
        uasort($map, function($a, $b) {
            return strlen($b['name']) <=> strlen($a['name']);
        });
        
        $finalMap = [];
        foreach ($map as $key => $data) {
            $merged = false;
            foreach ($finalMap as $fKey => $fData) {
                // Check if current name is contained in an existing name (e.g. "Harry" in "Harry Potter")
                // Use word boundaries to avoid "Ron" matching "Aaron"
                $pattern = '/\b' . preg_quote($key, '/') . '\b/u';
                if (preg_match($pattern, $fKey)) {
                    // Merge into existing (longer) entry
                    $finalMap[$fKey]['description'] = $this->coherentMerge($finalMap[$fKey]['description'], $data['description']);
                    $finalMap[$fKey]['source_count'] += $data['source_count'];
                    if (empty($finalMap[$fKey]['url'])) $finalMap[$fKey]['url'] = $data['url'];
                    $merged = true;
                    break;
                }
            }
            if (!$merged) {
                $finalMap[$key] = $data;
            }
        }

        $list = array_values($finalMap);
        if (count($list) > 20) $list = array_slice($list, 0, 20);
        return $list;
    }

    private function wikiGetCharactersSection(string $pageTitle, string $lang): array
    {
        $secUrl = "https://{$lang}.wikipedia.org/w/api.php?action=parse&page=" . urlencode($pageTitle) . "&prop=sections&format=json";
        $secJson = $this->fetchUrl($secUrl);
        if (!$secJson) return [];
        $secData = json_decode($secJson, true);
        $sections = $secData['parse']['sections'] ?? [];
        $index = null;
        foreach ($sections as $s) {
            $line = strtolower($s['line'] ?? '');
            if (
                strpos($line, 'personajes') !== false ||
                strpos($line, 'protagonistas') !== false ||
                strpos($line, 'characters') !== false ||
                strpos($line, 'main characters') !== false ||
                strpos($line, 'cast') !== false ||
                strpos($line, 'reparto') !== false ||
                strpos($line, 'papeles') !== false ||
                strpos($line, 'roles') !== false
            ) {
                $index = $s['index'];
                break;
            }
        }
        if (!$index) {
            return $this->wikiExtractCharactersFromPageHTML($pageTitle, $lang);
        }
        $htmlUrl = "https://{$lang}.wikipedia.org/w/api.php?action=parse&page=" . urlencode($pageTitle) . "&prop=text&section={$index}&format=json&formatversion=2";
        $htmlJson = $this->fetchUrl($htmlUrl);
        if (!$htmlJson) return [];
        $htmlData = json_decode($htmlJson, true);
        $html = $htmlData['parse']['text'] ?? '';
        if (!$html) return [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $out = [];
        foreach (['//ul/li','//ol/li','//dl/dd','//table//tr'] as $q) {
            $nodes = $xpath->query($q);
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                if (!$text) continue;
                $nameNode = $xpath->query('.//b|.//a', $node)->item(0);
                $name = $nameNode ? trim($nameNode->textContent) : strtok($text, '–-—:');
                if (!$name || !$this->isValidCharacterName($name)) continue;
                $desc = trim($text);
                if (strlen($desc) < 10) continue;
                $link = $xpath->query('.//a', $node)->item(0);
                $href = $link && $link->attributes->getNamedItem('href') ? $link->attributes->getNamedItem('href')->nodeValue : '';
                $url = '';
                if ($href) {
                    if (strpos($href, '/wiki/') === 0) $url = "https://{$lang}.wikipedia.org" . $href;
                    elseif (strpos($href, 'http') === 0) $url = $href;
                }
                $out[] = ['name' => $name, 'description' => $this->shorten($desc), 'url' => $url, 'lang' => $lang];
                if (count($out) >= 20) break 2;
            }
        }
        return $out;
    }

    private function wikiExtractCharactersFromPageHTML(string $pageTitle, string $lang): array
    {
        $htmlUrl = "https://{$lang}.wikipedia.org/w/api.php?action=parse&page=" . urlencode($pageTitle) . "&prop=text&format=json&formatversion=2";
        $htmlJson = $this->fetchUrl($htmlUrl);
        if (!$htmlJson) return [];
        $htmlData = json_decode($htmlJson, true);
        $html = $htmlData['parse']['text'] ?? '';
        if (!$html) return [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $headings = $xpath->query('//h2|//h3');
        $out = [];
        $keywords = ['personajes', 'protagonistas', 'characters', 'main characters', 'cast'];
        foreach ($headings as $h) {
            $title = strtolower(trim($h->textContent));
            $match = false;
            foreach ($keywords as $kw) {
                if (strpos($title, $kw) !== false) { $match = true; break; }
            }
            if (!$match) continue;
            $node = $h->nextSibling;
            while ($node && !in_array(strtolower($node->nodeName), ['h2','h3'])) {
                if (strtolower($node->nodeName) === 'ul') {
                    foreach ($xpath->query('.//li', $node) as $li) {
                        $text = trim($li->textContent);
                        if (!$text) continue;
                        $nameNode = $xpath->query('.//b|.//a', $li)->item(0);
                        $name = $nameNode ? trim($nameNode->textContent) : strtok($text, '–-—:');
                        if (!$name || !$this->isValidCharacterName($name)) continue;
                        $desc = trim($text);
                        if (strlen($desc) < 10) continue;
                        $link = $xpath->query('.//a', $li)->item(0);
                        $href = $link && $link->attributes->getNamedItem('href') ? $link->attributes->getNamedItem('href')->nodeValue : '';
                        $url = '';
                        if ($href && strpos($href, '/wiki/') === 0) {
                            $url = "https://{$lang}.wikipedia.org" . $href;
                        }
                        $out[] = ['name' => $name, 'description' => $this->shorten($desc), 'url' => $url, 'lang' => $lang];
                        if (count($out) >= 20) break 3;
                    }
                }
                $node = $node->nextSibling;
            }
        }
        return $out;
    }

    private function extractTraits(string $description): array
    {
        $text = strtolower($description);
        $traits = [];
        $roles = ['protagonist','antagonist','deuteragonist','tritagonist','mentor','villain','hero','antihero',
                  'wizard','witch','student','detective','soldier','king','queen','duke','princess','prince'];
        $personality = ['brave','smart','loyal','cunning','kind','cruel','ambitious','jealous','fearless','introvert','extrovert'];
        $appearance = ['young','teenager','adult','elderly','tall','short','slim','muscular','blonde','dark-haired','red-haired','blue-eyed','green-eyed','scar'];
        $spanishRoles = ['protagonista','antagonista','mentor','villano','héroe','antiheroe','mago','bruja','estudiante','detective','soldado','rey','reina','princesa','príncipe'];
        $spanishPersonality = ['valiente','inteligente','leal','astuto','amable','cruel','ambicioso','celoso','audaz','introvertido','extrovertido'];
        $spanishAppearance = ['joven','adolescente','adulto','anciano','alto','bajo','delgado','musculoso','rubio','moreno','pelirrojo','ojos azules','ojos verdes','cicatriz'];
        foreach (array_merge($roles,$personality,$appearance,$spanishRoles,$spanishPersonality,$spanishAppearance) as $c) {
            if (strpos($text, $c) !== false) $traits[] = $c;
        }
        return array_slice(array_unique($traits), 0, 8);
    }

    private function buildPrompt(string $bookTitle, string $name, array $traits): string
    {
        $t = array_map(function($x){ return strtolower($x); }, $traits);
        $age = '';
        if (in_array('teenager', $t) || in_array('adolescente', $t) || in_array('young', $t) || in_array('joven', $t)) {
            $age = 'teenager';
        } elseif (in_array('adult', $t) || in_array('adulto', $t)) {
            $age = 'adult';
        } elseif (in_array('elderly', $t) || in_array('anciano', $t)) {
            $age = 'elderly';
        }
        $roleTags = [];
        foreach (['protagonist','protagonista','antagonist','antagonista','hero','héroe','heroine','heroína','villain','villano','mentor'] as $r) {
            if (in_array($r, $t, true)) $roleTags[] = $r;
        }
        $personalityTags = [];
        foreach (['brave','valiente','smart','inteligente','loyal','leal','cunning','astuto','kind','amable','cruel','ambicioso','ambitious','introvertido','introvert','extrovertido','extrovert'] as $p) {
            if (in_array($p, $t, true)) $personalityTags[] = $p;
        }
        $appearanceTags = [];
        foreach (['tall','alto','short','bajo','slim','delgado','muscular','musculoso','blonde','rubio','dark-haired','moreno','red-haired','pelirrojo','blue-eyed','ojos azules','green-eyed','ojos verdes','scar','cicatriz'] as $a) {
            if (in_array($a, $t, true)) $appearanceTags[] = $a;
        }
        $parts = [];
        $parts[] = "{$name} from {$bookTitle}";
        if ($age) $parts[] = $age;
        if (!empty($roleTags)) $parts[] = implode(', ', $roleTags);
        if (!empty($appearanceTags)) $parts[] = implode(', ', $appearanceTags);
        if (!empty($personalityTags)) $parts[] = implode(', ', $personalityTags);
        $style = "photorealistic human portrait, ultra realistic, natural skin texture, realistic proportions, soft studio lighting, shallow depth of field, 50mm lens, high detail, color graded";
        $neg = "no cartoon, no anime, no illustration, no cgi, no 3d render, no stylized";
        $prompt = implode(', ', $parts) . ", " . $style . ", " . $neg;
        return $this->sanitizePrompt($prompt);
    }

    private function fetchUrl($url, $timeout = 15)
    {
        // echo "[DEBUG] fetchUrl called for $url (timeout $timeout)\n";
        // static $cache = [];
        static $last = 0;
        // if (isset($cache[$url])) return $cache[$url];
        if ($this->timeboxStart > 0 && (microtime(true) - $this->timeboxStart) > 100.0) {
            return null;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Connect timeout
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // Use a real browser User-Agent to avoid blocking
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
            "Accept-Language: es-ES,es;q=0.9,en;q=0.8",
            "Referer: https://www.google.com/"
        ]);
        
        $retries = 1;
        while ($retries-- > 0) {
            // echo "[DEBUG] fetchUrl loop retry $retries\n";
            $now = microtime(true);
            if ($now - $last < 1.0) { // Increase delay
                usleep((int)((1.0 - ($now - $last)) * 1e6));
            }
            
            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            // echo "[DEBUG] fetchUrl result: Code $httpCode, Err: $err, Len: " . strlen($resp ?? '') . "\n";
            $last = microtime(true);
            
            if ($resp !== false && $httpCode >= 200 && $httpCode < 400 && strlen($resp) > 0) {
                curl_close($ch);
                // $cache[$url] = $resp;
                return $resp;
            } else {
                // echo "[Trace] fetchUrl failed for $url: Code $httpCode, Err: $err\n";
            }
            
            if ($retries > 0) usleep(1000000);
        }
        curl_close($ch);
        return null;
    }

    private function wikiFetchCharacterDetails(string $url, string $lang): ?string
    {
        $api = "https://{$lang}.wikipedia.org/w/api.php?action=parse&page=" . urlencode(basename($url)) . "&prop=text&format=json&formatversion=2";
        $json = $this->fetchUrl($api);
        if (!$json) return null;
        $data = json_decode($json, true);
        $html = $data['parse']['text'] ?? '';
        if (!$html) return null;
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $p = $xpath->query('//p')->item(0);
        if ($p) {
            $text = trim($p->textContent);
            if (strlen($text) > 50) return $this->shorten($text);
        }
        return null;
    }

    private function wikiFetchDetailsBatch(array $chars): array
    {
        $requests = [];
        $limit = 8;
        $count = 0;
        foreach ($chars as $c) {
            if (!empty($c['url']) && !empty($c['lang'])) {
                $lang = $c['lang'];
                $requests[$c['name']] = "https://{$lang}.wikipedia.org/w/api.php?action=parse&page=" . urlencode(basename($c['url'])) . "&prop=text&format=json&formatversion=2";
                $count++;
                if ($count >= $limit) break;
            }
        }
        if (empty($requests)) return [];
        $responses = $this->multiFetch(array_values($requests));
        $map = [];
        $i = 0;
        foreach ($requests as $name => $reqUrl) {
            $json = $responses[$i++] ?? null;
            if (!$json) continue;
            $data = json_decode($json, true);
            $html = $data['parse']['text'] ?? '';
            if (!$html) continue;
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            libxml_clear_errors();
            $xpath = new \DOMXPath($dom);
            $p = $xpath->query('//p')->item(0);
            if ($p) {
                $text = trim($p->textContent);
                if (strlen($text) > 50) $map[$name] = $this->shorten($text);
            }
        }
        return $map;
    }

    private function genericFetchDetailsBatch(array $chars, array $domainContains): array
    {
        $targets = [];
        $limit = 8;
        foreach ($chars as $c) {
            $u = $c['url'] ?? '';
            if (!$u) continue;
            foreach ($domainContains as $d) {
                if (strpos($u, $d) !== false) {
                    $targets[$c['name']] = $u;
                    if (count($targets) >= $limit) break 2;
                    break;
                }
            }
        }
        if (empty($targets)) return [];
        $responses = $this->multiFetch(array_values($targets));
        $map = [];
        $i = 0;
        foreach ($targets as $name => $reqUrl) {
            $html = $responses[$i++] ?? null;
            if (!$html) continue;
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            libxml_clear_errors();
            $xpath = new \DOMXPath($dom);
            $p = $xpath->query('//p')->item(0);
            if ($p) {
                $text = trim($p->textContent);
                if (strlen($text) > 50) $map[$name] = $this->shorten($text);
            }
        }
        return $map;
    }

    private function multiFetch(array $urls): array
    {
        if (!function_exists('curl_multi_init')) {
            $out = [];
            $slice = array_slice($urls, 0, 8);
            foreach ($slice as $u) {
                $out[] = $this->fetchUrl($u);
            }
            return $out;
        }
        $mh = curl_multi_init();
        $chs = [];
        $responses = [];
        $i = 0;
        $slice = array_slice($urls, 0, 8);
        foreach ($slice as $url) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
            ]);
            curl_multi_add_handle($mh, $ch);
            $chs[$i++] = $ch;
        }
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 1.0);
        } while ($running > 0);
        foreach ($chs as $idx => $ch) {
            $responses[$idx] = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        return $responses;
    }

    private function isValidCharacterName(string $name): bool
    {
        $trim = trim($name);
        if ($trim === '') return false;
        // Reject if mostly lowercase single word (likely not proper noun)
        if (preg_match('/^[a-záéíóúñü]+$/u', $trim)) return false;
        
        // Stopwords blacklist (English and Spanish)
        $stopwords = [
            'The', 'A', 'An', 'This', 'That', 'It', 'He', 'She', 'They', 'We', 'You', 'I', 'In', 'On', 'At', 'To', 'For', 'Of', 'With', 'By', 'From', 'About', 'As', 'If', 'But', 'Or', 'And', 'Not', 'No', 'Yes', 'So', 'Then', 'Else', 'When', 'Where', 'Why', 'How', 'Which', 'Who', 'What', 'Is', 'Are', 'Was', 'Were', 'Be', 'Been', 'Being', 'Has', 'Have', 'Had', 'Do', 'Does', 'Did', 'Can', 'Could', 'Will', 'Would', 'Shall', 'Should', 'May', 'Might', 'Must', 'My', 'Your', 'His', 'Her', 'Its', 'Our', 'Their', 'Each', 'These', 'Those', 'Some', 'Any', 'All', 'Many', 'Much', 'Few', 'Little', 'Other', 'Another', 'Such', 'Same', 'Different', 'Own', 'Very', 'Too', 'Also', 'Just', 'Now', 'Only', 'Even', 'Still', 'Back', 'Here', 'There', 'Up', 'Down', 'Out', 'Over', 'Under', 'Above', 'Below', 'Between', 'Through', 'Into', 'During', 'Before', 'After', 'Since', 'Until', 'While', 'Although', 'Though', 'Because', 'Unless', 'However', 'Therefore', 'Thus', 'Hence', 'Yet', 'Nor', 'Philosopher', 'Stone', 'British', 'Rowling', 'Book', 'Series', 'List', 'Page', 'Edit', 'History', 'Talk', 'Main', 'Article', 'Read', 'View', 'Source', 'Search', 'Navigation', 'Contribute', 'Tools', 'Print', 'Languages', 'Download', 'Help', 'Community', 'Portal', 'Recent', 'Changes', 'Upload', 'File', 'Special', 'Pages', 'Permanent', 'Link', 'Cite', 'Create', 'Account', 'Log', 'User', 'Talk', 'Contributions', 'Preferences', 'Watchlist',
            'El', 'La', 'Los', 'Las', 'Un', 'Una', 'Unos', 'Unas', 'Este', 'Esta', 'Estos', 'Estas', 'Ese', 'Esa', 'Esos', 'Esas', 'Aquel', 'Aquella', 'Aquellos', 'Aquellas', 'Yo', 'Tu', 'Ella', 'Ellos', 'Ellas', 'Nosotros', 'Vosotros', 'Usted', 'Ustedes', 'Mi', 'Su', 'Nuestro', 'Vuestro', 'Sus', 'Que', 'Quien', 'Cual', 'Donde', 'Cuando', 'Como', 'Por', 'Para', 'Con', 'Sin', 'Sobre', 'Entre', 'Hasta', 'Desde', 'Durante', 'Antes', 'Despues', 'Mientras', 'Aunque', 'Pero', 'Sino', 'Ni', 'Si', 'Tal', 'Tan', 'Muy', 'Mas', 'Menos', 'Mucho', 'Poco', 'Todo', 'Nada', 'Algo', 'Alguien', 'Nadie', 'Algun', 'Ningun', 'Otro', 'Mismo', 'Editar', 'Historial', 'Discusion', 'Leer', 'Ver', 'Fuente', 'Buscar', 'Navegacion', 'Contribuir', 'Herramientas', 'Imprimir', 'Idiomas', 'Descargar', 'Ayuda', 'Comunidad', 'Cambios', 'Subir', 'Archivo', 'Especial', 'Paginas', 'Enlace', 'Citar', 'Crear', 'Cuenta', 'Acceder', 'Usuario', 'Contribuciones', 'Preferencias', 'Lista', 'Seguimiento'
        ];
        
        if (in_array($trim, $stopwords)) return false;

        // Accept if contains space with capitalized words or starts uppercase
        if (preg_match('/^([A-ZÁÉÍÓÚÑÜ][\\p{L}\\-]+)(\\s+y\\s+[A-ZÁÉÍÓÚÑÜ][\\p{L}\\-]+|\\s+[A-ZÁÉÍÓÚÑÜ][\\p{L}\\-]+)*/u', $trim)) return true;
        // Otherwise require first letter uppercase
        return preg_match('/^[A-ZÁÉÍÓÚÑÜ]/u', $trim) === 1;
    }

    private function sanitizePrompt(string $text): string
    {
        // Remove excessive punctuation and limit length; transliterate accents
        $text = preg_replace('/[\\r\\n]+/', ' ', $text);
        $text = preg_replace('/\\s+/', ' ', $text);
        if (function_exists('iconv')) {
            $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($t !== false) $text = $t;
        }
        return trim(substr($text, 0, 180));
    }

    private function shorten(string $text): string
    {
        $text = trim($text);
        $sentences = preg_split('/[\.!\?]\s+/', $text);
        $first = $sentences[0] ?? $text;
        return strlen($first) > 300 ? substr($first, 0, 300) : $first;
    }

    private function cacheKey(string $title, string $author): string
    {
        $key = $this->mbLower(trim($title . '_' . $author));
        $key = preg_replace('/[^a-z0-9\-]+/i', '_', $key);
        return $key;
    }

    private function cacheDir(): string
    {
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'characters';
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        return $dir;
    }

    private function cacheGet(string $key): ?array
    {
        $file = $this->cacheDir() . DIRECTORY_SEPARATOR . $key . '.json';
        if (!file_exists($file)) return null;
        $ttl = 60 * 60 * 24 * 7; // 7 days
        if (time() - filemtime($file) > $ttl) return null;
        $data = json_decode(@file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    private function cacheSet(string $key, array $data): void
    {
        $file = $this->cacheDir() . DIRECTORY_SEPARATOR . $key . '.json';
        @file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function mbLower(string $text): string
    {
        if (function_exists('mb_strtolower')) {
            return \mb_strtolower($text, 'UTF-8');
        }
        return strtolower($text);
    }
}
