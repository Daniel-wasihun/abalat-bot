import { defineStore } from 'pinia';
import axios from 'axios';

/**
 * Smart cache with per-filter keying and in-flight request deduplication.
 * This prevents redundant API calls when multiple components mount simultaneously
 * or when the same filter set is requested within the TTL window.
 */
const CACHE_TTL   = 120_000; // 2 minutes
const cache       = new Map();   // key → { data, ts }
const inFlight    = new Map();   // key → Promise

function cacheKey(filters, page, perPage) {
  return JSON.stringify({ ...filters, page, perPage });
}

function isFresh(key) {
  const entry = cache.get(key);
  return entry && (Date.now() - entry.ts < CACHE_TTL);
}

function getCached(key) {
  return cache.get(key)?.data ?? null;
}

function setCache(key, data) {
  cache.set(key, { data, ts: Date.now() });
}

function invalidateCache() {
  cache.clear();
}

export const useFeedbackStore = defineStore('feedback', {
  state: () => ({
    feedbackList: [],
    pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
    filters:     { search: '', category: '', language: '', priority: '', status: '' },
    loading:     false,
    error:       null,
  }),

  getters: {
    hasFilters: (state) =>
      Object.values(state.filters).some(v => v !== ''),

    activeKey: (state) =>
      cacheKey(state.filters, state.pagination.current_page, state.pagination.per_page),
  },

  actions: {
    setFilter(key, value) {
      this.filters[key]              = value;
      this.pagination.current_page   = 1;
      this.fetchFeedback(true);
    },

    setPage(page) {
      this.pagination.current_page = page;
      this.fetchFeedback(true);
    },

    setPerPage(size) {
      this.pagination.per_page = size;
      this.pagination.current_page = 1;
      this.fetchFeedback(true);
    },

    /**
     * Fetches feedback with smart caching and request deduplication.
     * @param {boolean} forceRefresh – skip cache and always re-fetch
     */
    async fetchFeedback(forceRefresh = false) {
      const key = this.activeKey;

      // Serve from cache if still fresh
      if (!forceRefresh && isFresh(key)) {
        const cached = getCached(key);
        this.feedbackList = cached.data;
        this.pagination   = cached.meta;
        return;
      }

      // Deduplicate: if a request for this key is already in-flight, wait for it
      if (inFlight.has(key)) {
        try {
          const res = await inFlight.get(key);
          this.feedbackList = res.data.data;
          this.pagination   = res.data.meta;
        } catch (_) { /* handled below */ }
        return;
      }

      this.loading = true;
      this.error   = null;

      const request = axios.get('/feedback', {
        params: {
          page:     this.pagination.current_page,
          per_page: this.pagination.per_page,
          ...this.filters,
        },
      });

      inFlight.set(key, request);

      try {
        const res = await request;
        this.feedbackList = res.data.data;
        this.pagination   = res.data.meta;

        // Store both data and meta together
        setCache(key, { data: res.data.data, meta: res.data.meta });
      } catch (err) {
        this.error = err.response?.data?.message || 'Failed to load feedback.';
        console.error('[FeedbackStore] fetchFeedback error:', err);
        throw err;
      } finally {
        inFlight.delete(key);
        this.loading = false;
      }
    },

    async updateParameter(id, key, value) {
      try {
        await axios.put(`/feedback/${id}/${key}`, { [key]: value });
        const item = this.feedbackList.find(f => f.id === id);
        if (item) item[key] = value;
        invalidateCache();
      } catch (err) {
        console.error(`[FeedbackStore] updateParameter(${key}) error:`, err);
        throw err;
      }
    },

    async addNote(id, noteText) {
      try {
        const res  = await axios.post(`/feedback/${id}/notes`, { note: noteText });
        const item = this.feedbackList.find(f => f.id === id);
        if (item) item.internalNotes = res.data.internalNotes;
        invalidateCache();
        return res.data.internalNotes;
      } catch (err) {
        console.error('[FeedbackStore] addNote error:', err);
        throw err;
      }
    },

    async replyToFeedback(id, replyText) {
      try {
        const res     = await axios.post(`/feedback/${id}/reply`, { message: replyText });
        const updated = res.data.feedback;
        const index   = this.feedbackList.findIndex(f => f.id === id);
        if (index !== -1) this.feedbackList[index] = updated;
        invalidateCache();
        return updated;
      } catch (err) {
        console.error('[FeedbackStore] replyToFeedback error:', err);
        throw err;
      }
    },

    async deleteFeedback(id) {
      try {
        await axios.delete(`/feedback/${id}`);
        this.feedbackList = this.feedbackList.filter(f => f.id !== id);
        invalidateCache();
      } catch (err) {
        console.error('[FeedbackStore] deleteFeedback error:', err);
        throw err;
      }
    },

    /** Force-clear all cached feedback data */
    clearCache() {
      invalidateCache();
    },
  },
});
