import { getCurrentInstance, onBeforeUnmount, ref, watch } from 'vue';

/**
 * Debounce sebuah fungsi: hanya dijalankan setelah `delay` ms tanpa panggilan baru.
 * Dilengkapi `.cancel()` dan `.flush()`.
 */
export function debounce(fn, delay = 300) {
    let timer = null;
    let lastArgs = null;

    const debounced = (...args) => {
        lastArgs = args;
        if (timer) clearTimeout(timer);
        timer = setTimeout(() => {
            timer = null;
            fn(...lastArgs);
        }, delay);
    };

    debounced.cancel = () => {
        if (timer) clearTimeout(timer);
        timer = null;
    };

    debounced.flush = () => {
        if (!timer) return;
        debounced.cancel();
        fn(...lastArgs);
    };

    return debounced;
}

/**
 * Membuat ref turunan yang nilainya mengikuti `source` dengan jeda `delay` ms.
 * Cocok untuk input pencarian supaya filter/request tidak jalan di setiap ketikan.
 * Nilai kosong ('' / null) langsung diterapkan agar tombol "reset" terasa instan.
 */
export function useDebouncedRef(source, delay = 300) {
    const debounced = ref(source.value);
    const update = debounce((value) => { debounced.value = value; }, delay);

    watch(source, (value) => {
        if (value === '' || value === null || value === undefined) {
            update.cancel();
            debounced.value = value;
            return;
        }
        update(value);
    });

    if (getCurrentInstance()) onBeforeUnmount(update.cancel);

    return debounced;
}
