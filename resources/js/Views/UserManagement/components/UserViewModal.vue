<template>
  <Modal
    :show="show"
    @close="emit('close')"
    size="lg"
    :title="$tr('user.profile_details')"
    :icon="UserCircle2"
  >
    <div v-if="user" class="space-y-6 text-main-text">
      <!-- Header / Identity -->
      <div class="flex items-center gap-5 p-4 rounded-xl bg-card-bg border border-card-border/60 shadow-sm">
        <div class="relative shrink-0">
          <div class="w-20 h-20 rounded-full overflow-hidden border-4 border-main-bg shadow-sm bg-brand-blue/5 flex items-center justify-center">
            <img v-if="user.profile_picture" :src="user.profile_picture" :alt="localize(user.name)" class="w-full h-full object-cover" />
            <span v-else class="text-3xl font-semibold text-brand-blue capitalize">{{ localize(user.name).charAt(0) }}</span>
          </div>
          <div v-if="user.is_active" class="absolute bottom-0 right-0 w-4 h-4 rounded-full bg-emerald-500 border-2 border-main-bg"></div>
          <div v-else class="absolute bottom-0 right-0 w-4 h-4 rounded-full bg-rose-500 border-2 border-main-bg"></div>
        </div>

        <div class="flex-1 min-w-0">
          <h2 class="text-xl font-bold text-main-text truncate">{{ localize(user.name) }}</h2>
          <p class="text-sm text-main-text/60 mt-0.5 truncate">{{ user.email }}</p>
          <div class="flex flex-wrap gap-2 mt-2">
            <span
              v-for="role in user.roles"
              :key="role.id"
              class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-brand-blue/10 text-brand-blue border border-brand-blue/20"
            >
              <ShieldCheck class="w-3.5 h-3.5" />
              {{ localize(role.name) }}
            </span>
            <span
              v-if="!user.roles?.length"
              class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200"
            >
              No Role
            </span>
          </div>
        </div>
      </div>

      <!-- Grid Details -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Personal Info -->
        <div class="p-4 rounded-xl border border-card-border/50 bg-main-bg/30 space-y-4">
          <h3 class="text-sm font-semibold text-main-text/80 flex items-center gap-2 mb-3">
            <UserCircle2 class="w-4 h-4 text-brand-blue" /> Personal Information
          </h3>
          <div class="grid grid-cols-3 gap-2 text-sm">
            <span class="text-main-text/60">Gender</span>
            <span class="col-span-2 font-medium capitalize">{{ user.info?.gender || '—' }}</span>
          </div>
          <div class="grid grid-cols-3 gap-2 text-sm">
            <span class="text-main-text/60">Phone</span>
            <span class="col-span-2 font-medium">{{ user.info?.phone_number ? `+251 ${user.info.phone_number}` : '—' }}</span>
          </div>
          <div class="grid grid-cols-3 gap-2 text-sm">
            <span class="text-main-text/60">DOB</span>
            <span class="col-span-2 font-medium">{{ user.info?.date_of_birth ? formatDate(user.info.date_of_birth) : '—' }}</span>
          </div>
        </div>

        <!-- System Details -->
        <div class="p-4 rounded-xl border border-card-border/50 bg-main-bg/30 space-y-4">
          <h3 class="text-sm font-semibold text-main-text/80 flex items-center gap-2 mb-3">
            <LayoutGrid class="w-4 h-4 text-brand-blue" /> System Details
          </h3>
          <div class="grid grid-cols-3 gap-2 text-sm">
            <span class="text-main-text/60">ID</span>
            <span class="col-span-2 font-medium">#{{ user.id }}</span>
          </div>
          <div class="grid grid-cols-3 gap-2 text-sm">
            <span class="text-main-text/60">Reg. ID</span>
            <span class="col-span-2 font-mono text-xs bg-main-text/5 px-2 py-0.5 rounded">{{ user.info?.registration_id || '—' }}</span>
          </div>
          <div class="grid grid-cols-3 gap-2 text-sm">
            <span class="text-main-text/60">Joined</span>
            <span class="col-span-2 font-medium">{{ formatDate(user.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center justify-end gap-3 pt-4 border-t border-card-border/60">
        <Button variant="secondary" @click="emit('close')">{{ $tr('common.close', 'Close') }}</Button>
      </div>
    </div>
  </Modal>
</template>

<script setup lang="ts">
import {
  ShieldCheck,
  UserCircle2,
  LayoutGrid,
} from "lucide-vue-next";
import { storeToRefs } from "pinia";
import Modal from "@/components/common/Modal.vue";
import Button from "@/components/common/Button.vue";
import { localize as utilLocalize, formatDate } from "@/utils/format";
import { useLanguageStore } from "@/stores/languageStore";

const props = defineProps<{
  show: boolean;
  user: any;
}>();

const emit = defineEmits<{
  (e: "close"): void;
}>();

const { currentLanguage } = storeToRefs(useLanguageStore());

const localize = (val: any) => utilLocalize(val, currentLanguage.value);
</script>
