<script setup>
import { onMounted, reactive } from 'vue';
import * as mammoth from 'mammoth/mammoth.browser';

const props = defineProps({
    attachments: { type: Array, default: () => [] },
    subject: { type: String, default: '' },
});

const docxHtml = reactive({});
const attachmentUrl = (attachment) => '/storage/' + attachment.file_path;
const isImageAttachment = (attachment) => /\.(jpe?g|png|gif|webp)$/i.test(attachment.file_path || '');
const isPdfAttachment = (attachment) => /\.pdf$/i.test(attachment.file_path || '');
const isDocxAttachment = (attachment) => /\.docx?$/i.test(attachment.file_path || '');

const renderDocx = async (attachment) => {
    try {
        const response = await fetch(attachmentUrl(attachment));
        const arrayBuffer = await response.arrayBuffer();
        const result = await mammoth.convertToHtml({ arrayBuffer });
        docxHtml[attachment.id] = result.value;
    } catch {
        docxHtml[attachment.id] = '<p>Lampiran Word tidak dapat ditampilkan.</p>';
    }
};

onMounted(() => {
    props.attachments.filter(isDocxAttachment).forEach(renderDocx);
});
</script>

<template>
    <div v-if="attachments.length" class="memo-print-attachments w-full max-w-[210mm] print:max-w-none print:w-full">
        <div
            v-for="attachment in attachments"
            :key="'print-attachment-' + attachment.id"
            class="memo-print-attachment mt-6 min-h-[270mm] w-full bg-white p-6 shadow-md print:mt-0 print:min-h-0 print:p-0 print:shadow-none"
            style="page-break-before: always; break-before: page;"
        >
            <p v-if="subject" class="mb-2 text-center text-xs font-medium print:mb-1">Perihal: {{ subject }}</p>
            <h2 class="mb-4 text-center text-sm font-bold print:mb-2">Lampiran: {{ attachment.original_name || attachment.file_path }}</h2>
            <img
                v-if="isImageAttachment(attachment)"
                :src="attachmentUrl(attachment)"
                :alt="attachment.original_name || 'Lampiran memo'"
                class="mx-auto max-h-[250mm] max-w-full object-contain"
            />
            <iframe
                v-else-if="isPdfAttachment(attachment)"
                :src="attachmentUrl(attachment)"
                title="Lampiran PDF"
                class="h-[250mm] w-full border-0"
            ></iframe>
            <div v-else-if="isDocxAttachment(attachment) && docxHtml[attachment.id]" class="memo-attachment-docx" v-html="docxHtml[attachment.id]"></div>
            <div v-else-if="isDocxAttachment(attachment)" data-attachment-loading class="py-12 text-center text-sm text-gray-500">
                Lampiran sedang disiapkan untuk dicetak...
            </div>
            <div v-else class="py-12 text-center text-sm text-gray-500">
                <p class="mb-3">Format lampiran ini tidak dapat ditampilkan langsung di browser.</p>
                <a :href="attachmentUrl(attachment)" target="_blank" class="text-blue-600 underline print:text-black">
                    Buka lampiran {{ attachment.original_name || attachment.file_path }}
                </a>
            </div>
        </div>
    </div>
</template>

<style>
.memo-attachment-docx {
    color: #000;
    font-family: Tahoma, Arial, sans-serif;
    font-size: 12px;
    line-height: 1.4;
}

.memo-attachment-docx img {
    max-width: 100%;
}

.memo-attachment-docx table {
    width: 100%;
    border-collapse: collapse;
}

.memo-attachment-docx td,
.memo-attachment-docx th {
    border: 1px solid #000;
    padding: 4px;
}
</style>