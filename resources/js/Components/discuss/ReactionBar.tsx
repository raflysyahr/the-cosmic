import { type FC } from 'react'

export interface GroupedReaction {
  emoteId: string
  emoteCode: string
  imageUrl: string
  count: number
  userIds: string[]
}

interface ReactionBarProps {
  reactions: GroupedReaction[]
  onToggle: (emoteId: string) => void
  currentUserId: string
}

const ReactionBar: FC<ReactionBarProps> = ({ reactions, onToggle, currentUserId }) => {
  if (reactions.length === 0) return null

  return (
    <div className="flex flex-wrap gap-1">
      {reactions.map((r) => {
        const isReacted = r.userIds.includes(currentUserId)
        return (
          <button
            key={r.emoteId}
            onClick={() => onToggle(r.emoteId)}
            className={`flex items-center gap-1 border px-2 py-0.5 text-xs transition-colors ${
              isReacted
                ? 'border-white/40 bg-surface-container text-on-surface'
                : 'border-outline-variant text-on-surface-variant hover:border-white/20'
            }`}
          >
            <img src={r.imageUrl} alt={r.emoteCode} className="h-3.5 w-3.5 object-contain" />
            <span>{r.count}</span>
          </button>
        )
      })}
    </div>
  )
}

export default ReactionBar
