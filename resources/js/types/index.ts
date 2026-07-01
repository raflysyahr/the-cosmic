export interface LatestChapter {
  index: number
  title: string
  releasedAt: string
}

export interface SeriesItem {
  id: number
  title: string
  slug: string
  cover: string
  rating: number
  status: string
  type: string
  isHot: boolean
  totalChapters: number
  genreIds?: number[]
  latestChapter?: LatestChapter
}

export interface SeriesDetail {
  id: number
  title: string
  slug: string
  cover: string
  rating: number
  status: string
  type: string
  isHot: boolean
  totalChapters: number
  genreIds?: number[]
  nativeTitle: string
  author: string
  genres: Genre[]
  animeAdaptation: boolean
  synopsis: string
  releasedAt: string
  views: number
  chapters: ChapterItem[]
}

export interface ChapterItem {
  index: number
  title: string
  releasedAt: string
}

export interface ChapterPages {
  images: string[]
}

export interface Genre {
  id: number
  name: string
  slug?: string
}

export interface PaginationMeta {
  total: number
  page: number
  lastPage: number
}

export interface ApiResponse<T> {
  success: boolean
  data: T
  meta?: PaginationMeta
}
