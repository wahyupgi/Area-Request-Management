const longSignerName = 'nugroho samudra sujatmiko';

export const signatureColumnFractions = (slots, getName = (slot) => slot?.name) => {
    const hasLongSigner = slots.some((slot) => String(getName(slot) || '').toLowerCase().includes(longSignerName));
    if (!hasLongSigner) return null;

    return slots.map((slot) => (
        String(getName(slot) || '').toLowerCase().includes(longSignerName) ? 2.25 : 1
    ));
};
