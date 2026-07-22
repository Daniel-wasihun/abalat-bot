<template>
  <div class="flex min-h-screen bg-slate-50 dark:bg-slate-950">
    <Sidebar :is-open="sidebarOpen" @close="sidebarOpen = false" />

    <div class="flex-1 flex flex-col min-w-0 lg:pl-64">
      <Navbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <main class="flex-grow p-4 md:p-6 lg:p-8 space-y-5 overflow-y-auto">

        <!-- Page header + export actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Feedback Submissions</h2>
            <p class="text-xs text-slate-400 mt-0.5">Manage, respond to and export user feedback</p>
          </div>
          <div class="flex items-center gap-2">
            <button @click="exportData('csv')" class="flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition-colors">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
              </svg>
              CSV
            </button>
            <button @click="exportData('pdf')" class="flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition-colors">
              <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              PDF
            </button>
          </div>
        </div>

        <!-- Filter row -->
        <div class="card p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <input v-model="filters.search" @input="debouncedFetch" type="text"
                 placeholder="Search message or user…"
                 class="input-base" />

          <select v-model="filters.category" @change="fetchFeedback" class="input-base">
            <option value="">All Categories</option>
            <option>Bug</option><option>Suggestion</option>
            <option>Complaint</option><option>Question</option><option>Other</option>
          </select>

          <select v-model="filters.priority" @change="fetchFeedback" class="input-base">
            <option value="">All Priorities</option>
            <option>Low</option><option>Medium</option>
            <option>High</option><option>Critical</option>
          </select>

          <select v-model="filters.status" @change="fetchFeedback" class="input-base">
            <option value="">All Statuses</option>
            <option>New</option><option>Read</option>
            <option>In Progress</option><option>Resolved</option><option>Closed</option>
          </select>
        </div>

        <!-- Table -->
        <div class="card overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/50 text-slate-400 font-semibold uppercase tracking-wide">
                  <th class="px-4 py-3">Sender</th>
                  <th class="px-4 py-3">Category</th>
                  <th class="px-4 py-3 max-w-xs">Message</th>
                  <th class="px-4 py-3">Priority</th>
                  <th class="px-4 py-3">Status</th>
                  <th class="px-4 py-3">Date</th>
                  <th class="px-4 py-3 text-right">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr v-if="!feedbackList.length">
                  <td colspan="7" class="px-4 py-10 text-center text-slate-400">No feedback found</td>
                </tr>
                <tr
                  v-else
                  v-for="item in feedbackList"
                  :key="item.id"
                  class="table-row-base"
                >
                  <td class="px-4 py-3">
                    <p class="font-semibold text-slate-800 dark:text-slate-200">{{ item.userName || 'Anonymous' }}</p>
                    <p class="text-[10px] text-slate-400 mt-0.5">@{{ item.username || 'N/A' }}</p>
                  </td>
                  <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ item.category }}</td>
                  <td class="px-4 py-3 max-w-xs truncate text-slate-600 dark:text-slate-300">{{ item.message }}</td>
                  <td class="px-4 py-3">
                    <span class="badge" :class="getPriorityClasses(item.priority)">{{ item.priority }}</span>
                  </td>
                  <td class="px-4 py-3">
                    <span class="badge" :class="getStatusClasses(item.status)">{{ item.status }}</span>
                  </td>
                  <td class="px-4 py-3 text-slate-400 whitespace-nowrap">{{ formatDate(item.createdAt) }}</td>
                  <td class="px-4 py-3 text-right">
                    <button
                      @click="openDetail(item)"
                      class="px-3 py-1.5 text-xs font-semibold text-primary-600 bg-primary-50 hover:bg-primary-100 dark:bg-primary-950/40 dark:text-primary-400 dark:hover:bg-primary-950/70 rounded-lg transition-colors"
                    >
                      Manage
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="flex items-center justify-between px-4 py-3 border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500">
            <span>Page {{ pagination.current_page }} of {{ pagination.last_page || 1 }}</span>
            <div class="flex gap-2">
              <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                      class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 transition-colors">
                ← Prev
              </button>
              <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                      class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-40 transition-colors">
                Next →
              </button>
            </div>
          </div>
        </div>

      </main>
    </div>

    <!-- Detail Drawer Modal -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="selectedItem" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="w-full max-w-2xl max-h-[88vh] flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden">

            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 shrink-0">
              <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">ID: {{ selectedItem.id }}</p>
                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 mt-0.5">{{ selectedItem.userName || 'Anonymous' }}</h3>
              </div>
              <button @click="selectedItem = null" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Scrollable body -->
            <div class="flex-1 overflow-y-auto p-6 space-y-5">

              <!-- Message content -->
              <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Feedback Message</p>
                <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line leading-relaxed">{{ selectedItem.message }}</p>

                <!-- Attachment -->
                <div v-if="selectedItem.attachmentUrl" class="mt-4 pt-4 border-t border-slate-200/60 dark:border-slate-800/60">
                  <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Attachment</p>
                  <img v-if="selectedItem.type === 'image'" :src="selectedItem.attachmentUrl" class="max-h-56 rounded-xl border border-slate-200 dark:border-slate-700" alt="Attachment" />
                  <audio v-else-if="selectedItem.type === 'voice'" controls :src="selectedItem.attachmentUrl" class="w-full mt-1" />
                  <a v-else :href="selectedItem.attachmentUrl" target="_blank" class="inline-flex items-center gap-2 text-xs font-semibold text-primary-600 dark:text-primary-400 hover:underline">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" /></svg>
                    Download file
                  </a>
                </div>
              </div>

              <!-- Status controls -->
              <div class="grid grid-cols-3 gap-3">
                <div v-for="field in ['category', 'priority', 'status']" :key="field">
                  <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">{{ field }}</label>
                  <select v-model="selectedItem[field]" @change="updateParameter(field)" class="input-base text-xs">
                    <option v-for="opt in fieldOptions[field]" :key="opt" :value="opt">{{ opt }}</option>
                  </select>
                </div>
              </div>

              <!-- Internal notes -->
              <div>
                <p class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-3">Internal Notes</p>
                <div class="space-y-2.5 mb-3">
                  <div v-for="note in selectedItem.internalNotes" :key="note.id"
                       class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs">
                    <div class="flex justify-between text-slate-400 font-semibold mb-1">
                      <span>{{ note.author }}</span><span>{{ formatDate(note.createdAt) }}</span>
                    </div>
                    <p class="text-slate-600 dark:text-slate-300">{{ note.note }}</p>
                  </div>
                  <p v-if="!selectedItem.internalNotes?.length" class="text-xs text-slate-400">No notes yet.</p>
                </div>
                <div class="flex gap-2">
                  <input v-model="newNote" @keyup.enter="saveNote" type="text" placeholder="Add a note and press Enter…" class="input-base flex-1 text-xs" />
                  <button @click="saveNote" class="btn-primary text-xs px-3">Add</button>
                </div>
              </div>

            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/60 shrink-0">
              <button @click="deleteItem" class="btn-danger text-xs">Delete Feedback</button>
              <button @click="selectedItem = null" class="btn-ghost text-xs">Close</button>
            </div>
          </div>
        </div>
      </transition>
    </teleport>

  </div>
