<script setup lang="ts">
import { computed, useSlots, ref } from "vue";
import {
  ChevronUp,
  ChevronDown,
  CheckSquare,
  Square,
  SearchX,
} from "lucide-vue-next";
import TablePagination from "./TablePagination.vue";

/**
 * DataTable — Core professional primitive.
 * High-performance, accessible, and future-proofed against large datasets.
 */
export interface ColumnDef {
  key: string;
  label: string;
  align?: "left" | "center" | "right";
  width?: string;
  sortable?: boolean;
  renderCell?: Function;
}

const props = withDefaults(
  defineProps<{
    columns?: ColumnDef[];
    items: any[];
    selected?: (string | number)[];
    selectable?: boolean;
    loading?: boolean;
    sortBy?: string;
    sortOrder?: "asc" | "desc";
    emptyMessage?: string;
    rowKey?: string;
    /** Optional class or function returning a class for the <tr> element */
    rowClass?: string | ((item: any, index: number) => string);
    /** Pagination props for automatic footer rendering */
    pagination?: {
      currentPage: number;
      lastPage: number;
      total: number;
      perPage: number;
      summary?: string;
    };
    /** Empty state configuration */
    emptyIcon?: any;
    emptyTitle?: string;
    emptyDesc?: string;
  }>(),
  {
    selected: () => [],
    selectable: false,
    loading: false,
    emptyMessage: "No records found.",
    rowKey: "id", // Professional fallback
    rowClass: "",
    emptyIcon: null,
    emptyTitle: "",
    emptyDesc: "",
  },
);

const emit = defineEmits([
  "update:selected",
  "sort",
  "row-click",
  "page-change",
  "per-page-change",
]);
const slots = useSlots();

// --- Column Definition Parser ---
const resolvedColumns = computed<ColumnDef[]>(() => {
  if (props.columns?.length) return props.columns;
  if (!slots.default) return [];

  const flatten = (nodes: any): any[] => {
    if (!Array.isArray(nodes)) return [];
    return nodes.reduce(
      (acc, n) =>
        acc.concat(
          typeof n.type === "symbol" ? flatten(n.children) : n,
        ),
      [],
    );
  };

  return flatten(slots.default())
    .filter((n) => n.type?.name === "TableColumn")
    .map((n, i) => ({
      key: n.props?.field || n.props?.key || `col-${i}`,
      label: n.props?.header || n.props?.label || "",
      align: n.props?.align || "left",
      width: n.props?.width,
      sortable:
        n.props?.sortable !== undefined && n.props?.sortable !== false,
      renderCell: n.children?.default,
    }));
});

// --- Selection Logic (Key-Safe) ---
const allSelected = computed(
  () =>
    props.items.length > 0 &&
    props.items.every((i) => props.selected.includes(i[props.rowKey])),
);
const someSelected = computed(
  () => props.selected.length > 0 && !allSelected.value,
);

const toggleAll = () =>
  emit(
    "update:selected",
    allSelected.value ? [] : props.items.map((i) => i[props.rowKey]),
  );
const toggleRow = (id: string | number) => {
  const next = new Set(props.selected);
  next.has(id) ? next.delete(id) : next.add(id);
  emit("update:selected", [...next]);
};

// --- Smart Header (Performance Optimised) ---
const scrollContainer = ref<HTMLElement | null>(null);
const isHeaderHidden = ref(false);
let lastTop = 0;
let ticking = false;

const handleScroll = () => {
  if (ticking || !scrollContainer.value) return;
  ticking = true;
  requestAnimationFrame(() => {
    const top = scrollContainer.value?.scrollTop || 0;
    isHeaderHidden.value = top > 30 && top > lastTop; // Directional logic
    if (top < lastTop) isHeaderHidden.value = false;
    lastTop = top;
    ticking = false;
  });
};
</script>

