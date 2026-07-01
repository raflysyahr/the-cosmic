<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\KomikcastService;
use Illuminate\Http\Request;

class SeriesController extends Controller
{
    public function index(Request $request, KomikcastService $svc)
    {
        $result = $svc->getSeries(
            page: (int) $request->query('page', 1),
            take: (int) $request->query('take', 20),
            format: $request->query('format', ''),
            sort: $request->query('sort', ''),
            preset: $request->query('preset', ''),
            genreId: $request->query('genreId') ? (int) $request->query('genreId') : null,
            takeChapter: (int) $request->query('takeChapter', 0),
        );

        return response()->json($result);
    }

    public function search(Request $request, KomikcastService $svc)
    {
        $q = $request->query('q', '');
        if (!trim($q)) {
            return response()->json(['success' => true, 'data' => [], 'meta' => ['total' => 0, 'page' => 1, 'lastPage' => 0]]);
        }

        $result = $svc->search(
            q: $q,
            page: (int) $request->query('page', 1),
            take: (int) $request->query('take', 12),
        );

        return response()->json($result);
    }

    public function trending(KomikcastService $svc)
    {
        return response()->json($svc->getTrending());
    }

    public function show(string $slug, KomikcastService $svc)
    {
        $result = $svc->getDetail($slug);

        if (!($result['success'] ?? false)) {
            return response()->json($result, 404);
        }

        $chapters = $svc->getChapters($slug);
        $result['data']['chapters'] = $chapters['data'] ?? [];

        return response()->json($result);
    }

    public function chapters(string $slug, KomikcastService $svc)
    {
        return response()->json($svc->getChapters($slug));
    }

    public function chapterPages(string $slug, int $index, KomikcastService $svc)
    {
        return response()->json($svc->getChapterPages($slug, $index));
    }
}
