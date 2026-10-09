import { h, reactive } from 'vue';
import { DoubleLeftOutlined, DoubleRightOutlined } from '@ant-design/icons-vue';

export function createTablePagination(options = {}) {
    let pagination;
    const { getTotal, onChange, ...paginationOptions } = options;

    const pageCount = () => Math.max(1, Math.ceil((getTotal?.() ?? paginationOptions.total ?? 0) / pagination.pageSize));
    const goToPage = (page) => {
        const target = Math.min(Math.max(page, 1), pageCount());
        pagination.current = target;
        onChange?.(target, pagination.pageSize);
    };

    pagination = reactive({
        pageSize: 10,
        showSizeChanger: false,
        hideOnSinglePage: true,
        ...paginationOptions,
        current: options.current ?? 1,
        itemRender: ({ type, originalElement }) => {
            if (type !== 'prev' && type !== 'next') return originalElement;

            const first = type === 'prev';
            const edgeButton = h('button', {
                type: 'button',
                class: 'table-pagination-edge-button',
                'aria-label': first ? 'Halaman pertama' : 'Halaman terakhir',
                disabled: first ? pagination.current <= 1 : pagination.current >= pageCount(),
                onClick: (event) => {
                    event.stopPropagation();
                    goToPage(first ? 1 : pageCount());
                },
            }, [h(first ? DoubleLeftOutlined : DoubleRightOutlined)]);

            return h('span', { class: 'table-pagination-control-group' }, first
                ? [edgeButton, originalElement]
                : [originalElement, edgeButton]);
        },
        onChange: (page, pageSize) => {
            pagination.current = page;
            onChange?.(page, pageSize);
        },
    });

    return pagination;
}
