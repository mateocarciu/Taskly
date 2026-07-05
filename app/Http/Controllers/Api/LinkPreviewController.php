<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LinkPreviewController extends Controller
{
    public function batch(Request $request)
    {
        $request->validate([
            'urls' => 'required|array',
            'urls.*' => 'required|url',
        ]);

        $urls = array_unique($request->input('urls'));
        $results = [];
        $urlsToFetch = [];

        $localHosts = ['localhost', '127.0.0.1', '127.0.0.2', '::1', strtolower(parse_url(config('app.url', ''), PHP_URL_HOST) ?? '')];
        $serverHost = strtolower(parse_url($request->getSchemeAndHttpHost(), PHP_URL_HOST) ?? '');
        if ($serverHost) {
            $localHosts[] = $serverHost;
        }

        foreach ($urls as $url) {
            $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
            
            if (in_array($host, $localHosts)) {
                $results[$url] = [
                    'url' => $url,
                    'title' => $host ?: 'Local Link',
                    'description' => 'Link to local page',
                    'image' => null,
                    'favicon' => null,
                ];
                continue;
            }

            $cacheKey = 'link_preview_' . md5($url);
            $cached = Cache::get($cacheKey);

            if ($cached) {
                $results[$url] = $cached;
            } else {
                $urlsToFetch[] = $url;
            }
        }

        if (!empty($urlsToFetch)) {
            $responses = Http::pool(function ($pool) use ($urlsToFetch) {
                return array_map(function ($url) use ($pool) {
                    return $pool->timeout(3)
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        ])
                        ->get($url);
                }, $urlsToFetch);
            });

            foreach ($urlsToFetch as $index => $url) {
                $response = $responses[$index];
                $cacheKey = 'link_preview_' . md5($url);

                try {
                    if ($response instanceof \Exception || !$response->successful()) {
                        throw new \Exception('Failed to fetch ' . $url);
                    }
                    $data = $this->parseResponse($response, $url);
                } catch (\Exception $e) {
                    $data = [
                        'url' => $url,
                        'title' => parse_url($url, PHP_URL_HOST),
                        'description' => '',
                        'image' => null,
                        'favicon' => null,
                    ];
                }

                Cache::put($cacheKey, $data, now()->addDay());
                $results[$url] = $data;
            }
        }

        return response()->json($results);
    }

    private function parseResponse($response, string $url): array
    {
        $contentType = $response->header('Content-Type', '');
        if (strpos($contentType, 'text/html') === false) {
            return [
                'url' => $url,
                'title' => basename($url) ?: parse_url($url, PHP_URL_HOST),
                'description' => 'Link to file: ' . $contentType,
                'image' => null,
                'favicon' => null,
            ];
        }

        $html = $response->body();

        // Only parse the <head> section (or first 150KB) to save memory and CPU
        $headEnd = stripos($html, '</head>');
        if ($headEnd !== false) {
            $html = substr($html, 0, $headEnd + 7);
        } else {
            $html = substr($html, 0, 150000);
        }

        // Parse HTML using DOMDocument
        $doc = new \DOMDocument();
        // Suppress HTML parsing warnings
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR | LIBXML_NOWARNING);

        $xpath = new \DOMXPath($doc);

        // Extract metadata
        $title = $this->getXpathContent($xpath, [
            '//meta[@property="og:title"]/@content',
            '//meta[@name="twitter:title"]/@content',
            '//title',
        ]) ?: parse_url($url, PHP_URL_HOST);

        $description = $this->getXpathContent($xpath, [
            '//meta[@property="og:description"]/@content',
            '//meta[@name="twitter:description"]/@content',
            '//meta[@name="description"]/@content',
        ]);

        $image = $this->getXpathContent($xpath, [
            '//meta[@property="og:image"]/@content',
            '//meta[@name="twitter:image"]/@content',
        ]);

        $favicon = $this->getXpathContent($xpath, [
            '//link[@rel="icon"]/@href',
            '//link[@rel="shortcut icon"]/@href',
            '//link[@rel="apple-touch-icon"]/@href',
        ]);

        // Resolve relative paths to absolute URLs
        if ($image) {
            $image = $this->resolveUrl($image, $url);
        }
        if ($favicon) {
            $favicon = $this->resolveUrl($favicon, $url);
        } else {
            $favicon = null;
        }

        return [
            'url' => $url,
            'title' => trim($title),
            'description' => trim($description),
            'image' => $image,
            'favicon' => $favicon,
        ];
    }

    private function getXpathContent(\DOMXPath $xpath, array $queries): ?string
    {
        foreach ($queries as $query) {
            $nodes = $xpath->query($query);
            if ($nodes && $nodes->length > 0) {
                return $nodes->item(0)->nodeValue;
            }
        }
        return null;
    }

    private function resolveUrl(string $path, string $baseUrl): string
    {
        if (parse_url($path, PHP_URL_SCHEME) !== null) {
            return $path;
        }

        $parts = parse_url($baseUrl);
        $domain = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');

        if (strpos($path, '//') === 0) {
            return $parts['scheme'] . ':' . $path;
        }

        if (strpos($path, '/') === 0) {
            return $domain . $path;
        }

        $basePath = isset($parts['path']) ? $parts['path'] : '/';
        if (substr($basePath, -1) !== '/') {
            $basePath = dirname($basePath) . '/';
        }

        return $domain . $basePath . $path;
    }

}
