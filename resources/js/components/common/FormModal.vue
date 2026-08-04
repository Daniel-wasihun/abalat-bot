<script setup lang="ts">
/**
 * FormModal — A reusable wrapper around Modal designed specifically for
 * Create and Edit forms.
 *
 * It automatically wraps the content in a `<form>` tag, handles the "Submit" and "Cancel"
 * footer buttons, and manages the disabled/loading states natively.
 *
 * @example
 * <FormModal
 * :show="showEditModal"
 * title="Edit Book"
 * :loading="isSaving"
 * @close="showEditModal = false"
 * @submit="saveBook"
 * >
 * <div class="space-y-4">
 * <FormField label="Title" v-model="form.title" />
 * <FormSelect label="Status" v-model="form.status" :options="statusOpts" />
 * </div>
 * </FormModal>
 */
import Modal from "./Modal.vue";
import Button from "./Button.vue";

interface Props {
 show: boolean;
 title: string;
 /** Pass an icon (like Edit or Plus) for the header badge */
 icon?: any;
 /** Sets the max-width of the modal. */
 size?: "sm" | "md" | "lg" | "xl";
 /** Shows a loading spinner on the submit button and disables all form inputs */
 loading?: boolean;
 /** Override the text on the primary submit button. Default: 'Save' */
 submitText?: string;
 /** Override the text on the secondary cancel button. Default: 'Cancel' */
 cancelText?: string;
 /** Disable the submit button natively (e.g. if form validations fail before submit) */
 submitDisabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
 size: "md",
 loading: false,
 submitText: "",
 cancelText: "",
 submitDisabled: false,
});

const emit = defineEmits<{
 (e: "close"): void;
 (e: "submit"): void;
}>();

function onCancel() {
 if (props.loading) return; // Prevent closing while fetching
 emit("close");
}

function onSubmit() {
 if (props.loading || props.submitDisabled) return;
 emit("submit");
}
</script>

<template>
 <Modal
 :show="show"
 :title="title"
 :icon="icon"
 :size="size"
 @close="onCancel"
 class="form-modal"
 no-padding>
 <!-- Use a native form tag to capture 'Enter' key presses normally -->
 <form
 @submit.prevent="onSubmit"
 class="flex flex-col h-full overflow-hidden">
 <!-- Form Body (Slot) -->
 <div
 class="px-6 py-4 flex-1 overflow-y-auto custom-scrollbar"
 :class="{ 'opacity-60 pointer-events-none': loading }">
 <slot />
 </div>

 <div
 class="px-6 py-5 border-t border-card-border flex items-center justify-end gap-3 shrink-0 bg-card-bg">
 <!-- Optional left-aligned actions (e.g. "Delete" button inside an edit modal) -->
 <div class="mr-auto">
 <slot name="footer-left" />
 </div>
 
 <Button class="font-bold tracking-tight capitalize px-6 border-card-border/60"
 type="button"
 variant="secondary"
 size="md"
 :disabled="loading"
 @click="onCancel">
 {{ $tr("common.cancel") }}
 </Button>

 <Button
 type="submit"
 variant="primary"
 size="md"
 :loading="loading"
 :disabled="submitDisabled"
 class="tracking-tight min-w-[140px]">
 {{ submitText || $tr("common.save") }}
 </Button>
 </div>
 </form>
 </Modal>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
 width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
 background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
 background: rgba(0, 0, 0, 0.1);
 border-radius: 10px;
}
</style>
