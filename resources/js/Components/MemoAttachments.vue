<script setup>
import { onBeforeUnmount, onMounted, reactive } from 'vue';
import { renderAsync } from 'docx-preview';
import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

GlobalWorkerOptions.workerSrc = pdfWorker;

const props = defineProps({
    attachments: { type: Array, default: () => [] },
    subject: { type: String, default: '' },
});

const docxContainers = {};
const docxObservers = {};
const pdfContainers = {};
const pdfLoadingTasks = {};
const docxLoading = reactive({});
const docxErrors = reactive({});
const pdfLoading = reactive({});
const pdfErrors = reactive({});
const attachmentUrl = (attachment) => '/storage/' + attachment.file_path;
const isImageAttachment = (attachment) => /\.(jpe?g|png|gif|webp)$/i.test(attachment.file_path || '');
const isPdfAttachment = (attachment) => /\.pdf$/i.test(attachment.file_path || '');
const isDocxAttachment = (attachment) => /\.docx?$/i.test(attachment.file_path || '');

const setDocxContainer = (id, element) => {
    if (element) docxContainers[id] = element;
    else delete docxContainers[id];
};

const setPdfContainer = (id, element) => {
    if (element) pdfContainers[id] = element;
    else delete pdfContainers[id];
};

const pdfUrl = (attachment) => attachment.preview_path
    ? '/storage/' + attachment.preview_path
    : attachmentUrl(attachment);

const renderPdf = async (attachment) => {
    const container = pdfContainers[attachment.id];
    if (!container) return;

    pdfLoading[attachment.id] = true;
    pdfErrors[attachment.id] = '';
    container.replaceChildren();

    try {
        const response = await fetch(pdfUrl(attachment), { credentials: 'same-origin' });
        if (!response.ok) throw new Error(`File PDF tidak dapat dimuat (${response.status}).`);

        const data = new Uint8Array(await response.arrayBuffer());
        const loadingTask = getDocument({ data });
        pdfLoadingTasks[attachment.id] = loadingTask;
        const document = await loadingTask.promise;

        for (let pageNumber = 1; pageNumber <= document.numPages; pageNumber += 1) {
            const page = await document.getPage(pageNumber);
            const cssViewport = page.getViewport({ scale: 1 });
            const renderViewport = page.getViewport({ scale: 1.5 });
            const canvas = window.document.createElement('canvas');
            const context = canvas.getContext('2d', { alpha: false });

            canvas.className = 'memo-pdf-page';
            canvas.width = renderViewport.width;
            canvas.height = renderViewport.height;
            canvas.style.width = `${cssViewport.width}px`;
            canvas.style.height = `${cssViewport.height}px`;
            canvas.setAttribute('aria-label', `Halaman ${pageNumber}`);
            container.appendChild(canvas);

            await page.render({ canvasContext: context, viewport: renderViewport }).promise;
        }
    } catch (error) {
        console.error('Memo attachment PDF preview failed.', attachment.id, error);
        pdfErrors[attachment.id] = error instanceof Error
            ? `Lampiran PDF gagal ditampilkan: ${error.message}`
            : 'Lampiran PDF tidak dapat ditampilkan.';
    } finally {
        pdfLoading[attachment.id] = false;
        delete pdfLoadingTasks[attachment.id];
    }
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
    props.attachments
        .filter((attachment) => isPdfAttachment(attachment) || (isDocxAttachment(attachment) && attachment.preview_path))
        .forEach((attachment) => renderPdf(attachment));

    props.attachments.filter((attachment) => isDocxAttachment(attachment) && !attachment.preview_path).forEach((attachment) => {
        docxLoading[attachment.id] = true;
        renderDocx(attachment);
    });
});

onBeforeUnmount(() => {
    Object.values(docxObservers).forEach((observer) => observer.disconnect());
    Object.values(pdfLoadingTasks).forEach((task) => task.destroy());
});
</script>

