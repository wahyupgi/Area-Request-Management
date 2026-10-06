const allowedControlKeys = new Set([
    'Backspace',
    'Delete',
    'Tab',
    'Enter',
    'Escape',
    'ArrowLeft',
    'ArrowRight',
    'ArrowUp',
    'ArrowDown',
    'Home',
    'End',
]);

export const restrictToDigits = (event) => {
    if (event.ctrlKey || event.metaKey || allowedControlKeys.has(event.key)) return;
    if (/^\d$/.test(event.key)) return;
    event.preventDefault();
};

export const preventNonDigitPaste = (event) => {
    const pastedText = event.clipboardData?.getData('text') ?? '';
    if (!/^\d+$/.test(pastedText)) event.preventDefault();
};
