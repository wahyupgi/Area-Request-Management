// Lazy loading: sweetalert2 baru diunduh saat alert pertama kali ditampilkan.
let swalPromise = null;
const loadSwal = () => {
    if (!swalPromise) {
        swalPromise = import('sweetalert2').then((module) => module.default).catch((error) => {
            swalPromise = null;
            throw error;
        });
    }
    return swalPromise;
};

const fire = async (options) => (await loadSwal()).fire(options);

const baseOptions = {
    buttonsStyling: false,
    customClass: {
        confirmButton: 'swal-confirm-button',
        cancelButton: 'swal-cancel-button',
    },
};

export function useSweetAlert() {
    const toast = (icon, title) => fire({
        ...baseOptions,
        toast: true,
        position: 'top-end',
        icon,
        title,
        showConfirmButton: false,
        timer: 3200,
        timerProgressBar: true,
    });

    const success = (title) => toast('success', title);
    const error = (title) => toast('error', title);
    const info = (title) => toast('info', title);

    const confirm = async (title, text = 'Tindakan ini tidak dapat dibatalkan.', confirmText = 'Ya, lanjutkan') => {
        const result = await fire({
            ...baseOptions,
            icon: 'warning',
            title,
            text,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'Batal',
            reverseButtons: true,
            focusCancel: true,
        });

        return result.isConfirmed;
    };

    return { success, error, info, confirm };
}
