import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

declare global {
  interface Window {
    Pusher: typeof Pusher
  }
}

window.Pusher = Pusher

let echoInstance: Echo<'reverb'> | null = null

const createEcho = (token: string): Echo<'reverb'> => {
  const wsHost = import.meta.env.VITE_REVERB_HOST ?? 'localhost'
  const wsPort = Number(import.meta.env.VITE_REVERB_PORT ?? 8080)
  const wsScheme = (import.meta.env.VITE_REVERB_SCHEME ?? 'http').toLowerCase()
  const forceTLS = wsScheme === 'https'

  return new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY ?? 'local-app-key',
    wsHost,
    wsPort,
    wssPort: wsPort,
    forceTLS,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: `${(import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api').replace(/\/api\/?$/, '')}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
  })
}

export const getEcho = (token: string): Echo<'reverb'> => {
  if (!echoInstance) {
    echoInstance = createEcho(token)
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
