import { ref } from "vue";

/**
 * Reusable filter logic for store-based modules.
 * Standardizes searching, debouncing, and filter state toggling.
 */
export function useFilters(
    store: any,
    options: {
        searchField?: string;
        fetchMethod?: string | ((page?: number, force?: boolean) => any);
        toggleMethod?: string | (() => any);
    } = {},
) {
    const searchField = options.searchField || "search";
    let searchTimeout: any;

    /**
     * Executes the fetch operation based on store conventions or explicit overrides.
     */
    const invokeFetch = (page = 1, force = true) => {
        // 1. Check for functional override
        if (typeof options.fetchMethod === "function") {
            return options.fetchMethod(page, force);
        }

        // 2. Check for string-based method name override
        if (options.fetchMethod && typeof store[options.fetchMethod as string] === "function") {
            return store[options.fetchMethod as string](page, force);
        }

        // 3. Fallback to common LMS fetch name patterns
        const patterns = [
            "fetchUsers", "fetchItems", "fetchData", "fetchCategories",
            "fetchBooks", "fetchLibraries", "fetchShelves", "fetchCopies",
            "fetchBorrows", "fetchSpotReadings", "fetchFines", "fetchPolicies"
        ];

        for (const method of patterns) {
            if (typeof store[method] === "function") {
                return store[method](page, force);
            }
        }
    };

    /**
     * Debounced search handler.
     */
    const handleSearch = (val: string) => {
        if (store.filters) {
            store.filters[searchField] = val;
        }
        
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => invokeFetch(1, true), 500);
    };

    /**
     * Immediate filter change handler (for selects/toggles).
     */
    const handleFilterChange = () => invokeFetch(1, true);

    /**
     * Resets filter state and re-fetches.
     */
    const resetFilters = () => {
        if (typeof store.resetFilters === "function") {
            store.resetFilters();
        }
    };

    /**
     * Toggles filter visibility pane.
     */
    const toggleFilters = () => {
        if (typeof options.toggleMethod === "function") {
            return options.toggleMethod();
        }

        const method = options.toggleMethod as string;
        if (method && typeof store[method] === "function") {
            return store[method]();
        }

        // Automatic discovery for common toggle patterns
        const togglePatterns = [
            "toggleFilters", "toggleSpotReadingFilters", 
            "toggleBorrowFilters", "toggleReturnFilters"
        ];

        for (const p of togglePatterns) {
            if (typeof store[p] === "function") return store[p]();
        }

        // Fallback to property toggling
        const statePatterns = ["showFilters", "showSpotReadingFilters", "showBorrowFilters"];
        for (const s of statePatterns) {
            if (s in store) {
                store[s] = !store[s];
                return;
            }
        }
    };

    return {
        handleSearch,
        handleFilterChange,
        resetFilters,
        toggleFilters,
    };
}
