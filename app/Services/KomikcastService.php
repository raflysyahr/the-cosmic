<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Response;

class KomikcastService
{
    private const WORKER_URL = 'https://nice-try-your-job.xor96982.workers.dev/api/proxy';

    private function request(string $method, string $path, array $params = [], array $body = []): array
    {
        $url = self::WORKER_URL . '/' . ltrim($path, '/');
        if ($params) {
            $url .= '?' . http_build_query($params);
        }

        $http = Http::timeout(15)->withHeaders([
            'Accept' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 Chrome/132.0.0.0 Mobile Safari/537.36',
        ]);

        $response = match (strtolower($method)) {
            'get' => $http->get($url),
            'post' => $http->post($url, $body),
            default => $http->get($url),
        };

        if ($response->failed()) {
            return [
                'success' => false,
                'error' => 'Upstream error: ' . $response->status(),
            ];
        }

        return $response->json() ?? ['success' => false, 'error' => 'Empty response'];
    }

    private function normalizeItem(array $item): array
    {
        $d = $item['data'] ?? [];
        $chapters = $item['chapters'] ?? [];
        $latestChapter = null;

        if (!empty($chapters)) {
            $first = $chapters[0];
            $latestChapter = [
                'index' => $first['chapterIndex'] ?? ($first['data']['index'] ?? 0),
                'title' => !empty($first['data']['title']) ? $first['data']['title'] : (!empty($first['title']) ? $first['title'] : ('Chapter ' . ($first['chapterIndex'] ?? 1))),
                'releasedAt' => $first['data']['createdAt'] ?? $first['createdAt'] ?? '',
            ];
        } elseif (!empty($d['totalChapters'])) {
            $latestChapter = [
                'index' => (int) $d['totalChapters'],
                'title' => 'Chapter ' . $d['totalChapters'],
                'releasedAt' => $item['updatedAt'] ?? '',
            ];
        }

        return [
            'id' => $item['id'] ?? 0,
            'title' => $d['title'] ?? '',
            'slug' => $d['slug'] ?? '',
            'cover' => $d['coverImage'] ?? '',
            'rating' => $d['rating'] ?? 0,
            'status' => $d['status'] ?? 'unknown',
            'type' => $d['format'] ?? '',
            'isHot' => $d['isHot'] ?? false,
            'totalChapters' => (int) ($d['totalChapters'] ?? 0),
            'genreIds' => $d['genreIds'] ?? [],
            'latestChapter' => $latestChapter,
        ];
    }

    public function getSeries(int $page = 1, int $take = 20, string $format = '', string $sort = '', string $preset = '', ?int $genreId = null, int $takeChapter = 0): array
    {
        $params = [
            'take' => $genreId ? 200 : $take,
            'page' => $genreId ? 1 : $page,
            'takeChapter' => $takeChapter ?: 1,
        ];
        if ($format) $params['format'] = $format;
        if ($sort) $params['sort'] = $sort;
        if ($preset) $params['preset'] = $preset;

        $result = $this->request('get', 'series', $params);

        if (!($result['success'] ?? true)) {
            return $result;
        }

        $rawData = $result['data'] ?? [];
        $meta = $result['meta'] ?? ['total' => 0, 'page' => 1, 'lastPage' => 1];

        if ($genreId) {
            $filtered = array_filter($rawData, function ($item) use ($genreId) {
                $ids = $item['data']['genreIds'] ?? [];
                return in_array($genreId, $ids);
            });
            $filtered = array_values($filtered);
            $total = count($filtered);
            $lastPage = max(1, (int) ceil($total / $take));
            $offset = ($page - 1) * $take;
            $rawData = array_slice($filtered, $offset, $take);
            $meta = ['total' => $total, 'page' => $page, 'lastPage' => $lastPage];
        }

        return [
            'success' => true,
            'data' => array_map(fn($item) => $this->normalizeItem($item), $rawData),
            'meta' => $meta,
        ];
    }

    public function search(string $q, int $page = 1, int $take = 12): array
    {
        $filter = 'title=like="' . $q . '",nativeTitle=like="' . $q . '"';

        $params = [
            'filter' => $filter,
            'takeChapter' => 2,
            'includeMeta' => 'true',
            'sort' => 'latest',
            'sortOrder' => 'desc',
            'take' => $take,
            'page' => $page,
        ];

        $result = $this->request('get', 'series', $params);

        if (!($result['success'] ?? true)) {
            return $result;
        }

        $rawData = $result['data'] ?? [];
        $meta = $result['meta'] ?? ['total' => 0, 'page' => 1, 'lastPage' => 1];

        return [
            'success' => true,
            'data' => array_map(fn($item) => $this->normalizeItem($item), $rawData),
            'meta' => $meta,
        ];
    }

