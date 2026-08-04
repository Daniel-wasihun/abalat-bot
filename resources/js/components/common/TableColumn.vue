<script setup lang="ts">
/**
 * TableColumn — Declarative column definition for DataTable.
 *
 * Works purely as a "virtual" component. It doesn't render HTML directly,
 * but instead provides configuration metadata and scoped slots to its parent DataTable.
 *
 * @example
 * <DataTable :items="users">
 * <TableColumn field="name" header="Full Name" sortable />
 * <TableColumn field="status" align="center">
 * <template #default="{ row }">
 * <StatusBadge :status="row.status" />
 * </template>
 * </TableColumn>
 * </DataTable>
 */
import { defineComponent } from "vue";

// Define the name explicitly so DataTable can find it in the slot tree
defineOptions({
 name: "TableColumn",
});

interface Props {
 /** The data key in your row object */
 field?: string;
 /** The text shown in the table header */
 header?: string;
 /** Text alignment */
 align?: "left" | "center" | "right";
 /** Width class or px/rem string */
 width?: string;
 /** Enable sort controls */
 sortable?: boolean;
}

withDefaults(defineProps<Props>(), {
 align: "left",
 sortable: false,
});

// Provides the #default scoped slot to the parent (returns { row, value, index })
defineSlots<{
 default(props: { row: any; value: any; index: number }): any;
 header(props: {}): any;
}>();
</script>

<template>
 <!-- This component is "virtual". DataTable extracts props and slots directly from the VNode.
 We don't render the slot here to avoid executing cell logic with empty data (ghost render). -->
</template>
