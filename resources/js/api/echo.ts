import Echo from "laravel-echo";
import Pusher from "pusher-js";
import { ref } from "vue";
import Cookies from "js-cookie";
import apiClient from "./apiClient";

declare global {
    interface Window {
        Pusher: any;
        Echo: Echo<any>;
    }
}

window.Pusher = Pusher;

export const isConnected = ref(false);

const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;

const apiUrl = (
    import.meta.env.VITE_API_URL || "http://localhost:8000/api"
).replace(/\/$/, "");

// Initialize Echo with Reverb or a dummy object to prevent crashes
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || "http";
const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname;
const reverbPort = Number(import.meta.env.VITE_REVERB_PORT) || 8085;

const echo = reverbKey
    ? new Echo({
          broadcaster: "reverb",
          key: reverbKey,
          wsHost: reverbHost,
          wsPort: reverbPort,
          wssPort: reverbPort,
          forceTLS: reverbScheme === "https",
          enabledTransports: reverbScheme === "https" ? ["wss"] : ["ws"],
          authorizer: (channel: any) => {
              return {
                  authorize: (socketId: string, callback: Function) => {
                      apiClient
                          .post("/broadcasting/auth", {
                              socket_id: socketId,
                              channel_name: channel.name,
                          })
                          .then((response) => {
                              callback(false, response.data);
                          })
                          .catch((error) => {
                              console.error("🔒 Broadcaster auth failed:", error);
                              callback(true, error);
                          });
                  },
              };
          },
          reconnectAt: true,
          activityTimeout: 60000,
      })
    : (() => {
          console.warn(
              "Real-time features disabled: VITE_REVERB_APP_KEY missing.",
          );
          const noop = () => ({ listen: noop, notification: noop });
          return {
              channel: noop,
              private: noop,
              leave: noop,
              connector: { pusher: { connection: { bind: noop } } },
          } as any;
      })();

// Track connection status if echo is real
if (reverbKey) {
    echo.connector.pusher.connection.bind("state_change", (states: any) => {
        console.log(`🌐 Echo State: ${states.previous} -> ${states.current}`);
        isConnected.value = states.current === "connected";
    });

    echo.connector.pusher.connection.bind("connected", () => {
        console.log("✅ Real-time connection established!");
    });

    echo.connector.pusher.connection.bind("error", (err: any) => {
        console.error("❌ Echo connection error:", err);
    });
}

export default echo;
