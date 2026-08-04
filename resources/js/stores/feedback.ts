import { defineStore } from 'pinia';
import axios from 'axios';

// ─────────────────────────────────────────────
//  Types
// ─────────────────────────────────────────────
interface FeedbackItem {
    id: string;
    userName?: string;
    username?: string;
    language?: string;
    category?: string;
    message?: string;
    priority?: string;
    status?: string;
    createdAt?: string;
    attachmentUrl?: string;
    attachmentType?: string;
    fileName?: string;
    type?: string;
    telegramMessageId?: string;
    replies?: ReplyItem[];
    internalNotes?: NoteItem[];
}

interface ReplyItem {
    id: string;
    author: string;
    message: string;
    createdAt: string;
}

interface NoteItem {
    id: string;
    author: string;
    note: string;
    createdAt: string;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Filters {
    search: string;
    category: string;
    language: string;
    priority: string;
    status: string;
}

interface FeedbackState {
    feedbackList: FeedbackItem[];
    pagination: Pagination;
    filters: Filters;
    loading: boolean;
    error: string | null;
}

// ─────────────────────────────────────────────
//  Cache Helpers
// ─────────────────────────────────────────────
const CACHE_TTL = 120_000; // 2 minutes

interface CacheEntry {
    data: { data: FeedbackItem[]; meta: Pagination };
    ts: number;
}

const cache = new Map<string, CacheEntry>();
const inFlight = new Map<string, Promise<any>>();

function cacheKey(filters: Filters, page: number, perPage: number): string {
    return JSON.stringify({ ...filters, page, perPage });
}

function isFresh(key: string): boolean {
    const entry = cache.get(key);
    return !!entry && Date.now() - entry.ts < CACHE_TTL;
}

function getCached(key: string): CacheEntry['data'] | null {
    return cache.get(key)?.data ?? null;
}

function setCache(key: string, data: CacheEntry['data']): void {
    cache.set(key, { data, ts: Date.now() });
}

function invalidateCache(): void {
    cache.clear();
}

// ─────────────────────────────────────────────
//  Store
// ─────────────────────────────────────────────
export const useFeedbackStore = defineStore('feedback', {
    state: (): FeedbackState => ({
        feedbackList: [],
        pagination: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        filters: {
            search: '',
            category: '',
            language: '',
            priority: '',
            status: '',
        },
        loading: false,
        error: null,
    }),

    getters: {
        hasFilters: (state): boolean =>
            Object.values(state.filters).some((v) => v !== ''),

        activeKey: (state): string =>
            cacheKey(
                state.filters,
                state.pagination.current_page,
                state.pagination.per_page
            ),
    },

    actions: {
        setFilter(key: keyof Filters, value: string): void {
            this.filters[key] = value;
            this.pagination.current_page = 1;
            this.fetchFeedback(true);
        },

        setPage(page: number): void {
            this.pagination.current_page = page;
            this.fetchFeedback(true);
        },

        setPerPage(size: number): void {
            this.pagination.per_page = size;
            this.pagination.current_page = 1;
            this.fetchFeedback(true);
        },

        async fetchFeedback(forceRefresh = false): Promise<void> {
            const key = this.activeKey;

            if (!forceRefresh && isFresh(key)) {
                const cached = getCached(key);
                if (cached) {
                    this.feedbackList = cached.data;
                    this.pagination = cached.meta;
                }
                return;
            }

            if (inFlight.has(key)) {
                try {
                    const res = await inFlight.get(key);
                    this.feedbackList = res.data.data;
                    this.pagination = res.data.meta;
                } catch (_) { /* handled below */ }
                return;
            }

            this.loading = true;
            this.error = null;

            const request = axios.get('/bot/feedback', {
                params: {
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    ...this.filters,
                },
            });

            inFlight.set(key, request);

            try {
                const res = await request;
                this.feedbackList = res.data.data;
                this.pagination = res.data.meta;
                setCache(key, { data: res.data.data, meta: res.data.meta });
            } catch (err: any) {
                this.error =
                    err.response?.data?.message || 'Failed to load feedback.';
                throw err;
            } finally {
                inFlight.delete(key);
                this.loading = false;
            }
        },

        async updateParameter(
            id: string,
            key: string,
            value: string
        ): Promise<void> {
            await axios.put(`/bot/feedback/${id}/${key}`, { [key]: value });
            const item = this.feedbackList.find((f) => f.id === id);
            if (item) (item as any)[key] = value;
            invalidateCache();
        },

        async addNote(id: string, noteText: string): Promise<NoteItem[]> {
            const res = await axios.post(`/bot/feedback/${id}/notes`, {
                note: noteText,
            });
            const item = this.feedbackList.find((f) => f.id === id);
            if (item) item.internalNotes = res.data.internalNotes;
            invalidateCache();
            return res.data.internalNotes;
        },

        async replyToFeedback(
            id: string,
            replyText: string
        ): Promise<FeedbackItem> {
            const res = await axios.post(`/bot/feedback/${id}/reply`, {
                message: replyText,
            });
            const updated: FeedbackItem = res.data.feedback;
            const index = this.feedbackList.findIndex((f) => f.id === id);
            if (index !== -1) this.feedbackList[index] = updated;
            invalidateCache();
            return updated;
        },

        async deleteFeedback(id: string): Promise<void> {
            await axios.delete(`/bot/feedback/${id}`);
            this.feedbackList = this.feedbackList.filter((f) => f.id !== id);
            invalidateCache();
        },

        clearCache(): void {
            invalidateCache();
        },
    },
});
