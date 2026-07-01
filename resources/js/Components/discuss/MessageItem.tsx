import { useState, type FC } from 'react'
import ReactionBar from './ReactionBar'
import EmojiText from '../../lib/emoji-renderer'
import type { GroupedReaction } from './ReactionBar'

interface MessageUser {
  id: string
  display_name: string
  avatar_url: string | null
  rank?: { name: string; color: string }
}

interface ReplyTo {
  id: string
  body: string
  user: { display_name: string; avatar_url?: string | null }
}

interface Message {
  id: string
  body: string | null
  type: 'text' | 'image' | 'sticker' | 'system'
  user: MessageUser
  reply_to?: ReplyTo
  reply_count: number
  attachments: string[]
  reactions: GroupedReaction[]
  is_edited: boolean
  is_deleted: boolean
  created_at: string
}

interface MessageItemProps {
  message: Message
  currentUserId: string
  onReply: (msg: Message) => void
  onDelete: (id: string) => void
  onReact: (msgId: string, emoteId: string) => void
  showAvatar?: boolean
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

function formatTime(iso: string): string {
  const d = new Date(iso)
  return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const MessageItem: FC<MessageItemProps> = ({ message, currentUserId, onReply, onDelete, onReact, showAvatar = true }) => {
  const [showActions, setShowActions] = useState(false)
  const isOwner = message.user?.id === currentUserId

  if (message.type === 'system') {
    const isJoin = message.body?.toLowerCase().includes('added') || message.body?.toLowerCase().includes('joined')
    return (
      <div className="flex flex-col items-center gap-stack-xs py-2">
        <div className="flex items-center gap-2 rounded-full bg-surface-container px-4 py-1.5">
          <SyIcon name={isJoin ? 'person_add' : 'logout'} className="text-on-surface-variant text-[16px]" />
          <p className="font-body-sm text-body-sm text-on-surface">{message.body}</p>
        </div>
        <span className="font-label-sm text-label-sm text-on-surface-variant">{formatTime(message.created_at)}</span>
      </div>
    )
  }

  if (message.is_deleted) {
    return (
      <div className="flex gap-2 group py-1">
        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-surface-container-highest">
          <SyIcon name="block" className="text-on-surface-variant text-[14px]" />
        </div>
        <div className="flex-1 pt-1.5">
          <p className="font-body-sm text-body-sm italic text-on-surface-variant">Message deleted</p>
        </div>
      </div>
    )
  }

  const avatarFallback = message.user.display_name?.charAt(0).toUpperCase() || '?'
  const showHeader = !isOwner && showAvatar
  const bodyText = message.body?.trim() || ''
  const isLongBody = bodyText.length > 40
  const sizeClass = isLongBody ? 'w-[80%]' : 'w-[90%]'
  const emojiOnly = !message.reply_to && bodyText.length > 0 && bodyText.length <= 10 && /^(\p{Extended_Pictographic}|\uFE0F|\u200D|\u20E3|\p{Emoji_Modifier_Base}|\p{RI})+$/u.test(bodyText)

  return (
    <div
      className={`group transition-colors ${showAvatar ? 'py-1' : 'py-0.5'}`}
      onMouseEnter={() => setShowActions(true)}
      onMouseLeave={() => setShowActions(false)}
    >
      {/* Own message — right aligned, no avatar */}
      {isOwner ? (
        <div className={`flex flex-col items-end gap-stack-xs self-end ${sizeClass} ml-auto`}>
          {emojiOnly ? (
            <EmojiText text={bodyText} size="3rem" className="leading-none" />
          ) : (
            <div className="message-bubble-self px-bubble-padding-x py-bubble-padding-y relative">
              {/* Reply preview inside bubble */}
              {message.reply_to && (
                <div className="mb-2 border-l-2 border-[#555] bg-[#2C2C2C] px-2 py-1">
                  <p className="text-[10px] font-medium text-[#aaa] leading-none mb-0.5">{message.reply_to.user.display_name}</p>
                  <p className="text-[10px] text-[#777] leading-tight truncate">{message.reply_to.body}</p>
                </div>
              )}

              {/* Body */}
              {message.body && (
                <EmojiText text={message.body} className="font-body-md pr-1" />
              )}

              {/* Image attachments */}
              {message.attachments.length > 0 && (
                <div className="mt-1 flex flex-wrap gap-1">
                  {message.attachments.map((url, i) => (
                    <a key={i} href={url} target="_blank" rel="noopener noreferrer">
                      <img src={url} alt={`attachment ${i + 1}`} className="max-w-[240px] object-cover" />
                    </a>
                  ))}
                </div>
              )}
            </div>
          )}

          {/* Footer: edited badge + time + checkmark + reply count */}
          <div className="flex items-center gap-1 mr-1">
            {message.is_edited && (
              <span className="font-label-sm text-label-sm text-on-surface-variant">(edited)</span>
            )}
            <span className="font-label-sm text-label-sm text-on-surface-variant">{formatTime(message.created_at)}</span>
            <SyIcon name="done_all" className="text-primary text-[16px]" />
            {message.reply_count > 0 && (
              <>
                <span className="text-outline-variant">·</span>
                <span className="font-label-sm text-label-sm text-on-surface-variant">
                  {message.reply_count} {message.reply_count === 1 ? 'reply' : 'replies'}
                </span>
              </>
            )}
          </div>

          {/* Reactions */}
          <ReactionBar
            reactions={message.reactions}
            onToggle={(emoteId) => onReact(message.id, emoteId)}
            currentUserId={currentUserId}
          />

          {/* Action bar */}
          {showActions && (
            <div className="flex gap-2 mr-1">
              <button onClick={() => onReply(message)} className="transition-colors hover:text-on-surface" title="Reply">
                <SyIcon name="reply" className="text-on-surface-variant text-[16px]" />
              </button>
              <button onClick={() => onReact(message.id, '')} className="transition-colors hover:text-on-surface" title="React">
                <SyIcon name="add_reaction" className="text-on-surface-variant text-[16px]" />
              </button>
              <button onClick={() => onDelete(message.id)} className="transition-colors hover:text-error" title="Delete">
                <SyIcon name="delete" className="text-on-surface-variant text-[16px]" />
              </button>
            </div>
          )}
        </div>
      ) : (
        /* Other's message — left aligned, with avatar */
        <div className={`flex gap-3 ${sizeClass}`}>
          {/* Avatar column */}
          {showAvatar ? (
            <div className="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-surface-container-highest">
              {message.user.avatar_url ? (
                <img src={message.user.avatar_url} alt="" className="h-full w-full object-cover" />
              ) : (
                <span className="font-label-sm text-label-sm text-on-surface font-bold">{avatarFallback}</span>
              )}
            </div>
          ) : (
            <div className="w-8 shrink-0" />
          )}

          <div className="min-w-0 flex-1 flex flex-col gap-stack-xs">
            {/* Header — only for other's first message */}
            {showHeader && (
              <span className="font-label-md text-label-md text-on-surface-variant ml-1">{message.user.display_name}</span>
            )}

            {emojiOnly ? (
              <EmojiText text={bodyText} size="3rem" className="leading-none" />
            ) : (
              <div className="message-bubble-other px-bubble-padding-x py-bubble-padding-y">
                {/* Reply preview inside bubble */}
                {message.reply_to && (
                  <div className="mb-1 border-l-2 border-[#666] bg-[#343434] px-2 py-1">
                    <p className="text-[10px] font-medium text-[#aaa] leading-none mb-0.5">{message.reply_to.user.display_name}</p>
                    <p className="text-[10px] text-[#777] leading-tight truncate">{message.reply_to.body}</p>
                  </div>
                )}

                {/* Body */}
                {message.body && (
                  <EmojiText text={message.body} className="font-body-md text-on-surface" />
                )}

                {/* Image attachments */}
                {message.attachments.length > 0 && (
                  <div className="mt-1 flex flex-wrap gap-1">
                    {message.attachments.map((url, i) => (
                      <a key={i} href={url} target="_blank" rel="noopener noreferrer">
                        <img src={url} alt={`attachment ${i + 1}`} className="max-w-[240px] object-cover" />
                      </a>
                    ))}
                  </div>
                )}
              </div>
            )}

            {/* Footer: edited badge + time + reply count */}
            <div className="flex items-center gap-1 ml-1">
              {message.is_edited && (
                <span className="font-label-sm text-label-sm text-on-surface-variant">(edited)</span>
              )}
              <span className="font-label-sm text-label-sm text-on-surface-variant">{formatTime(message.created_at)}</span>
              {message.reply_count > 0 && (
                <>
                  <span className="text-outline-variant">·</span>
                  <span className="font-label-sm text-label-sm text-on-surface-variant">
                    {message.reply_count} {message.reply_count === 1 ? 'reply' : 'replies'}
                  </span>
                </>
              )}
            </div>

            {/* Reactions */}
            <ReactionBar
              reactions={message.reactions}
              onToggle={(emoteId) => onReact(message.id, emoteId)}
              currentUserId={currentUserId}
            />

            {/* Action bar */}
            {showActions && (
              <div className="flex gap-2 ml-1">
                <button onClick={() => onReply(message)} className="transition-colors hover:text-on-surface" title="Reply">
                  <SyIcon name="reply" className="text-on-surface-variant text-[16px]" />
                </button>
                <button onClick={() => onReact(message.id, '')} className="transition-colors hover:text-on-surface" title="React">
                  <SyIcon name="add_reaction" className="text-on-surface-variant text-[16px]" />
                </button>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  )
}

export type { Message, MessageUser, ReplyTo, GroupedReaction }
export default MessageItem