<template>
    <div v-if="attachments.length" class="memo-print-attachments w-full max-w-[210mm] print:max-w-none print:w-full">
        <div
            v-for="attachment in attachments"
            :key="'print-attachment-' + attachment.id"
            class="memo-print-attachment mt-6 w-full bg-white p-4 shadow-md print:mt-0 print:min-h-0 print:p-0 print:shadow-none"
        >
            <p v-if="subject" class="mb-2 text-center text-xs font-medium print:hidden">Perihal: {{ subject }}</p>
            <h2 :class="['mb-4 text-center text-sm font-bold print:mb-2', { 'print:hidden': isDocxAttachment(attachment) || isPdfAttachment(attachment) }]">Lampiran: {{ attachment.original_name || attachment.file_path }}</h2>
            <img
                v-if="isImageAttachment(attachment)"
                :src="attachmentUrl(attachment)"
                :alt="attachment.original_name || 'Lampiran memo'"
                class="mx-auto max-h-[250mm] max-w-full object-contain"
            />
            <div v-else-if="isPdfAttachment(attachment)" class="memo-pdf-pages">
                <div :ref="(element) => setPdfContainer(attachment.id, element)" class="memo-pdf-page-list"></div>
                <div v-if="pdfLoading[attachment.id]" data-attachment-loading class="py-12 text-center text-sm text-gray-500">Lampiran PDF sedang dimuat...</div>
                <div v-else-if="pdfErrors[attachment.id]" class="py-12 text-center text-sm text-red-600">
                    {{ pdfErrors[attachment.id] }}
                    <a :href="attachmentUrl(attachment)" target="_blank" class="ml-1 underline">Buka file</a>
                </div>
            </div>
            <div v-else-if="isDocxAttachment(attachment)" class="memo-attachment-docx overflow-x-auto print:overflow-visible">
                <template v-if="attachment.preview_path">
                    <div class="memo-pdf-pages">
                        <div :ref="(element) => setPdfContainer(attachment.id, element)" class="memo-pdf-page-list"></div>
                        <div v-if="pdfLoading[attachment.id]" data-attachment-loading class="py-12 text-center text-sm text-gray-500">Preview PDF sedang dimuat...</div>
                        <div v-else-if="pdfErrors[attachment.id]" class="py-12 text-center text-sm text-red-600">Preview PDF tidak dapat ditampilkan.</div>
                    </div>
                </template>
                <template v-else>
                    <div :ref="(element) => setDocxContainer(attachment.id, element)"></div>
                    <div v-if="docxLoading[attachment.id]" data-attachment-loading class="py-12 text-center text-sm text-gray-500">
                        Lampiran sedang disiapkan untuk dicetak...
                    </div>
                    <div v-else-if="docxErrors[attachment.id]" class="py-12 text-center text-sm text-red-600">
                        {{ docxErrors[attachment.id] }}
                    </div>
                    <p class="mt-2 text-xs text-amber-700 print:hidden">Preview DOCX dapat berbeda dari format Word asli karena konversi PDF belum tersedia.</p>
                </template>
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

.memo-pdf-pages {
    padding: 12px;
    background: #f3f4f6;
}

.memo-pdf-page-list {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.memo-pdf-page {
    display: block;
    max-width: 100%;
    height: auto;
    background: #fff;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.16);
}

@media print {
    .memo-pdf-pages {
        padding: 0;
        background: #fff;
    }

    .memo-pdf-page-list {
        gap: 0;
    }

    .memo-pdf-page {
        box-shadow: none;
        max-height: 295mm;
    }
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
        min-height: 0 !important;
        height: 295mm !important;
        break-after: page;
        page-break-after: always;
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .memo-attachment-docx .docx-wrapper > section.docx:last-of-type {
        break-after: auto;
        page-break-after: auto;
        margin-bottom: 0;
    }

    .memo-print-attachment:last-child {
        break-after: avoid;
        page-break-after: avoid;
    }
}
</style>