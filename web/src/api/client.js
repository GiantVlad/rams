const API_BASE = 'http://localhost:8000'

function getSessionId() {
  let id = localStorage.getItem('player_session_id')
  if (!id) {
    id = crypto.randomUUID()
    localStorage.setItem('player_session_id', id)
  }
  return id
}

export async function apiFetch(path, options = {}) {
  const res = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Player-Session-ID': getSessionId(),
      ...(options.headers || {}),
    },
  })

  const text = await res.text()
  let data = null
  try {
    data = text ? JSON.parse(text) : null
  } catch {
    data = { message: text }
  }

  if (!res.ok) {
    const message = (data && data.message) ? data.message : `Request failed (${res.status})`
    const err = new Error(message)
    err.status = res.status
    err.data = data
    throw err
  }

  return data
}
