import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import axios from 'axios';

declare global {
  interface Window {
    Pusher: any;
    Echo: Echo;
  }
}

window.Pusher = Pusher;

// important: ensure axios sends cookies + XSRF
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

window.Echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY,
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
  forceTLS: true,
  authorizer: (channel) => {
    return {
      authorize: (socketId: string, callback: (err: boolean, data: any) => void) => {
        axios.post('/api/broadcasting/auth', {
          socket_id: socketId,
          channel_name: channel.name,
        })
          .then((res) => callback(false, res.data))
          .catch((err) => callback(true, err));
      },
    };
  },
});
