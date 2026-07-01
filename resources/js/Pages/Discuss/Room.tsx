import { useState, useEffect, useCallback } from 'react'
import { router } from '@inertiajs/react'
import MessageList from '../../Components/discuss/MessageList'
import MessageInput from '../../Components/discuss/MessageInput'
import EmojiPicker from '../../Components/discuss/EmojiPicker'
import MemberSidebar from '../../Components/discuss/MemberSidebar'
import type { Message } from '../../Components/discuss/MessageItem'
import { apiFetch } from '../../api/fetch'
import { proxyImg } from '../../utils/imageProxy'

interface RoomData {
  id: string
  slug: string
  name: string
  cover_url: string | null
  online_count: number
}

interface Member {
  userId: string
  displayName: string
  avatarUrl: string | null
  role: 'member' | 'moderator' | 'admin'
  rank: { name: string; color: string } | null
  xpPoints: number
  isOnline: boolean
}

interface PageProps {
  room: RoomData
  messages: Message[]
  members: Member[]
  currentUserId: string
}

function Icon({ name, className = '' }: { name: string; className?: string }) {
  return (
    <span className={`material-symbols-outlined ${className}`} style={{ fontVariationSettings: "'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24" }}>
      {name}
    </span>
  )
}

export default function DiscussRoom(props: PageProps) {
  const [messages, setMessages] = useState<Message[]>(props.messages ?? [])
  const [members, setMembers] = useState<Member[]>(props.members ?? [])
  const [replyTo, setReplyTo] = useState<Message | undefined>(undefined)
  const [showSidebar, setShowSidebar] = useState(false)
  const [showEmoji, setShowEmoji] = useState(false)
  const [emojiInsert, setEmojiInsert] = useState<{ unicode: string; id: number } | null>(null)
  const [loading, setLoading] = useState(false)
  const currentUserId = props.currentUserId

  const room = props.room

  const handleSend = useCallback((body: string, replyToId?: string) => {
    const payload: Record<string, unknown> = { body }
    if (replyToId) payload.reply_to_id = replyToId
    apiFetch(`/api/rooms/${room.slug}/messages`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
      .then((r) => { if (!r.ok) throw new Error('send failed'); return r.json() })
      .then((res: { message: Message }) => {
        setMessages((prev) => [...prev, res.message])
        setReplyTo(undefined)
      })
      .catch(() => {})
  }, [room.slug])

  const handleReply = useCallback((msg: Message) => {
    setReplyTo(msg)
  }, [])

  const handleCancelReply = useCallback(() => {
    setReplyTo(undefined)
  }, [])

  const handleEmojiSelect = useCallback((unicode: string) => {
    setEmojiInsert({ unicode, id: Date.now() })
  }, [])

  const handleDelete = useCallback((messageId: string) => {
    apiFetch(`/api/rooms/${room.slug}/messages/${messageId}`, { method: 'DELETE' })
      .then((r) => { if (!r.ok) throw new Error('delete failed') })
      .then(() => {
        setMessages((prev) =>
          prev.map((m) => (m.id === messageId ? { ...m, is_deleted: true } : m)),
        )
      })
      .catch(() => {})
  }, [room.slug])

  const handleReact = useCallback((messageId: string, emoteId: string) => {
    apiFetch(`/api/rooms/${room.slug}/messages/${messageId}/reactions`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ emote_id: emoteId }),
    }).catch(() => {})
  }, [room.slug])

  const handleKick = useCallback((userId: string) => {
    apiFetch(`/api/rooms/${room.slug}/members/${userId}/kick`, { method: 'POST' }).catch(() => {})
  }, [room.slug])

  const handleMute = useCallback((userId: string) => {
    apiFetch(`/api/rooms/${room.slug}/members/${userId}/mute`, { method: 'POST' }).catch(() => {})
  }, [room.slug])

  const handleBan = useCallback((userId: string) => {
    apiFetch(`/api/rooms/${room.slug}/members/${userId}/ban`, { method: 'POST' }).catch(() => {})
  }, [room.slug])

  useEffect(() => {
    apiFetch(`/api/rooms/${room.slug}/messages?take=50`)
      .then((r) => { if (!r.ok) throw new Error('fetch messages failed'); return r.json() })
      .then((res: { data: Message[] }) => setMessages(res.data.reverse()))
      .catch(() => {})
      .finally(() => setLoading(false))
  }, [room.slug])

  useEffect(() => {
    apiFetch(`/api/rooms/${room.slug}/members`)
      .then((r) => { if (!r.ok) throw new Error('fetch members failed'); return r.json() })
      .then((m: Member[]) => setMembers(m))
      .catch(() => {})
  }, [room.slug])

  useEffect(() => {
    if (!room.id || !window.Echo) return
    const channel = window.Echo.join(`room.${room.id}`)

    channel.listen('MessageSent', (e: { message: Message }) => {
      setMessages((prev) => [...prev, e.message])
    })

    channel.listen('MessageDeleted', (e: { message_id: string }) => {
      setMessages((prev) =>
        prev.map((m) => (m.id === e.message_id ? { ...m, is_deleted: true } : m)),
      )
    })

    channel.listen('MessageEdited', (e: { message_id: string; body: string }) => {
      setMessages((prev) =>
        prev.map((m) => (m.id === e.message_id ? { ...m, body: e.body, is_edited: true } : m)),
      )
    })

    channel.here((users: { id: string; display_name: string }[]) => {
      setMembers((prev) => prev.map((m) => ({ ...m, isOnline: users.some((u) => u.id === m.userId) })))
    })

    channel.joining((user: { id: string }) => {
      setMembers((prev) => prev.map((m) => (m.userId === user.id ? { ...m, isOnline: true } : m)))
    })

    channel.leaving((user: { id: string }) => {
      setMembers((prev) => prev.map((m) => (m.userId === user.id ? { ...m, isOnline: false } : m)))
    })

    return () => {
      window.Echo?.leave(`room.${room.id}`)
    }
  }, [room.id])

  const onlineCount = members.filter((m) => m.isOnline).length

  return (
    <div className="mx-auto flex min-h-screen max-w-md flex-col bg-surface shadow-2xl">
      {/* Top AppBar */}
      <header className="fixed top-0 z-50 flex w-full max-w-md items-center justify-between border-b border-outline-variant bg-surface px-gutter-md h-14">
        <div className="flex items-center gap-3">
          <button
            onClick={() => router.visit('/discuss')}
            className="active:opacity-70 transition-opacity p-1 -ml-1"
          >
            <Icon name="arrow_back" className="text-primary" />
          </button>
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-surface-container-high">
              {room.cover_url ? (
                <img src={proxyImg(room.cover_url)} alt="" className="h-full w-full object-cover" />
              ) : (
                <Icon name="groups" className="text-on-surface-variant" />
              )}
            </div>
            <div className="flex flex-col w-[160px]">
              <h1 className="font-headline-sm-mobile truncate text-headline-sm-mobile text-primary leading-tight">{room.name}</h1>
              <span className="font-label-sm text-label-sm text-on-surface-variant">
                {members.length} members, {onlineCount} online
              </span>
            </div>
          </div>
        </div>
        <div className="flex items-center gap-1">



          <button className="p-2 hover:bg-surface-container-high transition-colors rounded-full">
            <Icon name="more_vert" className="text-on-surface-variant" />
          </button>






        </div>
      </header>

      {/* Pinned Message (sample UI) */}
      <div className="fixed top-14 left-0 right-0 z-40 mx-auto flex max-w-md items-center gap-3 border-b border-outline-variant bg-surface-container-low px-gutter-md py-3">
        <Icon name="campaign" className="text-secondary text-[20px]" />
        <div className="min-w-0 flex-1 overflow-hidden">
          <p className="mb-0.5 font-label-sm text-label-sm uppercase tracking-wider text-secondary">
            Pinned Message
          </p>
          <p className="truncate font-body-sm text-body-sm text-on-surface">
            Welcome to {room.name}! Check the rules before chatting.
          </p>
        </div>
        <Icon name="push_pin" className="text-on-surface-variant text-[18px]" style={{ fontVariationSettings: "'FILL' 1" }} />
      </div>

      {/* Content Canvas */}
      <main className={`flex-1 pt-[112px] px-gutter-md flex flex-col gap-stack-md overflow-y-auto scrollbar-hide ${
        showEmoji ? 'pb-[420px]' : 'pb-[84px]'
      }`}>
        <MessageList
          messages={messages}
          currentUserId={currentUserId}
          onReply={handleReply}
          onDelete={handleDelete}
          onReact={handleReact}
          loading={loading}
        />
      </main>

      {/* Bottom drawer: input + emoji picker */}
      <div className="fixed bottom-0 left-0 right-0 z-50 mx-auto max-w-md">
        {/* Message input */}
        <nav className="flex h-[72px] items-center gap-stack-sm border-t border-outline-variant bg-surface-container-low px-gutter-md">
          <MessageInput
            onSend={handleSend}
            replyTo={replyTo}
            onCancelReply={handleCancelReply}
            showEmoji={showEmoji}
            toggleEmoji={() => setShowEmoji((p) => !p)}
            emojiInsert={emojiInsert}
          />
        </nav>

        {/* Emoji picker - below input when open */}
        {showEmoji && (
          <div className="border-t border-outline-variant bg-surface-container">
            <EmojiPicker onSelect={handleEmojiSelect} onClose={() => setShowEmoji(false)} />
          </div>
        )}
      </div>

      {/* Sidebar (overlay on mobile, side panel on desktop) */}
      {showSidebar && (
        <>
          <div
            className="fixed inset-0 z-40 bg-black/50 md:hidden"
            onClick={() => setShowSidebar(false)}
          />
          <div className="fixed right-0 top-0 z-50 h-full md:relative md:z-auto">
            <MemberSidebar
              members={members}
              currentUserId={currentUserId}
              onKick={handleKick}
              onMute={handleMute}
              onBan={handleBan}
            />
          </div>
        </>
      )}
    </div>
  )
}
