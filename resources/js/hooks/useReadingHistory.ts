import { useState, useEffect, useRef, useCallback } from 'react'

const STORAGE_KEY = 'cosmic_reading_history'

export type ReadingHistory = Record<string, number[]>

function load(): ReadingHistory {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? JSON.parse(raw) : {}
  } catch {
    return {}
  }
}

function save(data: ReadingHistory) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(data))
}

export function useReadingHistory() {
  const cache = useRef<ReadingHistory>(load())
  const [version, setVersion] = useState(0)

  useEffect(() => {
    const handler = () => {
      cache.current = load()
      setVersion((v) => v + 1)
    }
    window.addEventListener('storage', handler)
    return () => window.removeEventListener('storage', handler)
  }, [])

  const getReadSet = useCallback(
    (slug: string): Set<number> => new Set(cache.current[slug] || []),
    [version]
  )

  const markRead = useCallback((slug: string, index: number) => {
    const data = load()
    const list = data[slug] || []
    if (!list.includes(index)) {
      list.push(index)
      data[slug] = list
      save(data)
      cache.current = data
      setVersion((v) => v + 1)
    }
  }, [])

  return { getReadSet, markRead }
}
