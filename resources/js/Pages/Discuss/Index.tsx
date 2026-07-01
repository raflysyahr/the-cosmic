import Layout from '../../Components/layout/Layout'
import DiscussRoomCard from '../../Components/discuss/DiscussRoomCard'
import type { RoomCardData } from '../../Components/discuss/DiscussRoomCard'

interface PageProps {
  rooms: RoomCardData[]
}

export default function DiscussIndex({ rooms }: PageProps) {
  return (
    <Layout>
      <div className="mx-auto max-w-4xl px-4 py-8">
        <h1 className="mb-4 font-headline-sm text-headline-sm tracking-wider text-primary">
          DISCUSSION ROOMS
        </h1>
        <div className="mb-2 h-px bg-outline-variant/30" />

        {rooms.length === 0 ? (
          <p className="py-12 text-center font-body-sm text-body-sm text-on-surface-variant">
            No discussion rooms yet.
          </p>
        ) : (
          <div className="flex flex-col divide-y divide-outline-variant/30">
            {rooms.map((room) => (
              <DiscussRoomCard key={room.id} room={room} />
            ))}
          </div>
        )}
      </div>
    </Layout>
  )
}
