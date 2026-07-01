export function apiFetch(url: string, opts: RequestInit = {}): Promise<Response> {
  const headers = new Headers(opts.headers)
  if (!headers.has('Accept')) headers.set('Accept', 'application/json')
  if (!headers.has('Content-Type') && opts.method !== undefined && opts.method !== 'GET') {
    headers.set('Content-Type', 'application/json')
  }

  return fetch(url, { ...opts, credentials: 'include', headers })
}
