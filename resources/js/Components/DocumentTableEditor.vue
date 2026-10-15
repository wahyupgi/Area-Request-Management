<script setup>
import { DeleteOutlined, PlusOutlined } from '@ant-design/icons-vue';

const props = defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, required: true },
});
const emit = defineEmits(['update:columns', 'update:rows']);
let nextColumnId = 0;

const updateColumn = (columnIndex, key, value) => {
    emit('update:columns', props.columns.map((column, index) => (
        index === columnIndex ? { ...column, [key]: value } : column
    )));
};

const addColumn = () => {
    const key = `custom_${Date.now()}_${nextColumnId++}`;
    emit('update:columns', [...props.columns, { key, label: '', type: 'text', required: false }]);
    emit('update:rows', props.rows.map((row) => ({ ...row, [key]: '' })));
};

const removeColumn = (columnIndex) => {
    if (props.columns.length <= 1) return;
    const key = props.columns[columnIndex].key;
    emit('update:columns', props.columns.filter((_, index) => index !== columnIndex));
    emit('update:rows', props.rows.map((row) => {
        const { [key]: removed, ...remaining } = row;
        return remaining;
    }));
};

const updateCell = (rowIndex, columnKey, value) => {
    emit('update:rows', props.rows.map((row, index) => (
        index === rowIndex ? { ...row, [columnKey]: value } : row
    )));
};

const addRow = () => {
    const row = Object.fromEntries(props.columns.map((column) => [column.key, '']));
    emit('update:rows', [...props.rows, row]);
};

const removeRow = (rowIndex) => {
    if (props.rows.length <= 1) return;
    emit('update:rows', props.rows.filter((_, index) => index !== rowIndex));
};
</script>

<template>
    <div>
        <p class="mb-4 text-sm text-gray-500">Atur nama kolom dan isi rincian, lalu tambahkan kolom atau baris sesuai kebutuhan.</p>

        <div class="mb-5 space-y-3">
            <div
                v-for="(column, columnIndex) in columns"
                :key="column.key"
                class="flex items-end gap-2"
            >
                <a-form-item :label="`Nama Kolom ${columnIndex + 1}`" class="mb-0 flex-1">
                    <a-input
                        :value="column.label"
                        placeholder="Contoh: Uraian"
                        @update:value="updateColumn(columnIndex, 'label', $event)"
                    />
                </a-form-item>
                <a-button
                    danger
                    type="text"
                    :disabled="columns.length <= 1"
                    :aria-label="`Hapus kolom ${columnIndex + 1}`"
                    @click="removeColumn(columnIndex)"
                >
                    <DeleteOutlined />
                </a-button>
            </div>
            <a-button type="dashed" block @click="addColumn">
                <template #icon><PlusOutlined /></template>
                Tambah Kolom
            </a-button>
        </div>

        <div
            v-for="(row, rowIndex) in rows"
            :key="`row-${rowIndex}`"
            class="mb-4 rounded-lg border border-gray-200 p-4"
        >
            <div class="mb-3 flex items-center justify-between border-b pb-2">
                <h3 class="mb-0 font-semibold">Baris {{ rowIndex + 1 }}</h3>
                <a-button
                    v-if="rows.length > 1"
                    danger
                    type="text"
                    size="small"
                    @click="removeRow(rowIndex)"
                >
                    <template #icon><DeleteOutlined /></template>
                    Hapus Baris
                </a-button>
            </div>
            <a-form-item
                v-for="column in columns"
                :key="column.key"
                :label="column.label || 'Kolom tanpa nama'"
                class="mb-3"
            >
                <a-textarea
                    :value="row[column.key]"
                    :auto-size="{ minRows: 1, maxRows: 4 }"
                    @update:value="updateCell(rowIndex, column.key, $event)"
                />
            </a-form-item>
        </div>

        <a-button type="dashed" block @click="addRow">
            <template #icon><PlusOutlined /></template>
            Tambah Baris
        </a-button>
    </div>
</template>
