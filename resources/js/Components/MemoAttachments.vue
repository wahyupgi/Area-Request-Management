<script setup>
import { onBeforeUnmount, onMounted, reactive } from 'vue';
import { renderAsync } from 'docx-preview';

const props = defineProps({
    attachments: { type: Array, default: () => [] },
    subject: { type: String, default: '' },
});

const docxContainers = {};
const docxObservers = {};
const docxLoading = reactive({});
const docxErrors = reactive({});
const attachmentUrl = (attachment) => '/storage/' + attachment.file_path;
const isImageAttachment = (attachment) => /\.(jpe?g|png|gif|webp)$/i.test(attachment.file_path || '');
const isPdfAttachment = (attachment) => /\.pdf$/i.test(attachment.file_path || '');
const isDocxAttachment = (attachment) => /\.docx?$/i.test(attachment.file_path || '');

const setDocxContainer = (id, element) => {
    if (element) docxContainers[id] = element;
    else delete docxContainers[id];
};

const fitDocxPages = (container, pageWidth) => {
    const preview = container.parentElement;
    if (!preview) return;
    const availableWidth = preview.clientWidth;
    if (pageWidth > 0 && availableWidth > 0) {
        const scale = Math.min(1, availableWidth / pageWidth);
        container.style.setProperty('--docx-preview-scale', String(scale));
    }
};

const keepAnchoredImagesOnPage = (container) => {
    container.querySelectorAll('.docx-wrapper > section.docx').forEach((page) => {
        const pageRect = page.getBoundingClientRect();
        const zoom = Number.parseFloat(window.getComputedStyle(page).zoom) || 1;

        page.querySelectorAll('img').forEach((image) => {
            const imageRect = image.getBoundingClientRect();
            if (imageRect.top >= pageRect.top) return;

            let drawing = image.parentElement;
            while (drawing && drawing !== page && !drawing.style.top) {
                drawing = drawing.parentElement;
            }
            if (!drawing || drawing === page) return;

            page.style.overflow = 'visible';
            const offset = (pageRect.top + 4 - imageRect.top) / zoom;
            drawing.style.top = `calc(${drawing.style.top} + ${offset}px)`;
        });
    });
};

const renderDocx = async (attachment) => {
    try {
        const response = await fetch(attachmentUrl(attachment));
        if (!response.ok) throw new Error('DOCX fetch failed');
        const arrayBuffer = await response.arrayBuffer();
        const container = docxContainers[attachment.id];
        if (!container) throw new Error('DOCX container unavailable');
        await renderAsync(arrayBuffer, container, container, {
            breakPages: true,
            ignoreLastRenderedPageBreak: false,
        });
        container.querySelectorAll('.docx-wrapper > section.docx').forEach((page) => {
            const hasNegativePositionedContent = Array.from(page.querySelectorAll('[style]')).some((element) => {
                const top = Number.parseFloat(element.style.top);
                return Number.isFinite(top) && top < 0;
            });
            if (hasNegativePositionedContent) page.style.overflow = 'visible';
        });
        const page = container.querySelector('.docx-wrapper > section.docx');
        const preview = container.parentElement;
        if (page && preview) {
            const pageWidth = page.getBoundingClientRect().width;
            fitDocxPages(container, pageWidth);
            keepAnchoredImagesOnPage(container);
            if (window.ResizeObserver) {
                let previousWidth = preview.clientWidth;
                docxObservers[attachment.id] = new ResizeObserver(() => {
                    const currentWidth = preview.clientWidth;
                    if (currentWidth !== previousWidth) {
                        previousWidth = currentWidth;
                        fitDocxPages(container, pageWidth);
                        keepAnchoredImagesOnPage(container);
                    }
                });
            docxObservers[attachment.id].observe(preview);
            }
        }
    } catch {
        docxErrors[attachment.id] = 'Lampiran Word tidak dapat ditampilkan.';
    } finally {
        docxLoading[attachment.id] = false;
    }
};

onMounted(() => {
    props.attachments.filter(isDocxAttachment).forEach((attachment) => {
        docxLoading[attachment.id] = true;
        renderDocx(attachment);
    });
});

onBeforeUnmount(() => {
    Object.values(docxObservers).forEach((observer) => observer.disconnect());
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
            <p v-if="subject" :class="['mb-2 text-center text-xs font-medium print:mb-1', { 'print:hidden': isDocxAttachment(attachment) }]">Perihal: {{ subject }}</p>
            <h2 :class="['mb-4 text-center text-sm font-bold print:mb-2', { 'print:hidden': isDocxAttachment(attachment) }]">Lampiran: {{ attachment.original_name || attachment.file_path }}</h2>
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
            <div v-else-if="isDocxAttachment(attachment)" class="memo-attachment-docx overflow-x-auto print:overflow-visible">
                <div :ref="(element) => setDocxContainer(attachment.id, element)"></div>
                <div v-if="docxLoading[attachment.id]" data-attachment-loading class="py-12 text-center text-sm text-gray-500">
                    Lampiran sedang disiapkan untuk dicetak...
                </div>
                <div v-else-if="docxErrors[attachment.id]" class="py-12 text-center text-sm text-red-600">
                    {{ docxErrors[attachment.id] }}
                    <a :href="attachmentUrl(attachment)" target="_blank" class="ml-1 underline">Unduh file</a>
                </div>
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
    background: #fff;
}

.memo-attachment-docx .docx-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0;
    background: #fff;
}

.memo-attachment-docx .docx-wrapper > section.docx {
    margin: 0 auto 12px;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.16);
    zoom: var(--docx-preview-scale, 1);
}

@media print {
    .memo-attachment-docx .docx-wrapper {
        padding: 0;
        background: #fff;
    }

    .memo-attachment-docx .docx-wrapper > section.docx {
        margin: 0 auto;
        box-shadow: none;
        zoom: 1 !important;
        break-after: page;
        page-break-after: always;
    }
}
</style>