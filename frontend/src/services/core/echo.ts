import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare global {
  interface Window {
    Pusher: typeof Pusher
  }
}

window.Pusher = Pusher

let echoInstance: Echo<'reverb'> | null = null

const readCookie = (name: string): string | null => {
  const escaped = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  const match = document.cookie.match(new RegExp(`(?:^|; )${escaped}=([^;]*)`))
  const rawValue = match?.[1]
  return typeof rawValue === 'string' ? decodeURIComponent(rawValue) : null
}

const createChannelAuthHandler = (authEndpoint: string) => {
  return (
    params: { socketId: string; channelName: string },
    callback: (error: Error | null, data: { auth: string } | null) => void,
  ): void => {
    const xhr = new XMLHttpRequest()
    xhr.open('POST', authEndpoint, true)
    xhr.withCredentials = true
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
    xhr.setRequestHeader('Accept', 'application/json')

    const xsrfToken = readCookie('XSRF-TOKEN')
    if (xsrfToken) {
      xhr.setRequestHeader('X-XSRF-TOKEN', xsrfToken)
    }

    xhr.onreadystatechange = () => {
      if (xhr.readyState !== 4) return

      if (xhr.status === 200) {
        try {
          callback(null, JSON.parse(xhr.responseText))
          return
        } catch {
          callback(new Error('Invalid broadcasting auth response.'), null)
          return
        }
      }

      let message = 'Broadcast authorization failed.'
      try {
        const parsed = JSON.parse(xhr.responseText) as { message?: string }
        if (typeof parsed.message === 'string' && parsed.message.trim() !== '') {
          message = parsed.message
        }
      } catch {
      }

      callback(new Error(`${message} (HTTP ${xhr.status})`), null)
    }

    const body = new URLSearchParams({
      socket_id: params.socketId,
      channel_name: params.channelName,
    })

    xhr.send(body.toString())
  }
}

const createEcho = (): Echo<'reverb'> => {
  const wsHost = import.meta.env.VITE_REVERB_HOST ?? 'localhost'
  const wsPort = Number(import.meta.env.VITE_REVERB_PORT ?? 8080)
  const wsScheme = (import.meta.env.VITE_REVERB_SCHEME ?? 'http').toLowerCase()
  const forceTLS = wsScheme === 'https'
  const authEndpoint = `${(import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api').replace(/\/api\/?$/, '')}/broadcasting/auth`

  return new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY ?? 'local-app-key',
    wsHost,
    wsPort,
    wssPort: wsPort,
    forceTLS,
    enabledTransports: ['ws', 'wss'],
    withCredentials: true,
    authEndpoint,
    channelAuthorization: {
      endpoint: authEndpoint,
      transport: 'ajax',
      customHandler: createChannelAuthHandler(authEndpoint),
    },
    auth: {
      headers: {
        Accept: 'application/json',
      },
    },
  })
}

export const getEcho = (): Echo<'reverb'> => {
  if (!echoInstance) {
    echoInstance = createEcho()
  }

  return echoInstance
}

export const getExistingEcho = (): Echo<'reverb'> | null => {
  return echoInstance
}

export const resetEcho = (): void => {
  if (echoInstance) {
    echoInstance.disconnect()
    echoInstance = null
  }
}