</template>

<script setup>
import { ref, onMounted, inject } from 'vue';
import Sidebar from '../components/Sidebar.vue';
import Navbar  from '../components/Navbar.vue';
import axios   from 'axios';

const sidebarOpen = ref(false);
const feedbackList = ref([]);
const selectedItem = ref(null);
const newNote      = ref('');
const showToast    = inject('showToast');

const filters = ref({ search: '', category: '', priority: '', status: '' });
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 });

const fieldOptions = {
  category: ['Bug', 'Suggestion', 'Complaint', 'Question', 'Other'],
  priority: ['Low', 'Medium', 'High', 'Critical'],
  status:   ['New', 'Read', 'In Progress', 'Resolved', 'Closed'],
};

const fetchFeedback = async () => {
  try {
    const res = await axios.get('/feedback', {
      params: { page: pagination.value.current_page, per_page: pagination.value.per_page, ...filters.value }
    });
    feedbackList.value = res.data.data;
    pagination.value   = res.data.meta;
  } catch {
    showToast('Failed to load feedback', 'error');
  }
};

let debounceTimer;
const debouncedFetch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => { pagination.value.current_page = 1; fetchFeedback(); }, 350);
};

const changePage = (p) => { pagination.value.current_page = p; fetchFeedback(); };

const openDetail = (item) => { selectedItem.value = { ...item }; };

const updateParameter = async (key) => {
  try {
    await axios.put(`/feedback/${selectedItem.value.id}/${key}`, { [key]: selectedItem.value[key] });
    showToast(`${key.charAt(0).toUpperCase() + key.slice(1)} updated`);
    fetchFeedback();
  } catch { showToast(`Failed to update ${key}`, 'error'); }
};

const saveNote = async () => {
  if (!newNote.value.trim()) return;
  try {
    const res = await axios.post(`/feedback/${selectedItem.value.id}/notes`, { note: newNote.value });
    selectedItem.value.internalNotes = res.data.internalNotes;
    newNote.value = '';
    showToast('Note added');
    fetchFeedback();
  } catch { showToast('Failed to add note', 'error'); }
};

const deleteItem = async () => {
  if (!confirm('Delete this feedback permanently?')) return;
  try {
    await axios.delete(`/feedback/${selectedItem.value.id}`);
    showToast('Feedback deleted');
    selectedItem.value = null;
    fetchFeedback();
  } catch { showToast('Failed to delete', 'error'); }
};

const exportData = (format) => {
  const q = new URLSearchParams({ ...filters.value, token: localStorage.getItem('admin_token') }).toString();
  window.open(`/api/feedback/export/${format}?${q}`, '_blank');
};

const getPriorityClasses = (p) => ({
  Critical: 'bg-red-50 text-red-600 border border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-800',
  High:     'bg-amber-50 text-amber-600 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-800',
  Medium:   'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800',
  Low:      'bg-slate-100 text-slate-500 border border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
}[p] ?? 'bg-slate-100 text-slate-500');

const getStatusClasses = (s) => ({
  Resolved:      'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-800',
  Closed:        'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-800',
  'In Progress': 'bg-blue-50 text-blue-600 border border-blue-200 dark:bg-blue-950/30 dark:text-blue-400 dark:border-blue-800',
  Read:          'bg-purple-50 text-purple-600 border border-purple-200 dark:bg-purple-950/30 dark:text-purple-400 dark:border-purple-800',
  New:           'bg-amber-50 text-amber-600 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-800',
}[s] ?? 'bg-slate-100 text-slate-500');

const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '';

onMounted(fetchFeedback);
</script>
