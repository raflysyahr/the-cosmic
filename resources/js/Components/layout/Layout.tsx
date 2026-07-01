import { useEffect } from 'react'
import { usePage } from '@inertiajs/react'
import Header from './Header'
import Footer from './Footer'
import Popup from '../ui/Popup'
import { usePopup } from '../../contexts/PopupContext'
import { useAuth } from '../../contexts/AuthContext'

function PopupLayer() {
    const { popup, closePopup } = usePopup()
    if (!popup) return null
    return <Popup config={popup} onClose={closePopup} />
}

export default function Layout({ children }: { children: React.ReactNode }) {
    const { auth } = usePage().props as { auth: { user: Record<string, unknown> | null } }
    const { setUser } = useAuth()

    useEffect(() => {
        if (auth?.user) setUser(auth.user as never)
    }, [auth, setUser])

    return (
        <>
            <div className="flex min-h-screen flex-col">
                <Header />
                <main className="flex-1">{children}</main>
                <Footer />
            </div>
            <PopupLayer />
        </>
    )
}
