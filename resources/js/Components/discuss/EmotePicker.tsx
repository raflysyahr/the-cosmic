import { useState, useRef, useEffect, type FC } from 'react'

interface Emote {
  id: string
  code: string
  image_url: string
}

interface EmotePickerProps {
  emotes: Emote[]
  onSelect: (emote: Emote) => void
  onClose: () => void
}

function SyIcon({ name, className = '' }: { name: string; className?: string }) {
  return (
    <span
      className={`material-symbols-outlined ${className}`}
      style={{ fontVariationSettings: "'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 20" }}
    >
      {name}
    </span>
  )
}

const EmotePicker: FC<EmotePickerProps> = ({ emotes, onSelect, onClose }) => {
  const [query, setQuery] = useState('')
  const ref = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) {
        onClose()
      }
    }
    document.addEventListener('mousedown', handler)
    return () => document.removeEventListener('mousedown', handler)
  }, [onClose])

  const filtered = query.trim()
    ? emotes.filter((e) => e.code.toLowerCase().includes(query.toLowerCase()))
    : emotes

  return (
    <div
      ref={ref}
      className="absolute bottom-full left-0 mb-2 border border-outline-variant bg-surface-container shadow-lg z-50"
    >
      <div className="flex items-center gap-2 border-b border-outline-variant px-3 py-2">
        <SyIcon name="search" className="text-on-surface-variant text-[16px]" />
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Search emotes..."
          className="w-full bg-transparent text-xs text-on-surface placeholder:text-on-surface-variant focus:outline-none"
        />
      </div>
      <div className="grid grid-cols-8 gap-1 p-2">
        {filtered.map((emote) => (
          <button
            key={emote.id}
            onClick={() => onSelect(emote)}
            className="flex items-center justify-center rounded p-1 transition-colors hover:bg-surface-container-higher"
          >
            <img src={emote.image_url} alt={emote.code} className="h-7 w-7 object-contain" />
          </button>
        ))}
      </div>
    </div>
  )
}

export default EmotePicker
