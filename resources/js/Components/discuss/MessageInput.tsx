import { useState, useRef, useEffect, type FC, type KeyboardEvent } from 'react'

interface ReplyTo {
  id: string
  body: string
  user: { display_name: string; avatar_url?: string | null }
}

interface MessageInputProps {
  onSend: (body: string, replyToId?: string) => void
  replyTo?: ReplyTo
  onCancelReply: () => void
  disabled?: boolean
  disabledReason?: string
  showEmoji?: boolean
  toggleEmoji?: () => void
  emojiInsert?: { unicode: string; id: number } | null
}

function SyIcon({ name, className = '' }: { name: string; className?: string }) {
  return (
    <span
      className={`material-symbols-outlined ${className}`}
      style={{ fontVariationSettings: "'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24" }}
    >
      {name}
    </span>
  )
}

const MessageInput: FC<MessageInputProps> = ({ onSend, replyTo, onCancelReply, disabled, disabledReason, showEmoji, toggleEmoji, emojiInsert }) => {
  const [body, setBody] = useState('')
  const inputRef = useRef<HTMLInputElement>(null)
  const prevInsertId = useRef(0)

  useEffect(() => {
    inputRef.current?.focus()
  }, [])

  // Handle emoji insert from parent
  useEffect(() => {
    if (emojiInsert && emojiInsert.id !== prevInsertId.current) {
      prevInsertId.current = emojiInsert.id
      const input = inputRef.current
      const start = input?.selectionStart ?? body.length
      const end = input?.selectionEnd ?? start
      setBody((prev) => prev.slice(0, start) + emojiInsert.unicode + prev.slice(end))
      requestAnimationFrame(() => {
        input?.focus()
        input?.setSelectionRange(start + emojiInsert.unicode.length, start + emojiInsert.unicode.length)
      })
    }
  }, [emojiInsert])

  const handleSend = () => {
    const text = body.trim()
    if (!text || disabled) return
    onSend(text, replyTo?.id)
    setBody('')
    if (replyTo) onCancelReply()
    inputRef.current?.focus()
  }

  const handleKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      e.preventDefault()
      handleSend()
    }
  }

  const replyAvatarFallback = replyTo?.user.display_name?.charAt(0).toUpperCase() || '?'

  return (
    <>
      {/* Reply preview bar */}
      {replyTo && (
        <div className="absolute bottom-full left-0 right-0 flex items-center gap-2 border-b border-outline-variant bg-surface-container-low px-4 py-2">
          <div className="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-surface-container-highest">
            {replyTo.user.avatar_url ? (
              <img src={replyTo.user.avatar_url} alt="" className="h-full w-full rounded-full object-cover" />
            ) : (
              <span className="text-[9px] text-on-surface-variant">{replyAvatarFallback}</span>
            )}
          </div>
          <p className="min-w-0 flex-1 truncate font-body-sm text-body-sm text-on-surface-variant">
            <span className="font-medium text-on-surface-variant">@{replyTo.user.display_name}</span>: {replyTo.body}
          </p>
          <button onClick={onCancelReply} className="shrink-0 transition-colors hover:text-on-surface">
            <SyIcon name="close" className="text-on-surface-variant text-[16px]" />
          </button>
        </div>
      )}

      {disabled ? (
        <p className="font-body-sm text-body-sm text-on-surface-variant">{disabledReason ?? 'You cannot send messages right now.'}</p>
      ) : (
        <>
          {/* Attach button */}
          <button className="flex items-center justify-center p-2 transition-colors rounded-full hover:bg-surface-container-higher active:scale-95 text-on-surface-variant">
            <SyIcon name="attach_file" />
          </button>

          {/* Input field */}
          <div className="flex flex-1 items-center rounded-full bg-surface-container-high px-4 py-2">
            <input
              ref={inputRef}
              type="text"
              value={body}
              onChange={(e) => setBody(e.target.value)}
              onKeyDown={handleKeyDown}
              placeholder="Type a message..."
              className="w-full border-none bg-transparent p-0 font-body-md text-body-md text-on-surface placeholder:text-on-surface-variant focus:ring-0"
              onFocus={(e) => {
                (e.target.parentElement as HTMLElement)?.classList.add('ring-1', 'ring-outline')
              }}
              onBlur={(e) => {
                (e.target.parentElement as HTMLElement)?.classList.remove('ring-1', 'ring-outline')
              }}
            />
          </div>

          {/* Emoji picker trigger */}
          <button
            onClick={toggleEmoji}
            className={`flex items-center justify-center p-2 transition-colors rounded-full hover:bg-surface-container-higher active:scale-95 ${
              showEmoji ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant'
            }`}
          >
            <SyIcon name="emoji_emotions" />
          </button>

          {/* Send button */}
          <button
            onClick={handleSend}
            disabled={!body.trim()}
            className="flex items-center justify-center rounded-full bg-primary p-2 transition-transform active:scale-95 disabled:opacity-50"
          >
            <SyIcon name="send" className="text-on-primary" style={{ fontVariationSettings: "'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24" }} />
          </button>
        </>
      )}
    </>
  )
}

export default MessageInput
