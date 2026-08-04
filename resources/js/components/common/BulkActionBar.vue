<script setup lang="ts">
import { getCurrentInstance } from "vue";
import { CheckCircle2, XCircle, Trash2 } from "lucide-vue-next";
import Button from "./Button.vue";
import TableSelection from "./TableSelection.vue";

const props = defineProps<{
 count: number;
 canEdit?: boolean;
 canDelete?: boolean;
}>();

const emit = defineEmits<{
 (e: "clear"): void;
 (e: "activate"): void;
 (e: "deactivate"): void;
 (e: "delete"): void;
}>();

const { proxy } = getCurrentInstance() as any;
const $tr = proxy.$tr;
</script>

<template>
 <TableSelection
 :count="count"
 :label="$tr('common.selected')"
 @clear="emit('clear')">
 <Button
 v-if="canEdit"
 variant="soft-success"
 size="md"
 :icon="CheckCircle2"
 @click="emit('activate')">
 {{ $tr("common.activate") }}
 </Button>

 <Button
 v-if="canEdit"
 variant="soft-warning"
 size="md"
 :icon="XCircle"
 @click="emit('deactivate')">
 {{ $tr("common.deactivate") }}
 </Button>

 <Button
 v-if="canDelete"
 variant="soft-danger"
 size="md"
 :icon="Trash2"
 @click="emit('delete')">
 {{ $tr("common.delete") }}
 </Button>
 </TableSelection>
</template>
