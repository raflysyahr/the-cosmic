import { Link } from '@inertiajs/react'
import { useState, useEffect, useRef } from 'react'
import { fetchChapters, fetchSeriesBySlug } from '../api/series'
import { useChapter } from '../hooks/useChapter'
import { useReadingHistory } from '../hooks/useReadingHistory'
import { saveRichHistory } from '../hooks/useReadingHistoryRich'
import { ChevronLeft, ChevronRight, List, Play, Pause, Maximize, Minimize } from 'lucide-react'
import type { ChapterItem } from '../types'
import { proxyImg } from '../utils/imageProxy'
import Skeleton from '../Components/ui/Skeleton'

export default function Reader({ slug, chapterSlug }: { slug: string; chapterSlug: string }) {
  const chapterIndex = parseInt(chapterSlug || '0')

  const [chapters, setChapters] = useState<ChapterItem[]>([])
  const [loading, setLoading] = useState(true)
  const [chapterListOpen, setChapterListOpen] = useState(false)
  const [autoScrolling, setAutoScrolling] = useState(false)
  const [scrollSpeed, setScrollSpeed] = useState<'slow' | 'medium' | 'fast'>('medium')
  const scrollTimerRef = useRef<ReturnType<typeof setInterval> | null>(null)
  const [navOpen, setNavOpen] = useState(true)
  const [navFloating, setNavFloating] = useState(false)
  const [fullscreen, setFullscreen] = useState(false)

  const { images, loading: pagesLoading, error: pagesError } = useChapter(slug || '', chapterIndex)
  const { markRead } = useReadingHistory()

  const currentIdx = chapters.findIndex((c) => c.index === chapterIndex)
  const current = chapters[currentIdx]
  const prevChapter = chapters[currentIdx + 1]
  const nextChapter = chapters[currentIdx - 1]

  const [seriesMeta, setSeriesMeta] = useState<{ title: string; cover: string } | null>(null)
  const seriesFetchedRef = useRef(false)

  useEffect(() => {
    if (slug && chapterIndex) markRead(slug, chapterIndex)
  }, [slug, chapterIndex, markRead])

  useEffect(() => {
    if (!slug || seriesFetchedRef.current) return
    seriesFetchedRef.current = true
    fetchSeriesBySlug(slug)
      .then((s) => {
        setSeriesMeta({
          title: s.title,
          cover: s.cover || '',
        })
      })
      .catch(() => {})
  }, [slug])

  const savedRef = useRef<string>('')
  useEffect(() => {
    if (!slug || !chapterIndex || !seriesMeta || !current) return
    const key = `${slug}:${chapterIndex}`
    if (savedRef.current === key) return
    savedRef.current = key
    saveRichHistory({
      slug,
      title: seriesMeta.title,
      coverImage: seriesMeta.cover || '',
      chapterIndex,
      chapterTitle: current.title || `Chapter ${chapterIndex}`,
      chapterUrl: `/series/${slug}/chapter/${chapterIndex}`,
    })
  }, [slug, chapterIndex, seriesMeta, current])

  useEffect(() => {
    if (!slug) return
    setLoading(true)
    fetchChapters(slug)
      .then((items) => {
        const sorted = (items || [])
          .sort((a, b) => (b.index || 0) - (a.index || 0))
        setChapters(sorted)
      })
      .catch(() => {})
      .finally(() => setLoading(false))
  }, [slug])

  // Auto-scroll effect
  useEffect(() => {
    if (!autoScrolling) {
      if (scrollTimerRef.current) {
        clearInterval(scrollTimerRef.current)
        scrollTimerRef.current = null
      }
      return
    }

    const speedMap = { slow: 1, medium: 2, fast: 4 }
    const px = speedMap[scrollSpeed]

    scrollTimerRef.current = setInterval(() => {
      const scrollH = document.documentElement.scrollHeight
      const viewH = window.innerHeight
      if (window.scrollY + viewH >= scrollH - 10) {
        setAutoScrolling(false)
        return
      }
      window.scrollBy(0, px)
    }, 30)

    return () => {
      if (scrollTimerRef.current) {
        clearInterval(scrollTimerRef.current)
        scrollTimerRef.current = null
      }
    }
  }, [autoScrolling, scrollSpeed])

  // Stop auto-scroll on chapter change
  useEffect(() => {
    setAutoScrolling(false)
    setNavOpen(true)
  }, [chapterSlug])

  // Detect whether bottom nav should float or sit at bottom
  useEffect(() => {
    const check = () => {
      setNavFloating(
        window.scrollY + window.innerHeight < document.documentElement.scrollHeight - 50
      )
    }
    check()
    window.addEventListener('scroll', check, { passive: true })
    return () => window.removeEventListener('scroll', check)
  }, [])

  // Track fullscreen state
  useEffect(() => {
    const update = () => setFullscreen(!!document.fullscreenElement)
    document.addEventListener('fullscreenchange', update)
    return () => document.removeEventListener('fullscreenchange', update)
  }, [])

  const toggleFullscreen = () => {
    if (document.fullscreenElement) {
      document.exitFullscreen()
    } else {
      document.documentElement.requestFullscreen()
    }
  }

  const cycleSpeed = () => {
    setScrollSpeed((prev) => {
      if (prev === 'slow') return 'medium'
      if (prev === 'medium') return 'fast'
      return 'slow'
    })
  }

  const speedLabel = scrollSpeed === 'slow' ? '1x' : scrollSpeed === 'medium' ? '2x' : '3x'

  if (loading || pagesLoading) {
    return (
      <div className="mx-auto max-w-4xl px-4 py-20">
        <Skeleton className="mb-4 h-6 w-40" />
        <Skeleton className="h-[80vh] w-full" />
      </div>
    )
  }

  if (pagesError || (!current && !loading)) {
    return (
      <div className="mx-auto max-w-4xl px-4 py-20 text-center">
        <p className="text-sm text-[#555]">{pagesError || 'Chapter not found'}</p>
        <Link href={`/series/${slug}`} className="mt-4 inline-flex items-center gap-1.5 text-xs text-[#777] hover:text-white transition-colors">
          <ChevronLeft className="h-3 w-3" /> Back to series
        </Link>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-black">
      {/* Reader Top Bar */}
      <div className={`sticky top-0 z-40 flex h-10 items-center justify-between border-b border-[#2A2A2A] bg-black/95 backdrop-blur px-3 ${navOpen ? '' : 'hidden'}`}>
        <div className="flex items-center gap-2">
          <Link
            href={`/series/${slug}`}
            className="flex items-center gap-1 text-xs text-[#555] transition-colors hover:text-white"
          >
            <ChevronLeft className="h-3 w-3" />
            <span className="hidden sm:inline max-w-[200px] truncate">{slug?.replace(/-/g, ' ')}</span>
          </Link>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={() => setChapterListOpen(!chapterListOpen)}
            className="p-1.5 text-[#555] transition-colors hover:text-white"
            title="Chapter list"
          >
            <List className="h-3.5 w-3.5" />
          </button>

          <span className="text-xs font-medium text-[#999]">
            {current?.title || `Chapter ${chapterIndex}`}
          </span>

          <span className="text-[10px] text-[#555]">
            {currentIdx + 1}/{chapters.length}
          </span>
        </div>

        <div className="flex items-center gap-1">
          {prevChapter ? (
            <Link
              href={`/series/${slug}/chapter/${prevChapter.index}`}
              className="flex items-center gap-1 border border-[#2A2A2A] px-2 py-1 text-[10px] font-medium text-[#777] transition-colors hover:border-white/30 hover:text-white"
            >
              <ChevronLeft className="h-3 w-3" /> Prev
            </Link>
          ) : (
            <span className="flex items-center gap-1 border border-[#2A2A2A] px-2 py-1 text-[10px] font-medium text-[#333]">
              <ChevronLeft className="h-3 w-3" /> Prev
            </span>
          )}

          {nextChapter ? (
            <Link
              href={`/series/${slug}/chapter/${nextChapter.index}`}
              className="flex items-center gap-1 bg-white px-2 py-1 text-[10px] font-bold text-black transition-colors hover:bg-[#ccc]"
            >
              Next <ChevronRight className="h-3 w-3" />
            </Link>
          ) : (
            <span className="flex items-center gap-1 border border-[#2A2A2A] px-2 py-1 text-[10px] font-medium text-[#333]">
              Next <ChevronRight className="h-3 w-3" />
            </span>
          )}
        </div>
      </div>

      {/* Chapter List Dropdown */}
      {chapterListOpen && (
        <div
          className={`fixed left-0 right-0 z-50 max-h-80 overflow-y-auto border-b border-[#2A2A2A] bg-black ${
            navOpen ? 'top-10' : 'top-0'
          }`}
        >
          <div className="mx-auto max-w-4xl px-3 py-2">
            <div className="grid gap-px sm:grid-cols-2 lg:grid-cols-3 bg-[#2A2A2A]">
              {chapters.map((ch, i) => (
                <Link
                  key={ch.index}
                  href={`/series/${slug}/chapter/${ch.index}`}
                  onClick={() => setChapterListOpen(false)}
                  className={`px-3 py-2 text-xs transition-colors ${
                    i === currentIdx
                      ? 'bg-white text-black font-bold'
                      : 'bg-black text-[#777] hover:bg-[#1A1A1A] hover:text-white'
                  }`}
                >
                  {ch.title || `Chapter ${ch.index}`}
                </Link>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Chapter Pages — flush seamless reading */}
      <div
        className="mx-auto max-w-4xl pb-14"
        onClick={() => setNavOpen(!navOpen)}
      >
        {images.map((url, i) => (
          <img
            key={i}
            src={proxyImg(url)}
            alt={`Page ${i + 1}`}
            loading="lazy"
            className="w-full"
          />
        ))}
      </div>

      {/* Bottom Navigation — floating when content overflows */}
      <div
        onClick={() => { if (navFloating) setNavOpen(!navOpen) }}
        className={
          navFloating
            ? 'fixed bottom-0 left-0 right-0 z-30 border-t border-[#2A2A2A] bg-black/95 backdrop-blur'
            : 'border-t border-[#2A2A2A]'
        }
      >
        {navFloating && !navOpen ? (
          /* Collapsed: thin tap handle */
          <div className="flex h-8 cursor-pointer items-center justify-center">
            <div className="h-1 w-8 rounded-full bg-[#333] transition-colors hover:bg-[#555]" />
          </div>
        ) : (
          /* Expanded: Prev / Next + secondary controls */
          <div onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-center gap-3 px-4 py-5">
              {prevChapter ? (
                <Link
                  href={`/series/${slug}/chapter/${prevChapter.index}`}
                  className="flex items-center gap-2 border border-[#2A2A2A] bg-[#111] px-5 py-2 text-xs font-medium text-[#777] transition-colors hover:border-white/30 hover:text-white"
                >
                  <ChevronLeft className="h-3.5 w-3.5" /> Prev
                </Link>
              ) : (
                <span className="flex items-center gap-2 border border-[#2A2A2A] px-5 py-2 text-xs font-medium text-[#333]">
                  <ChevronLeft className="h-3.5 w-3.5" /> Prev
                </span>
              )}

              {nextChapter ? (
                <Link
                  href={`/series/${slug}/chapter/${nextChapter.index}`}
                  className="flex items-center gap-2 bg-white px-5 py-2 text-xs font-bold text-black transition-colors hover:bg-[#ccc]"
                >
                  Next <ChevronRight className="h-3.5 w-3.5" />
                </Link>
              ) : (
                <span className="flex items-center gap-2 border border-[#2A2A2A] px-5 py-2 text-xs font-medium text-[#333]">
                  Next <ChevronRight className="h-3.5 w-3.5" />
                </span>
              )}
            </div>

            {/* Secondary controls — auto-scroll, chapter list */}
            <div className="flex items-center justify-center gap-4 border-t border-[#2A2A2A]/50 px-4 py-2">
              <button
                onClick={() => setAutoScrolling(!autoScrolling)}
                className={`flex items-center gap-1 text-[10px] transition-colors ${
                  autoScrolling ? 'text-white' : 'text-[#555] hover:text-white'
                }`}
              >
                {autoScrolling ? <Pause className="h-3 w-3" /> : <Play className="h-3 w-3" />}
                <span>Auto</span>
              </button>

              {autoScrolling && (
                <button
                  onClick={cycleSpeed}
                  className="text-[10px] font-bold text-[#999] transition-colors hover:text-white"
                >
                  {speedLabel}
                </button>
              )}

              <span className="text-[#2A2A2A]">|</span>

              <button
                onClick={() => setChapterListOpen(!chapterListOpen)}
                className="flex items-center gap-1 text-[10px] text-[#555] transition-colors hover:text-white"
              >
                <List className="h-3 w-3" /> Chapters
              </button>

              <span className="text-[10px] text-[#555]">
                {currentIdx + 1}/{chapters.length}
              </span>

              <span className="text-[#2A2A2A]">|</span>

              <button
                onClick={toggleFullscreen}
                className="flex items-center gap-1 text-[10px] text-[#555] transition-colors hover:text-white"
                title={fullscreen ? 'Exit fullscreen' : 'Enter fullscreen'}
              >
                {fullscreen ? <Minimize className="h-3 w-3" /> : <Maximize className="h-3 w-3" />}
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
