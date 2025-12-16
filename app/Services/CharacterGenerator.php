<?php

namespace App\Services;

class CharacterGenerator
{
    private $timeboxStart = 0.0;
    public function generateCharacters(array $bookData): array
    {
        $title = $bookData['title'] ?? '';
        if (!$title) return [];
        $this->timeboxStart = microtime(true);
        $cacheKey = $this->cacheKey($title, $bookData['author'] ?? '');
        $cached = $this->cacheGet($cacheKey);
        if ($cached) return $cached;
        $characters = $this->extractCharactersFromWikipedia($title);
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

    private function extractCharactersFromWikipedia(string $title): array
    {
        $page = $this->wikiFindPage($title, 'es');
        if (!$page) $page = $this->wikiFindPage($title, 'en');
        if (!$page) return [];
        $chars = $this->wikiGetCharactersSection($page['title'], $page['lang']);
        if (!empty($chars)) return $chars;
        // Try dedicated list pages if main article has no character section
        $listPage = $this->wikiFindCharactersListPage($title, $page['lang']);
        if ($listPage) {
            $chars = $this->wikiExtractCharactersFromPageHTML($listPage['title'], $listPage['lang']);
        }
        return $chars;
    }

    private function wikiFindPage(string $title, string $lang): ?array
    {
        $queries = [
            $title . " novela",
            $title . " novel",
            $title
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
        $out = [];
        $max = 8;
        $siteQueries = [
            ["sparknotes.com", "\"{$title}\" characters site:sparknotes.com"],
            ["litcharts.com", "\"{$title}\" characters site:litcharts.com"],
            ["shmoop.com", "\"{$title}\" characters site:shmoop.com"],
            ["fandom.com", "\"{$title}\" characters site:fandom.com"],
            ["wikia.org", "\"{$title}\" characters site:wikia.org"],
            ["bookanalysis.com", $title . " characters site:bookanalysis.com"],
            ["goodreads.com", $title . " characters site:goodreads.com"],
            ["", $title . " personajes principales"],
            ["fandom.com", "\"Boys of Tommen\" characters site:fandom.com"],
            ["fandom.com", "\"{$title}\" personajes site:fandom.com"],
            ["goodreads.com", "\"{$title}\" personajes site:goodreads.com"],
            ["supersummary.com", "\"{$title}\" characters site:supersummary.com"],
            ["cliffsnotes.com", "\"{$title}\" characters site:cliffsnotes.com"],
            ["enotes.com", "\"{$title}\" characters site:enotes.com"],
            ["penguinrandomhouse.com", "\"{$title}\" characters site:penguinrandomhouse.com"],
            ["harpercollins.com", "\"{$title}\" characters site:harpercollins.com"],
            ["macmillan.com", "\"{$title}\" characters site:macmillan.com"]
        ];
        $siteQueries = array_slice($siteQueries, 0, 10);
        if (!empty($author)) {
            $authorQueries = [
                ["sparknotes.com", "\"{$title}\" \"{$author}\" characters site:sparknotes.com"],
                ["litcharts.com", "\"{$title}\" \"{$author}\" characters site:litcharts.com"],
                ["fandom.com", "\"{$title}\" \"{$author}\" characters site:fandom.com"],
                ["supersummary.com", "\"{$title}\" \"{$author}\" characters site:supersummary.com"],
                ["cliffsnotes.com", "\"{$title}\" \"{$author}\" characters site:cliffsnotes.com"],
                ["enotes.com", "\"{$title}\" \"{$author}\" characters site:enotes.com"]
            ];
            $siteQueries = array_merge($siteQueries, array_slice($authorQueries, 0, 4));
        }
        foreach ($siteQueries as [$site, $q]) {
            if ($this->timeboxStart > 0 && (microtime(true) - $this->timeboxStart) > 18.0) break;
            $urls = $this->searchDuckDuckGo($q);
            foreach ($urls as $u) {
                if ($this->timeboxStart > 0 && (microtime(true) - $this->timeboxStart) > 19.5) break 2;
                $list = [];
                if (strpos($u, 'sparknotes.com') !== false) {
                    $list = $this->parseSparkNotes($u);
                } elseif (strpos($u, 'litcharts.com') !== false) {
                    $list = $this->parseLitCharts($u);
                } elseif (strpos($u, 'shmoop.com') !== false) {
                    $list = $this->parseShmoop($u);
                } elseif (strpos($u, 'bookanalysis.com') !== false) {
                    $list = $this->parseBookAnalysis($u);
                } elseif (strpos($u, 'fandom') !== false || strpos($u, 'wikia') !== false) {
                    $list = $this->parseFandomCharacters($u);
                } elseif (strpos($u, 'goodreads.com') !== false) {
                    $list = $this->parseGenericCharacterListPage($u);
                } elseif (strpos($u, 'supersummary.com') !== false) {
                    $list = $this->parseSuperSummary($u);
                } elseif (strpos($u, 'cliffsnotes.com') !== false) {
                    $list = $this->parseCliffsNotes($u);
                } elseif (strpos($u, 'enotes.com') !== false) {
                    $list = $this->parseENotes($u);
                } elseif (strpos($u, 'penguinrandomhouse.com') !== false || strpos($u, 'harpercollins.com') !== false || strpos($u, 'macmillan.com') !== false) {
                    $list = $this->parsePublisherPage($u);
                } else {
                    $list = $this->parseGenericCharacterListPage($u);
                }
                $out = $this->mergeCharacterLists($out, $list);
                if (count($out) < $max) {
                    $textNames = $this->parseNamesFromUrl($u);
                    $out = $this->mergeCharacterLists($out, array_map(function($n){ return ['name'=>$n,'description'=>'','url'=>'','lang'=>'']; }, $textNames));
                }
                if (count($out) >= $max) break 2;
            }
        }
        return $out;
    }

    private function searchDuckDuckGo(string $query): array
    {
        $url = "https://duckduckgo.com/html/?q=" . urlencode($query);
        $html = $this->fetchUrl($url);
        if (!$html) return [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//a[@href]');
        $out = [];
        foreach ($nodes as $a) {
            $href = $a->getAttribute('href');
            if (strpos($href, 'http') === 0) {
                // Skip duckduckgo redirect links
                if (strpos($href, 'duckduckgo.com') !== false) continue;
                $out[] = $href;
                if (count($out) >= 6) break;
            }
        }
        return $out;
    }

    private function extractCharactersFromWikidata(string $title): array
    {
        $itemId = $this->wikidataFindItemId($title);
        if (!$itemId) return [];
        $chars = $this->wikidataGetCharacters($itemId);
        return $chars;
    }

    private function wikidataFindItemId(string $title): ?string
    {
        foreach (['es','en'] as $lang) {
            $url = "https://www.wikidata.org/w/api.php?action=wbsearchentities&search=" . urlencode($title) . "&language={$lang}&format=json";
            $json = $this->fetchUrl($url);
            if (!$json) continue;
            $data = json_decode($json, true);
            if (!empty($data['search'][0]['id'])) {
                return $data['search'][0]['id'];
            }
        }
        return null;
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

    private function parseSparkNotes(string $url): array
    {
        $html = $this->fetchUrl($url);
        if (!$html) return [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $out = [];
        // common patterns
        $cards = $xpath->query('//a[contains(@href,"/characters/")]|//li//a[contains(@href,"character")]');
        foreach ($cards as $link) {
            $name = trim($link->textContent);
            if (!$name || strlen($name) < 2) continue;
            $href = $link->getAttribute('href');
            $desc = '';
            // Try nearby paragraph
            $p = $link->parentNode ? $xpath->query('.//p', $link->parentNode)->item(0) : null;
            if ($p) $desc = trim($p->textContent);
            $out[] = ['name' => $name, 'description' => $desc, 'url' => $href, 'lang' => 'en'];
            if (count($out) >= 6) break;
        }
        return $out;
    }

    private function parseShmoop(string $url): array
    {
        $html = $this->fetchUrl($url);
        if (!$html) return [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $out = [];
        $links = $xpath->query('//a[contains(@href,"/characters/")]|//a[contains(translate(@href,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"character")]');
        foreach ($links as $a) {
            $name = trim($a->textContent);
            if (!$name) continue;
            $href = $a->getAttribute('href');
            $out[] = ['name' => $name, 'description' => '', 'url' => $href, 'lang' => 'en'];
            if (count($out) >= 6) break;
        }
        return $out;
    }

    private function parseLitCharts(string $url): array
    {
        $html = $this->fetchUrl($url);
        if (!$html) return [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $out = [];
        $links = $xpath->query('//a[contains(@href,"/lit/") and contains(translate(@href,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"characters")] | //a[contains(translate(@href,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"character")]');
        foreach ($links as $a) {
            $name = trim($a->textContent);
            if (!$name || !$this->isValidCharacterName($name)) continue;
            $href = $a->getAttribute('href');
            $out[] = ['name' => $name, 'description' => '', 'url' => $href, 'lang' => 'en'];
            if (count($out) >= 8) break;
        }
        return $out;
    }

    private function parseBookAnalysis(string $url): array
    {
        $html = $this->fetchUrl($url);
        if (!$html) return [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $out = [];
        $links = $xpath->query('//a[contains(translate(@href,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"character")]|//h2|//h3');
        foreach ($links as $node) {
            $name = trim($node->textContent);
            if (!$name || strlen($name) < 2) continue;
            if (!$this->isValidCharacterName($name)) continue;
            $href = $node->nodeName === 'a' ? $node->getAttribute('href') : '';
            $out[] = ['name' => $name, 'description' => '', 'url' => $href, 'lang' => 'en'];
            if (count($out) >= 8) break;
        }
        return $out;
    }

    private function parseSuperSummary(string $url): array
    {
        $html = $this->fetchUrl($url);
        if (!$html) return [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $out = [];
        $links = $xpath->query('//a[contains(translate(@href,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"character")] | //h2 | //h3');
        foreach ($links as $node) {
            $name = trim($node->textContent);
            if (!$name || !$this->isValidCharacterName($name)) continue;
            $href = $node->nodeName === 'a' ? $node->getAttribute('href') : '';
            $out[] = ['name' => $name, 'description' => '', 'url' => $href, 'lang' => 'en'];
            if (count($out) >= 10) break;
        }
        return $out;
    }

    private function parseCliffsNotes(string $url): array
    {
        $html = $this->fetchUrl($url);
        if (!$html) return [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $out = [];
        $links = $xpath->query('//a[contains(translate(@href,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"character")] | //h2 | //h3 | //strong');
        foreach ($links as $node) {
            $name = trim($node->textContent);
            if (!$name || !$this->isValidCharacterName($name)) continue;
            $href = $node->nodeName === 'a' ? $node->getAttribute('href') : '';
            $out[] = ['name' => $name, 'description' => '', 'url' => $href, 'lang' => 'en'];
            if (count($out) >= 10) break;
        }
        return $out;
    }

    private function parseENotes(string $url): array
    {
        $html = $this->fetchUrl($url);
        if (!$html) return [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $out = [];
        $links = $xpath->query('//a[contains(translate(@href,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"character")] | //h2 | //h3 | //li');
        foreach ($links as $node) {
            $name = trim($node->textContent);
            if (!$name || !$this->isValidCharacterName($name)) continue;
            $href = $node->nodeName === 'a' ? $node->getAttribute('href') : '';
            $out[] = ['name' => $name, 'description' => '', 'url' => $href, 'lang' => 'en'];
            if (count($out) >= 10) break;
        }
        return $out;
    }

    private function parsePublisherPage(string $url): array
    {
        $html = $this->fetchUrl($url);
        if (!$html) return [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $out = [];
        $nodes = $xpath->query('//h2|//h3|//li|//p');
        foreach ($nodes as $n) {
            $name = trim($n->textContent);
            if (!$name || strlen($name) < 2) continue;
            if (!$this->isValidCharacterName($name)) continue;
            $out[] = ['name' => $name, 'description' => '', 'url' => $url, 'lang' => 'en'];
            if (count($out) >= 6) break;
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
    private function parseGenericCharacterListPage(string $url): array
    {
        $html = $this->fetchUrl($url);
        if (!$html) return [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $out = [];
        $lis = $xpath->query('//li');
        foreach ($lis as $li) {
            $text = trim($li->textContent);
            if (!$text || strlen($text) < 4) continue;
            $nameNode = $xpath->query('.//b|.//a', $li)->item(0);
            $name = $nameNode ? trim($nameNode->textContent) : strtok($text, '–-—:');
            if (!$name || strlen($name) < 2) continue;
            $link = $xpath->query('.//a', $li)->item(0);
            $href = $link ? $link->getAttribute('href') : '';
            $out[] = ['name' => $name, 'description' => $this->shorten($text), 'url' => $href, 'lang' => 'en'];
            if (count($out) >= 20) break;
        }
        return $out;
    }

    private function parseFandomCharacters(string $url): array
    {
        $html = $this->fetchUrl($url);
        if (!$html) return [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $out = [];
        // Try category members grid
        $links = $xpath->query('//a[contains(@class,"category-page__member-link")]');
        if ($links->length === 0) {
            // Fallback: any link to /wiki/ that looks like character
            $links = $xpath->query('//a[contains(@href,"/wiki/")]');
        }
        foreach ($links as $a) {
            $name = trim($a->textContent);
            if (!$name || !$this->isValidCharacterName($name)) continue;
            $href = $a->getAttribute('href');
            if ($href && strpos($href, 'http') !== 0) {
                // make absolute if relative
                $parse = parse_url($url);
                $base = $parse['scheme'] . '://' . $parse['host'];
                if ($href[0] !== '/') $href = '/' . $href;
                $href = $base . $href;
            }
            $out[] = ['name' => $name, 'description' => '', 'url' => $href, 'lang' => 'en'];
            if (count($out) >= 12) break;
        }
        return $out;
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
        $map = [];
        foreach ($chars as $c) {
            $name = isset($c['name']) ? trim($c['name']) : '';
            if ($name === '' || !$this->isValidCharacterName($name)) continue;
            $key = $this->mbLower($name);
            if (!isset($map[$key])) {
                $map[$key] = [
                    'name' => $name,
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
        $list = array_values($map);
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
                strpos($line, 'main characters') !== false
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

    private function fetchUrl($url)
    {
        static $cache = [];
        static $last = 0;
        if (isset($cache[$url])) return $cache[$url];
        if ($this->timeboxStart > 0 && (microtime(true) - $this->timeboxStart) > 20.0) {
            return null;
        }
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: BookVibes/1.0 (Character Scraper)\r\n",
                "timeout" => 6
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
                "allow_self_signed" => true
            ]
        ];
        $context = stream_context_create($opts);
        $retries = 1;
        while ($retries-- > 0) {
            $now = microtime(true);
            if ($now - $last < 0.2) {
                usleep((int)((0.2 - ($now - $last)) * 1e6));
            }
            $resp = @file_get_contents($url, false, $context);
            $last = microtime(true);
            if ($resp !== false && strlen($resp) > 0) {
                $cache[$url] = $resp;
                return $resp;
            }
            usleep(150000);
        }
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
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => "BookVibes/1.0 (Character Scraper)"
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
        $key = $this->mbLower(trim($title . '|' . $author));
        $key = preg_replace('/[^a-z0-9\-\|]+/i', '_', $key);
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
