import { type Ref, isRef } from "vue";

/** Standard meta shape for our frontend pagination components */
export interface StoreMeta {
    currentPage: number;
    lastPage: number;
    total: number;
    perPage: number;
}

/**
 * Standardizes API pagination meta response into our StoreMeta format.
 * Handles both wrapped { meta: { ... } } and direct { current_page: ... } responses.
 */
export function syncMeta(pagination: any, apiData: any): StoreMeta {
    const raw = apiData.meta || apiData;
    const meta: StoreMeta = {
        currentPage: raw.current_page || raw.currentPage || 1,
        lastPage: raw.last_page || raw.lastPage || 1,
        total: raw.total ?? raw.total_records ?? 0,
        perPage: raw.per_page || raw.perPage || 10,
    };

    // Update the reactive/ref pagination object if provided
    if (pagination) {
        const target = isRef(pagination) ? pagination.value : pagination;
        if (target) Object.assign(target, meta);
    }

    return meta;
}

/**
 * Generates a consistent cache key for store requests
 */
export function getCacheKey(prefix: string, lang: string, params: any) {
    return `${prefix}-${lang}-${JSON.stringify(params)}`;
}

/**
 * Clears a reactive/ref cache object
 */
export function clearStoreCache(cache: Record<string, any>) {
    Object.keys(cache).forEach((key) => delete cache[key]);
}

/**
 * Checks if a filters object matches its default state
 */
export function isFilterDefault(current: any, defaults: any) {
    return Object.keys(defaults).every((key) => current[key] === defaults[key]);
}

/**
 * Orchestrates the full fetch-cache-sync lifecycle for a store.
 */
export async function performFetch({
    loading,
    cache,
    pagination,
    params,
    force,
    prefix,
    lang,
    records,
    apiCall,
}: {
    loading: Ref<boolean>;
    cache: Record<string, any>;
    pagination: any;
    params: any;
    force: boolean;
    prefix: string;
    lang: string;
    records: Ref<any[]>;
    apiCall: (params: any) => Promise<any>;
}) {
    const key = getCacheKey(prefix, lang, params);

    if (!force && cache[key]) {
        records.value = cache[key].data;
        syncMeta(pagination, cache[key].meta);
        return;
    }

    loading.value = true;
    try {
        const { data } = await apiCall(params);
        const results = data.data || (Array.isArray(data) ? data : []);
        records.value = results;
        cache[key] = {
            data: results,
            meta: syncMeta(pagination, data),
        };
    } catch (error) {
        throw error;
    } finally {
        loading.value = false;
    }
}
