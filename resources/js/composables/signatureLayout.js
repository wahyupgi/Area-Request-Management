export const signatureColumnFractions = (slots, getName = (slot) => slot?.name) => {
    const nameLengths = slots.map((slot) => String(getName(slot) || '').trim().length);
    if (!nameLengths.some((length) => length > 12)) return null;

    return nameLengths.map((length) => Math.min(2.25, Math.max(1, length / 12)));
};
