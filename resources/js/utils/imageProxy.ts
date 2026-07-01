const IMGKC_DOMAINS = [
    'sv1.imgkc1.my.id',
    'sv2.imgkc1.my.id',
    'sv3.imgkc1.my.id',
    'minio.imgkc1.my.id',
    'imgkc1.my.id',
    'imgkc2.my.id',
    'imgkc3.my.id',
    'cdn.komikcast.cc',
]

export function proxyImg(url: string | undefined | null): string {
    if (!url) return ''
    try {
        const host = new URL(url).hostname
        const needsProxy = IMGKC_DOMAINS.some((d) => host.endsWith(d))
        if (needsProxy) {
            return '/api/v1/image?url=' + encodeURIComponent(url)
        }
    } catch {}
    return url
}
