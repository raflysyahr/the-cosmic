import { type FC } from 'react'
import { Shield, Crown, UserMinus, MicOff, Ban } from 'lucide-react'

interface RankInfo {
  name: string
  color: string
}

interface Member {
  userId: string
  displayName: string
  avatarUrl: string | null
  role: 'member' | 'moderator' | 'admin'
  rank: RankInfo | null
  xpPoints: number
  isOnline: boolean
}

interface MemberSidebarProps {
  members: Member[]
  currentUserId: string
  onKick: (userId: string) => void
  onMute: (userId: string) => void
  onBan: (userId: string) => void
}

const roleOrder = { admin: 0, moderator: 1, member: 2 }
const roleLabel = { admin: 'Admin', moderator: 'Moderator', member: 'Member' }

const MemberSidebar: FC<MemberSidebarProps> = ({ members, currentUserId, onKick, onMute, onBan }) => {
  const sorted = [...members].sort((a, b) => roleOrder[a.role] - roleOrder[b.role])

  let lastRole: string | null = null

  return (
    <div className="w-64 border-l border-[#2A2A2A] bg-[#0a0a0a]">
      <div className="border-b border-[#2A2A2A] px-4 py-3">
        <span className="text-xs uppercase tracking-wider text-[#555]">
          MEMBERS ({members.length})
        </span>
      </div>
      <div className="overflow-y-auto">
        {sorted.map((m) => {
          const showLabel = m.role !== lastRole
          lastRole = m.role
          const avatarFallback = m.displayName?.charAt(0).toUpperCase() || '?'
          const isSelf = m.userId === currentUserId
          const currentUser = members.find((x) => x.userId === currentUserId)
          const canModerate = currentUser && (currentUser.role === 'admin' || currentUser.role === 'moderator') && !isSelf

          return (
            <div key={m.userId}>
              {showLabel && (
                <div className="px-4 pt-3 pb-1">
                  <span className="text-[10px] uppercase tracking-wider text-[#555]">
                    {roleLabel[m.role]}
                  </span>
                </div>
              )}
              <div className="group flex items-center gap-3 px-4 py-2 transition-colors hover:bg-[#111]">
                {/* Avatar */}
                <div className="relative shrink-0">
                  <div className="flex h-7 w-7 items-center justify-center overflow-hidden rounded-full bg-[#1A1A1A]">
                    {m.avatarUrl ? (
                      <img src={m.avatarUrl} alt="" className="h-full w-full object-cover" />
                    ) : (
                      <span className="text-[10px] text-[#555]">{avatarFallback}</span>
                    )}
                  </div>
                  <div
                    className={`absolute -bottom-0.5 -right-0.5 h-2 w-2 rounded-full border border-[#0a0a0a] ${
                      m.isOnline ? 'bg-white' : 'bg-[#333]'
                    }`}
                  />
                </div>

                {/* Name + rank */}
                <div className="min-w-0 flex-1">
                  <div className="flex items-center gap-1.5">
                    <span className="truncate text-sm text-white">{m.displayName}</span>
                    {m.role === 'admin' && <Crown size={12} className="shrink-0 text-yellow-400" />}
                    {m.role === 'moderator' && <Shield size={12} className="shrink-0 text-blue-400" />}
                  </div>
                  {m.rank && (
                    <span
                      className="rounded-sm px-1 text-[10px] font-medium leading-none"
                      style={{ backgroundColor: m.rank.color + '20', color: m.rank.color }}
                    >
                      {m.rank.name}
                    </span>
                  )}
                </div>

                {/* Actions */}
                {canModerate && (
                  <div className="hidden gap-1 group-hover:flex">
                    <button
                      onClick={() => onKick(m.userId)}
                      className="transition-colors hover:text-red-400"
                      title="Kick"
                    >
                      <UserMinus size={14} className="text-[#555]" />
                    </button>
                    <button
                      onClick={() => onMute(m.userId)}
                      className="transition-colors hover:text-yellow-400"
                      title="Mute"
                    >
                      <MicOff size={14} className="text-[#555]" />
                    </button>
                    <button
                      onClick={() => onBan(m.userId)}
                      className="transition-colors hover:text-red-500"
                      title="Ban"
                    >
                      <Ban size={14} className="text-[#555]" />
                    </button>
                  </div>
                )}
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}

export default MemberSidebar