<template>
  <div
    v-bind="$attrs"
    class="relative w-full rounded-lg border border-card-border overflow-hidden bg-card-bg flex flex-col">
    <div class="hidden"><slot /></div>

    <div
      ref="scrollContainer"
      class="overflow-auto custom-scrollbar relative flex-1"
      style="max-height: calc(100vh - 200px)"
      @scroll="handleScroll">
      <!-- High-Priority Header Injection Slot -->
      <slot name="header-top" />

      <table
        class="w-full text-left text-sm text-main-text min-w-max border-collapse">
        <thead
          class="sticky top-0 z-40 bg-card-bg/95 backdrop-blur-md border-b border-card-border text-sm font-normal text-main-text/90 capitalize tracking-[0.15em] "
          style="transition: transform 200ms ease, opacity 200ms ease"
          :class="
            isHeaderHidden
              ? '-translate-y-full opacity-0 pointer-events-none'
              : 'translate-y-0 opacity-100 '
          ">
          <tr role="row" class="bg-main-text/1">
            <th
              v-if="selectable"
              class="w-12 px-6 py-4 text-center border-r border-card-border/30 font-normal"
              role="columnheader">
              <button
                type="button"
                class="text-main-text/40 hover:text-brand-blue"
                @click="toggleAll"
                :aria-label="
                  allSelected ? 'Deselect All' : 'Select All'
                ">
                <CheckSquare
                  v-if="allSelected"
                  class="w-5 h-5 text-brand-blue" />
                <Square
                  v-else-if="!someSelected"
                  class="w-5 h-5" />
                <svg
                  v-else
                  class="w-5 h-5 text-brand-blue"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2">
                  <rect
                    x="3"
                    y="3"
                    width="18"
                    height="18"
                    rx="2"
                    ry="2" />
                  <line x1="8" y1="12" x2="16" y2="12" />
                </svg>
              </button>
            </th>
            <th
              v-for="c in resolvedColumns"
              :key="c.key"
              class="px-6 py-4 select-none transition-colors border-r border-card-border/20 last:border-r-0 font-normal"
              role="columnheader"
              :class="[
                c.align === 'center'
                  ? 'text-center'
                  : c.align === 'right'
                  ? 'text-right'
                  : 'text-left',
                c.sortable
                  ? 'cursor-pointer hover:bg-main-text/3 group'
                  : '',
              ]"
              :style="{ width: c.width, minWidth: c.width }"
              @click="c.sortable && emit('sort', c.key)">
              <div
                class="flex items-center gap-2"
                :class="{
                  'flex-row-reverse': c.align === 'right',
                }">
                <span>{{ c.label }}</span>
                <div
                  v-if="c.sortable"
                  class="flex flex-col opacity-20 group-hover:opacity-60 transition-opacity"
                  :class="
                    sortBy === c.key ? 'opacity-100!' : ''
                  ">
                  <ChevronUp
                    class="w-3 h-3 -mb-1"
                    :class="
                      sortBy === c.key &&
                      sortOrder === 'asc'
                        ? 'text-brand-blue'
                        : 'text-main-text/40'
                    " />
                  <ChevronDown
                    class="w-3 h-3"
                    :class="
                      sortBy === c.key &&
                      sortOrder === 'desc'
                        ? 'text-brand-blue'
                        : 'text-main-text/40'
                    " />
                </div>
              </div>
            </th>
          </tr>
        </thead>

        <tbody v-if="loading">
          <tr
            v-for="i in 8"
            :key="i"
            class="animate-pulse border-b border-card-border/60">
            <td v-if="selectable" class="px-6 py-2.5 text-center">
              <div
                class="w-4 h-4 rounded bg-main-text/10 mx-auto" />
            </td>
            <td
              v-for="c in resolvedColumns"
              :key="c.key"
              class="px-6 py-4">
              <div
                class="h-3.5 rounded bg-main-text/10"
                :class="
                  c.align === 'center'
                    ? 'mx-auto'
                    : c.align === 'right'
                    ? 'ml-auto'
                    : ''
                "
                :style="{ width: c.width || '80%' }" />
            </td>
          </tr>
        </tbody>

        <tbody v-else-if="!items.length">
          <tr>
            <td
              :colspan="
                selectable
                  ? resolvedColumns.length + 1
                  : resolvedColumns.length
              "
              class="px-6 py-16 text-center">
              <div
                class="flex flex-col items-center text-main-text/50">
                <component
                  :is="emptyIcon || SearchX"
                  class="w-12 h-12 text-main-text/20 mb-3" />
                <h3 class="text-sm font-medium">
                  {{ emptyTitle || emptyMessage }}
                </h3>
                <p v-if="emptyDesc" class="text-xs text-main-text/55 mt-1 max-w-[280px] mx-auto leading-relaxed">
                  {{ emptyDesc }}
                </p>
                <slot name="empty-actions" />
              </div>
            </td>
          </tr>
        </tbody>

        <tbody v-else>
          <tr
            v-for="(item, idx) in items"
            :key="item[rowKey] ?? idx"
            role="row"
            @click="emit('row-click', item)"
            class="group transition-colors duration-150 border-b border-card-border/40 bg-card-bg hover:bg-main-text/5 cursor-pointer"
            :class="[
              selected.includes(item[rowKey]) ? 'bg-brand-blue/5' : '',
              typeof rowClass === 'function' ? rowClass(item, idx) : rowClass
            ]">
            <td
              v-if="selectable"
              class="px-6 py-2 text-center"
              @click.stop
              role="gridcell">
              <button
                @click="toggleRow(item[rowKey])"
                class="text-main-text/20 hover:text-brand-blue focus:outline-none"
                :aria-label="
                  selected.includes(item[rowKey])
                    ? 'Deselect Row'
                    : 'Select Row'
                ">
                <CheckSquare
                  v-if="selected.includes(item[rowKey])"
                  class="w-5 h-5 text-brand-blue" />
                <Square v-else class="w-5 h-5" />
              </button>
            </td>
            <td
              v-for="c in resolvedColumns"
              :key="c.key"
              class="px-6 py-2 text-sm font-normal text-main-text/90"
              role="gridcell"
              :class="[
                c.align === 'center'
                  ? 'text-center'
                  : c.align === 'right'
                  ? 'text-right'
                  : 'text-left',
              ]">
              <component
                v-if="c.renderCell"
                :is="c.renderCell"
                :value="item[c.key]"
                :row="item"
                :index="idx" />
              <slot
                v-else
                :name="`cell-${c.key}`"
                :value="item[c.key]"
                :item="item"
                >{{ item[c.key] ?? "—" }}</slot
              >
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Sticky Footer Area (Fixed at bottom of component) -->
    <div
      v-if="$slots.footer || pagination"
      class="border-t border-card-border/60 bg-card-bg px-5 py-2 w-full shrink-0 z-50">
      <slot name="footer" />
      <TablePagination
        v-if="pagination"
        :current-page="pagination.currentPage"
        :last-page="pagination.lastPage"
        :total="pagination.total"
        :per-page="pagination.perPage"
        :summary="pagination.summary"
        :loading="loading"
        @page-change="emit('page-change', $event)"
        @per-page-change="emit('per-page-change', $event)" />
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 6px;
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(156, 163, 175, 0.5);
  border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(75, 85, 99, 0.5);
}
</style>
