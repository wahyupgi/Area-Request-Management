import Swal from 'sweetalert2';

const baseOptions = {
    buttonsStyling: false,
    customClass: {
        confirmButton: 'swal-confirm-button',
        cancelButton: 'swal-cancel-button',
    },
};

export function useSweetAlert() {
    const toast = (icon, title) => Swal.fire({
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

    const confirm = async (title, text = 'Tindakan ini tidak dapat dibatalkan.') => {
        const result = await Swal.fire({
            ...baseOptions,
            icon: 'warning',
            title,
            text,
            showCancelButton: true,
            confirmButtonText: 'Ya, lanjutkan',
            cancelButtonText: 'Batal',
            reverseButtons: true,
            focusCancel: true,
        });

        return result.isConfirmed;
    };

    return { success, error, info, confirm };
}
