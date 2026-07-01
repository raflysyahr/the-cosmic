import { type FC } from 'react'

interface RankBadgeProps {
  name: string
  color: string
  iconUrl?: string
}

const RankBadge: FC<RankBadgeProps> = ({ name, color, iconUrl }) => (
  <span
    style={{ borderColor: color, color }}
    className="inline-flex items-center gap-1 px-1.5 py-0.5 border text-[10px] font-bold tracking-wider uppercase"
  >
    {iconUrl && <img src={iconUrl} alt="" className="w-2.5 h-2.5" />}
    {name}
  </span>
)

export default RankBadge
