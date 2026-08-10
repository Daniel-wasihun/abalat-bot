<template>
  <div class="space-y-6">
    <div class="flex items-center gap-4">
      <button @click="router.push('/dashboard/academic/my-classes')" class="p-2 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
        <ArrowLeft class="w-5 h-5" />
      </button>
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          <span v-if="loading" class="animate-pulse bg-gray-200 h-8 w-48 rounded inline-block"></span>
          <span v-else>{{ course?.name }}</span>
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          <span v-if="!loading">{{ course?.code }} • {{ formatGrade(course?.senbet_class) }}</span>
        </p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700">
      <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            activeTab === tab.id 
              ? 'border-blue-500 text-blue-600 dark:text-blue-400' 
              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300',
            'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors'
          ]"
        >
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="mt-6 bg-white dark:bg-card-bg rounded-xl shadow-sm border border-gray-200 dark:border-card-border p-6 min-h-[400px]">
      
      <!-- Students Tab -->
      <div v-if="activeTab === 'students'">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white">Enrolled Students</h2>
          <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-sm font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
            {{ students.length }} Students
          </span>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead>
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Name</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Email</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
              <tr v-for="student in students" :key="student.id">
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white">
                  {{ formatName(student.name) }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                  {{ student.email }}
                </td>
              </tr>
              <tr v-if="students.length === 0">
                <td colspan="2" class="py-8 text-center text-gray-500">No students enrolled yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Assessments Tab -->
      <div v-else-if="activeTab === 'assessments'">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white">Assessment Components</h2>
          <button @click="showAssessmentModal = true" class="text-sm text-blue-600 hover:text-blue-500 font-medium flex items-center gap-1">
            <Plus class="w-4 h-4" /> Add Component
          </button>
        </div>
        
        <div class="space-y-4">
          <div v-for="comp in assessments" :key="comp.id" class="flex justify-between items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
            <div>
              <p class="font-medium text-gray-900 dark:text-white">{{ comp.name }}</p>
              <p class="text-xs text-gray-500 uppercase tracking-wider">{{ comp.type }}</p>
            </div>
            <div class="flex items-center gap-4">
              <span class="text-lg font-bold text-gray-700 dark:text-gray-300">{{ comp.percentage }}%</span>
              <button @click="deleteAssessment(comp.id)" class="text-red-500 hover:text-red-700"><Trash2 class="w-4 h-4" /></button>
            </div>
          </div>
          
          <div class="flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
            <span class="font-medium text-gray-900 dark:text-white">Total</span>
            <span class="text-lg font-bold" :class="Number(totalPercentage) === 100 ? 'text-green-600' : 'text-yellow-600'">
              {{ totalPercentage }}%
            </span>
          </div>
        </div>
      </div>

      <!-- Gradebook Tab -->
      <div v-else-if="activeTab === 'gradebook'">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-lg font-medium text-gray-900 dark:text-white">Gradebook</h2>
          <button @click="saveMarks" :disabled="savingMarks" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50">
            <Save class="w-4 h-4 mr-2" /> {{ savingMarks ? 'Saving...' : 'Save Marks' }}
          </button>
        </div>

        <div v-if="assessments.length === 0" class="text-center py-8 text-gray-500">
          Please define assessment components first before entering marks.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700 border border-gray-200 dark:border-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 border-r border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 sticky left-0 z-10">Student</th>
                <th v-for="comp in assessments" :key="comp.id" scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900 dark:text-gray-200 border-r border-gray-200 dark:border-gray-700">
                  {{ comp.name }}<br><span class="text-xs text-gray-500 font-normal">({{ comp.percentage }}%)</span>
                </th>
                <th scope="col" class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900 dark:text-gray-200">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="student in students" :key="student.id">
                <td class="whitespace-nowrap py-2 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-card-bg sticky left-0 z-10">
                  {{ formatName(student.name) }}
                </td>
                <td v-for="comp in assessments" :key="comp.id" class="p-0 border-r border-gray-200 dark:border-gray-700">
                  <input 
                    type="number" 
                    min="0" 
                    :max="comp.percentage" 
                    step="0.5"
                    v-model="studentMarks[student.id][comp.id]"
                    class="w-full h-full p-2 text-center text-sm border-0 focus:ring-1 focus:ring-inset focus:ring-blue-600 bg-transparent text-gray-900 dark:text-white"
                  />
                </td>
                <td class="whitespace-nowrap px-3 py-2 text-center text-sm font-bold" :class="getStudentTotal(student.id) >= 50 ? 'text-green-600' : 'text-red-600'">
                  {{ getStudentTotal(student.id).toFixed(1) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Attendance Tab -->
      <div v-else-if="activeTab === 'attendance'">
        <div class="text-center py-12">
          <Calendar class="mx-auto h-12 w-12 text-gray-400 mb-4" />
          <h2 class="text-lg font-medium text-gray-900 dark:text-white">Attendance Tracking</h2>
          <p class="text-gray-500 mt-2">Manage daily session attendance.</p>
          <button @click="createSession" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded shadow-sm text-white bg-blue-600 hover:bg-blue-700">
            Create Session
          </button>
        </div>
      </div>
      
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, Plus, Trash2, Save, Calendar } from 'lucide-vue-next';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const courseId = route.params.id;

const loading = ref(true);
const course = ref<any>(null);
const activeTab = ref('students');
const showAssessmentModal = ref(false);
const tabs = [
  { id: 'students', name: 'Students' },
  { id: 'assessments', name: 'Assessments' },
  { id: 'gradebook', name: 'Gradebook' },
  { id: 'attendance', name: 'Attendance' },
];

const students = ref<any[]>([]);
const assessments = ref<any[]>([]);
const marks = ref<any[]>([]);

// Structure: { studentId: { assessmentId: mark } }
const studentMarks = ref<Record<number, Record<number, number | null>>>({});

const savingMarks = ref(false);

const fetchData = async () => {
  loading.value = true;
  try {
    const courseRes = await axios.get(`/api/academic/courses/${courseId}`);
    course.value = courseRes.data;

    const studentsRes = await axios.get(`/api/academic/courses/${courseId}/students`);
    students.value = studentsRes.data;
    
    // Initialize marks grid
    students.value.forEach(s => {
        studentMarks.value[s.id] = {};
    });

    const assessRes = await axios.get(`/api/academic/courses/${courseId}/assessments`);
    assessments.value = assessRes.data;
    
    const marksRes = await axios.get(`/api/academic/courses/${courseId}/marks`);
    marks.value = marksRes.data;
    
    // Populate marks grid
    marks.value.forEach(m => {
        if(studentMarks.value[m.student_id]) {
            studentMarks.value[m.student_id][m.assessment_component_id] = parseFloat(m.marks_obtained);
        }
    });

  } catch (error) {
    console.error('Failed to load course data', error);
  } finally {
    loading.value = false;
  }
};

const totalPercentage = computed(() => {
    return assessments.value.reduce((acc, curr) => acc + parseFloat(curr.percentage), 0);
});

const getStudentTotal = (studentId: number) => {
    let total = 0;
    const sm = studentMarks.value[studentId];
    if(sm) {
        Object.values(sm).forEach(v => {
            if(v) total += parseFloat(v.toString());
        });
    }
    return total;
};

const formatGrade = (val: string) => {
  if (!val) return '';
  if (val === 'child') return 'Child / KG';
  if (val === 'post_12') return 'Post Grade 12';
  return `Grade ${val}`;
};

const formatName = (nameObj: any) => {
    if(!nameObj) return '';
    if(typeof nameObj === 'string') return nameObj.split(' ')[0];
    const n = nameObj.en || nameObj.am || nameObj.or || '';
    return n.split(' ')[0];
};

const deleteAssessment = async (id: number) => {
    if(!confirm('Delete this assessment component? This will erase all marks for it.')) return;
    try {
        await axios.delete(`/api/academic/courses/${courseId}/assessments/${id}`);
        fetchData();
    } catch(error) {
        alert('Failed to delete component');
    }
}

const saveMarks = async () => {
    savingMarks.value = true;
    const payload = [];
    
    for(const studentId in studentMarks.value) {
        for(const compId in studentMarks.value[studentId]) {
            const val = studentMarks.value[studentId][compId];
            if(val !== null && val !== undefined) {
                payload.push({
                    student_id: parseInt(studentId),
                    assessment_component_id: parseInt(compId),
                    marks_obtained: parseFloat(val.toString())
                });
            }
        }
    }
    
    try {
        await axios.post(`/api/academic/courses/${courseId}/marks`, { marks: payload });
        alert('Marks saved successfully');
    } catch(error) {
        console.error(error);
        alert('Failed to save marks');
    } finally {
        savingMarks.value = false;
    }
};

const createSession = () => {
    alert('Attendance session creation will be implemented here.');
};

onMounted(() => {
  fetchData();
});
</script>
