import { initializeApp, getApps } from 'firebase/app';
import { getFirestore, onSnapshot, collection, query, limit, orderBy } from 'firebase/firestore';

const firebaseConfig = {
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
  authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
  storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
  messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
  appId: import.meta.env.VITE_FIREBASE_APP_ID
};

let db = null;
let isFirebaseActive = false;

// Only initialize if VITE_FIREBASE_API_KEY is provided and not starting with "AIzaSyExample"
if (firebaseConfig.apiKey && !firebaseConfig.apiKey.includes('Example') && firebaseConfig.projectId && !firebaseConfig.projectId.includes('demo')) {
  try {
    const app = getApps().length === 0 ? initializeApp(firebaseConfig) : getApps()[0];
    db = getFirestore(app);
    isFirebaseActive = true;
    console.log('Firebase Web SDK Initialized successfully');
  } catch (e) {
    console.warn('Firebase Web SDK initialization failed:', e);
  }
} else {
  console.log('Firebase credentials are using placeholder values. Real-time updates will use API polling.');
}

export { db, isFirebaseActive };

/**
 * Helper to subscribe to real-time updates.
 * If Firestore is available, it listens to collection changes.
 * Otherwise, it falls back to polling the given apiEndpoint.
 */
export function subscribeToCollection(collectionName, apiEndpoint, callback, pollingIntervalMs = 15000) {
  if (isFirebaseActive && db) {
    try {
      const q = query(collection(db, collectionName), orderBy('createdAt', 'desc'), limit(50));
      return onSnapshot(q, (snapshot) => {
        const docs = [];
        snapshot.forEach((doc) => {
          docs.push({ id: doc.id, ...doc.data() });
        });
        callback(docs);
      }, (error) => {
        console.error(`Firestore real-time subscription error for ${collectionName}:`, error);
        // Fallback to polling on permission/network errors
        setupPolling(apiEndpoint, callback, pollingIntervalMs);
      });
    } catch (e) {
      console.warn('Firestore subscription failed, falling back to polling:', e);
      return setupPolling(apiEndpoint, callback, pollingIntervalMs);
    }
  } else {
    return setupPolling(apiEndpoint, callback, pollingIntervalMs);
  }
}

function setupPolling(apiEndpoint, callback, intervalMs) {
  const cleanEndpoint = apiEndpoint.replace(/^\/?api\//, '');
  const fetchUrl = `/api/${cleanEndpoint}`;
  let isCancelled = false;
  let timerId = null;

  const poll = async () => {
    if (isCancelled) return;

    const token = localStorage.getItem('admin_token');
    if (!token) {
      isCancelled = true;
      return;
    }

    try {
      const response = await fetch(fetchUrl, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      if (response.ok) {
        const data = await response.json();
        // Extract array key depending on the standard API response structure
        const arrayData = data.data || data;
        callback(arrayData);
      } else if (response.status === 401) {
        // Token expired or unauthorized - stop polling
        isCancelled = true;
        return;
      }
    } catch (e) {
      console.error('Polling failed:', e);
    } finally {
      if (!isCancelled) {
        timerId = setTimeout(poll, intervalMs);
      }
    }
  };

  poll();

  // Return unsubscribe handler
  return () => {
    isCancelled = true;
    if (timerId) clearTimeout(timerId);
  };
}
