<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $tr('nav.courses') }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Manage academic courses, assign teachers, and configure schedules.
        </p>
      </div>
      <div class="flex items-center gap-3 w-full sm:w-auto">
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full sm:w-auto transition-colors"
        >
          <Plus class="w-4 h-4 mr-2" />
          {{ $tr('actions.add_new') }}
        </button>
      </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white dark:bg-card-bg rounded-xl shadow-sm border border-gray-200 dark:border-card-border overflow-hidden">
      <!-- Empty State / Loading / Table content here -->
      <div class="p-6">
        <div v-if="loading" class="flex justify-center items-center py-12">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>
        
        <div v-else-if="courses.length === 0" class="text-center py-12">
          <GraduationCap class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No courses</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new course.</p>
          <div class="mt-6">
            <button
              @click="showCreateModal = true"
              type="button"
              class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
            >
              <Plus class="-ml-0.5 mr-1.5 h-5 w-5" aria-hidden="true" />
              New Course
            </button>
          </div>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead>
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 sm:pl-0">Course Name</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Code</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Grade / Class</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Semester</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Teachers</th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                  <span class="sr-only">Actions</span>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
              <tr v-for="course in courses" :key="course.id">
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white sm:pl-0">
                  {{ course.name }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                  {{ course.code }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                  {{ formatGrade(course.senbet_class) }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                  Semester {{ course.semester }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                  <span v-if="course.teachers?.length === 0" class="text-yellow-600">No teachers assigned</span>
                  <div v-else class="flex -space-x-2 overflow-hidden">
                    <span v-for="t in course.teachers" :key="t.id" class="inline-block h-6 w-6 rounded-full bg-gray-200 dark:bg-gray-700 text-xs flex items-center justify-center border border-white dark:border-gray-900" :title="t.name">{{ getInitials(t.name) }}</span>
                  </div>
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                  <button @click="openEditModal(course)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-4">Edit<span class="sr-only">, {{ course.name }}</span></button>
                  <button @click="openTeacherModal(course)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-4">Assign Teachers</button>
                  <button @click="deleteCourse(course.id)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Delete</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modals to be implemented later -->
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Plus, GraduationCap } from 'lucide-vue-next';
import axios from 'axios';

const courses = ref<any[]>([]);
const loading = ref(true);
const showCreateModal = ref(false);

const fetchCourses = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/academic/courses');
    courses.value = res.data;
  } catch (error) {
    console.error('Failed to fetch courses', error);
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

const getInitials = (name: any) => {
    let n = '';
    if (typeof name === 'string') n = name;
    else if (typeof name === 'object') n = name.en || name.am || name.or || '';
    return n.split(' ').map((part: string) => part[0]).join('').substring(0, 2).toUpperCase();
}

const openEditModal = (course: any) => {
  console.log('Edit', course);
};

const openTeacherModal = (course: any) => {
  console.log('Assign Teachers', course);
};

const deleteCourse = async (id: number) => {
  if (!confirm('Are you sure you want to delete this course?')) return;
  try {
    await axios.delete(`/api/academic/courses/${id}`);
    fetchCourses();
  } catch (error) {
    console.error('Failed to delete course', error);
  }
};

onMounted(() => {
  fetchCourses();
});
</script>
