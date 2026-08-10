<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $tr('nav.my_classes') }}</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        View and manage the classes you are assigned to teach.
      </p>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>
    
    <div v-else-if="courses.length === 0" class="text-center py-12 bg-white dark:bg-card-bg rounded-xl shadow-sm border border-gray-200 dark:border-card-border">
      <BookOpen class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No classes assigned</h3>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">You have not been assigned to teach any courses yet.</p>
    </div>

    <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <div 
        v-for="course in courses" 
        :key="course.id" 
        @click="goToCourse(course.id)"
        class="bg-white dark:bg-card-bg rounded-xl shadow-sm border border-gray-200 dark:border-card-border p-6 hover:shadow-md transition-shadow cursor-pointer flex flex-col justify-between"
      >
        <div>
          <div class="flex items-center justify-between">
            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
              {{ formatGrade(course.senbet_class) }}
            </span>
            <span class="text-sm text-gray-500">Sem {{ course.semester }}</span>
          </div>
          <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white truncate">{{ course.name }}</h3>
          <p class="mt-1 text-sm text-gray-500">{{ course.code }}</p>
          <div class="mt-4 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <Users class="w-4 h-4" />
            <span>{{ course.students_count || 0 }} Students</span>
          </div>
        </div>
        <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
          <span class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500">Manage Class &rarr;</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { BookOpen, Users } from 'lucide-vue-next';
import { useRouter } from 'vue-router';
import axios from 'axios';

const courses = ref<any[]>([]);
const loading = ref(true);
const router = useRouter();

const fetchMyClasses = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/academic/my-classes');
    courses.value = res.data;
  } catch (error) {
    console.error('Failed to fetch my classes', error);
  } finally {
    loading.value = false;
  }
};

const formatGrade = (val: string) => {
  if (!val) return '';
  if (val === 'child') return 'Child / KG';
  if (val === 'post_12') return 'Post Grade 12';
  return `Grade ${val}`;
};

const goToCourse = (id: number) => {
  router.push(`/dashboard/academic/my-classes/${id}`);
};

onMounted(() => {
  fetchMyClasses();
});
</script>