    public function getTrending(): array
    {
        $result = $this->request('get', 'series/trending', ['take' => 10]);

        if (!($result['success'] ?? true)) {
            return ['success' => false, 'data' => []];
        }

        return [
            'success' => true,
            'data' => array_map(fn($item) => $this->normalizeItem($item), $result['data'] ?? []),
        ];
    }

    public function getDetail(string $slug): array
    {
        $result = $this->request('get', "series/{$slug}");

        if (!($result['success'] ?? true)) {
            return ['success' => false, 'error' => $result['error'] ?? 'Not found'];
        }

        $item = $result['data'] ?? [];
        $d = $item['data'] ?? [];

        return [
            'success' => true,
            'data' => [
                'id' => $item['id'] ?? 0,
                'title' => $d['title'] ?? '',
                'nativeTitle' => $d['nativeTitle'] ?? '',
                'slug' => $d['slug'] ?? '',
                'cover' => $d['coverImage'] ?? '',
                'author' => $d['author'] ?? '',
                'rating' => $d['rating'] ?? 0,
                'status' => $d['status'] ?? 'unknown',
                'type' => $d['format'] ?? '',
                'isHot' => $d['isHot'] ?? false,
                'totalChapters' => (int) ($d['totalChapters'] ?? 0),
                'genreIds' => $d['genreIds'] ?? [],
                'animeAdaptation' => $d['animeAdaptation'] ?? false,
                'views' => $d['views'] ?? 0,
                'synopsis' => $d['synopsis'] ?? '',
                'releasedAt' => $d['releaseDate'] ?? $item['createdAt'] ?? '',
            ],
        ];
    }

    public function getChapters(string $slug): array
    {
        $result = $this->request('get', "series/{$slug}/chapters");

        if (!($result['success'] ?? true)) {
            return ['success' => false, 'data' => []];
        }

        $raw = $result['data'] ?? [];

        $chapters = array_filter($raw, fn($c) => !($c['data']['isDraft'] ?? false));

        usort($chapters, fn($a, $b) => ($b['data']['index'] ?? 0) - ($a['data']['index'] ?? 0));

        $seen = [];
        $deduped = [];
        foreach ($chapters as $c) {
            $idx = $c['data']['index'] ?? 0;
            if (!isset($seen[$idx])) {
                $seen[$idx] = true;
                $deduped[] = $c;
            }
        }

        return [
            'success' => true,
            'data' => array_map(fn($c) => [
                'index' => $c['data']['index'] ?? 0,
                'title' => $c['data']['title'] ?? '',
                'releasedAt' => $c['createdAt'] ?? '',
            ], $deduped),
        ];
    }

    public function getChapterPages(string $slug, int $index): array
    {
        $result = $this->request('get', "series/{$slug}/chapters/{$index}");

        if (!($result['success'] ?? true)) {
            return ['success' => false, 'data' => ['images' => []]];
        }

        $d = $result['data']['data'] ?? [];

        return [
            'success' => true,
            'data' => [
                'images' => $d['images'] ?? [],
            ],
        ];
    }

    public function getGenres(): array
    {
        $result = $this->request('get', 'genres');

        if (!($result['success'] ?? true)) {
            return ['success' => false, 'data' => []];
        }

        return [
            'success' => true,
            'data' => array_map(fn($g) => [
                'id' => $g['id'] ?? 0,
                'name' => $g['data']['name'] ?? '',
                'slug' => strtolower(str_replace(' ', '-', $g['data']['name'] ?? '')),
            ], $result['data'] ?? []),
        ];
    }

    public function login(string $email, string $password): array
    {
        $result = $this->request('post', 'auth/login', [], [
            'email' => $email,
            'password' => $password,
        ]);

        if (!($result['success'] ?? true)) {
            return ['success' => false, 'error' => $result['error'] ?? 'Login failed'];
        }

        $d = $result['data'] ?? [];
        $token = $d['token'] ?? $result['token'] ?? '';

        return [
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $d['user'] ?? null,
            ],
        ];
    }

    public function register(string $username, string $email, string $password): array
    {
        $result = $this->request('post', 'auth/register', [], [
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ]);

        if (!($result['success'] ?? true)) {
            return ['success' => false, 'error' => $result['error'] ?? 'Registration failed'];
        }

        $d = $result['data'] ?? [];
        $token = $d['token'] ?? $result['token'] ?? '';

        return [
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => $d['user'] ?? null,
            ],
        ];
    }

    public function proxyImage(string $url): \Illuminate\Http\Response
    {
        $targetUrl = self::WORKER_URL . '/image?url=' . urlencode($url);

        $response = Http::timeout(15)->get($targetUrl);

        if ($response->failed()) {
            return response('', 404);
        }

        return response(
            $response->body(),
            200,
            [
                'Content-Type' => $response->header('Content-Type', 'image/jpeg'),
                'Cache-Control' => 'public, max-age=86400',
                'Access-Control-Allow-Origin' => '*',
            ]
        );
    }
}
