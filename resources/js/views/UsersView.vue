<template>
  <div class="flex min-h-screen bg-slate-50 dark:bg-slate-950">
    <Sidebar :is-open="sidebarOpen" @close="sidebarOpen = false" />

    <div class="flex-1 flex flex-col min-w-0 lg:pl-64">
      <Navbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <main class="flex-grow p-4 md:p-6 lg:p-8 space-y-5 overflow-y-auto">

        <div>
          <h2 class="text-xl font-bold text-slate-800 dark:text-slate-100">Telegram Subscribers</h2>
          <p class="text-xs text-slate-400 mt-0.5">Manage chatbot subscribers and send direct messages</p>
        </div>

        <!-- Search bar -->
        <div class="card p-4 flex gap-3 items-center">
          <div class="relative flex-1 max-w-md">
            <input v-model="search" @input="debouncedFetch" type="text"
                   placeholder="Search by name, username, or Telegram ID…"
                   class="input-base pl-9" />
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <p class="text-xs text-slate-400 shrink-0">{{ users.length }} result{{ users.length !== 1 ? 's' : '' }}</p>
        </div>

        <!-- Users table -->
        <div class="card overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/50 text-slate-400 font-semibold uppercase tracking-wide">
                  <th class="px-4 py-3">Telegram ID</th>
                  <th class="px-4 py-3">Name</th>
                  <th class="px-4 py-3">Username</th>
                  <th class="px-4 py-3">Language</th>
                  <th class="px-4 py-3">Status</th>
                  <th class="px-4 py-3">Joined</th>
                  <th class="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr v-if="!users.length">
                  <td colspan="7" class="px-4 py-10 text-center text-slate-400">No subscribers found</td>
                </tr>
                <tr v-else v-for="user in users" :key="user.id" class="table-row-base">
                  <td class="px-4 py-3 font-mono font-semibold text-slate-600 dark:text-slate-300">{{ user.id }}</td>
                  <td class="px-4 py-3">
                    <p class="font-semibold text-slate-800 dark:text-slate-200">{{ user.firstName }} {{ user.lastName }}</p>
                  </td>
                  <td class="px-4 py-3 text-slate-500 dark:text-slate-400">@{{ user.username || '—' }}</td>
                  <td class="px-4 py-3 font-bold text-slate-400 uppercase">{{ user.languageCode || 'en' }}</td>
                  <td class="px-4 py-3">
                    <span class="badge" :class="user.status === 'banned'
                      ? 'bg-red-50 text-red-600 border border-red-200 dark:bg-red-950/30 dark:text-red-400 dark:border-red-800'
                      : 'bg-emerald-50 text-emerald-600 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-800'">
                      {{ user.status || 'active' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-slate-400 whitespace-nowrap">{{ formatDate(user.createdAt) }}</td>
                  <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <button @click="openDM(user)"
                              class="px-3 py-1.5 text-xs font-semibold text-primary-600 bg-primary-50 hover:bg-primary-100 dark:bg-primary-950/40 dark:text-primary-400 rounded-lg transition-colors">
                        Message
                      </button>
                      <button @click="toggleStatus(user)"
                              class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors"
                              :class="user.status === 'banned'
                                ? 'text-emerald-600 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-400'
                                : 'text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-950/30 dark:text-red-400'">
                        {{ user.status === 'banned' ? 'Unban' : 'Ban' }}
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </main>
    </div>

    <!-- Direct Message Modal -->
    <teleport to="body">
      <transition name="fade">
        <div v-if="targetUser" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
          <div class="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl p-6">
            <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-1">Direct Message</h3>
            <p class="text-xs text-slate-400 mb-5">
              To: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ targetUser.firstName }}</span>
              (ID: {{ targetUser.id }})
            </p>
            <form @submit.prevent="sendDM" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Message</label>
                <textarea v-model="dmText" rows="4" required
                          placeholder="Write your message to this subscriber…"
                          class="input-base resize-none" />
              </div>
              <div class="flex justify-end gap-3">
                <button type="button" @click="targetUser = null" class="btn-ghost">Cancel</button>
                <button type="submit" :disabled="sending" class="btn-primary">
                  {{ sending ? 'Sending…' : 'Send Message' }}
                </button>
              </div>
            </form>
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
const users       = ref([]);
const search      = ref('');
const targetUser  = ref(null);
const dmText      = ref('');
const sending     = ref(false);
const showToast   = inject('showToast');

const fetchUsers = async () => {
  try {
    const res = await axios.get('/users', { params: { search: search.value } });
    users.value = res.data;
  } catch { showToast('Failed to load subscribers', 'error'); }
};

let timer;
const debouncedFetch = () => { clearTimeout(timer); timer = setTimeout(fetchUsers, 350); };

const toggleStatus = async (user) => {
  const target = user.status === 'banned' ? 'active' : 'banned';
  if (!confirm(`Set this subscriber to "${target}"?`)) return;
  try {
    await axios.post(`/users/${user.id}/toggle-status`);
    showToast('Status updated');
    fetchUsers();
  } catch { showToast('Failed to update status', 'error'); }
};

const openDM = (user) => { targetUser.value = user; dmText.value = ''; };

const sendDM = async () => {
  sending.value = true;
  try {
    await axios.post(`/users/${targetUser.value.id}/message`, { message: dmText.value });
    showToast('Message sent via Telegram');
    targetUser.value = null;
  } catch { showToast('Failed to send message', 'error'); }
  finally { sending.value = false; }
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString() : '—';

onMounted(fetchUsers);
</script>
