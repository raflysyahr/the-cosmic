import { useState, useRef, useEffect, useMemo, type FC } from 'react'
import * as FluentEmoji from 'react-fluentui-emoji/lib/flat'
import { emojiList, searchEmoji, getEmojiByGroup, GROUP_LABELS, type EmojiGroup } from '../../lib/emoji-data'

interface EmojiPickerProps {
  onSelect: (unicode: string) => void
  onClose: () => void
}

const GROUPS = Object.keys(GROUP_LABELS) as EmojiGroup[]

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

const EmojiPicker: FC<EmojiPickerProps> = ({ onSelect, onClose }) => {
  const [query, setQuery] = useState('')
  const [activeGroup, setActiveGroup] = useState<EmojiGroup>(GROUPS[0])
  const ref = useRef<HTMLDivElement>(null)
  const gridRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) {
        onClose()
      }
    }
    document.addEventListener('mousedown', handler)
    return () => document.removeEventListener('mousedown', handler)
  }, [onClose])

  useEffect(() => {
    const handleKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose()
    }
    document.addEventListener('keydown', handleKey)
    return () => document.removeEventListener('keydown', handleKey)
  }, [onClose])

  const displayed = useMemo(() => {
    if (query.trim()) {
      return searchEmoji(query.trim())
    }
    return getEmojiByGroup(activeGroup)
  }, [query, activeGroup])

  const handleEmojiClick = (glyph: string) => {
    onSelect(glyph)
  }

  const FluentIcon = ({ componentName }: { componentName: string }) => {
    const IconComponent = (FluentEmoji as Record<string, React.ComponentType<{ size?: number | string }>>)[componentName]
    if (!IconComponent) {
      return null
    }
    return <IconComponent size="1.5rem" className="pointer-events-none" />
  }

  return (
    <div
      ref={ref}
      className="w-full bg-surface-container overflow-hidden"
    >
      {/* Search */}
      <div className="flex items-center gap-2 border-b border-outline-variant px-3 py-2">
        <SyIcon name="search" className="text-on-surface-variant text-[16px]" />
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Search emoji..."
          className="w-full bg-transparent text-xs text-on-surface placeholder:text-on-surface-variant focus:outline-none"
        />
      </div>

      {/* Category tabs */}
      {!query.trim() && (
        <div className="flex gap-1 overflow-x-auto border-b border-outline-variant px-2 py-1.5 scrollbar-hide">
          {GROUPS.map((group) => (
            <button
              key={group}
              onClick={() => setActiveGroup(group)}
              title={GROUP_LABELS[group].label}
              className={`flex items-center justify-center rounded-lg px-2 py-1 transition-colors ${
                activeGroup === group
                  ? 'bg-primary-container text-on-primary-container'
                  : 'text-on-surface-variant hover:bg-surface-container-higher'
              }`}
            >
              <SyIcon
                name={GROUP_LABELS[group].icon}
                className="text-[16px]"
              />
            </button>
          ))}
        </div>
      )}

      {/* Emoji grid */}
      <div ref={gridRef} className="h-[260px] overflow-y-auto p-2 scrollbar-hide">
        <div className="grid grid-cols-8 gap-0.5">
          {displayed.map((emoji) => (
            <button
              key={emoji.component + emoji.unicode}
              onClick={() => handleEmojiClick(emoji.glyph)}
              className="flex items-center justify-center rounded-md p-1 transition-colors hover:bg-surface-container-higher active:scale-110"
              title={emoji.name}
            >
              <FluentIcon componentName={emoji.component} />
            </button>
          ))}
          {displayed.length === 0 && (
            <p className="col-span-8 py-4 text-center font-body-sm text-body-sm text-on-surface-variant">
              No emoji found
            </p>
          )}
        </div>
      </div>
    </div>
  )
}

export default EmojiPicker
