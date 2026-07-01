import { useState, useCallback } from 'react'

const STORAGE_KEY = 'cosmic_bookmarks'

export interface BookmarkItem {
  slug: string
  title: string
  coverImage?: string
  format: string
  addedAt: string
}

function load(): BookmarkItem[] {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? JSON.parse(raw) : []
  } catch {
    return []
  }
}

function save(items: BookmarkItem[]): void {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(items))
}

export function useBookmarks() {
  const [items, setItems] = useState<BookmarkItem[]>(load)

  const isBookmarked = useCallback(
    (slug: string) => items.some((b) => b.slug === slug),
    [items]
  )

  const add = useCallback(
    (item: Omit<BookmarkItem, 'addedAt'>) => {
      const all = load()
      if (!all.some((b) => b.slug === item.slug)) {
        all.unshift({ ...item, addedAt: new Date().toISOString() })
        save(all)
        setItems(all)
      }
    },
    []
  )

  const remove = useCallback((slug: string) => {
    const all = load().filter((b) => b.slug !== slug)
    save(all)
    setItems(all)
  }, [])

  const toggle = useCallback(
    (item: Omit<BookmarkItem, 'addedAt'>) => {
      if (items.some((b) => b.slug === item.slug)) {
        remove(item.slug)
      } else {
        add(item)
      }
    },
    [items, add, remove]
  )

  return { items, isBookmarked, add, remove, toggle }
}
