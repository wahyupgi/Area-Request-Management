export const roleLines = (role) => {
    const value = String(role || '').trim();
    const match = value.match(/bisnis\s*(?:dan|&)\s*operasional/i);

    if (match && match.index > 0) {
        return [value.slice(0, match.index).trim(), value.slice(match.index).trim()];
    }

    return [value];
};

export const shortManagerName = (name) => {
    const clean = String(name || '').trim();
    if (!clean) return '';

    const parts = clean.replace(/^Bpk\.?\s*/i, '').split(/\s+/).filter(Boolean);
    if (parts.length < 2) return clean;

    return `Bpk. ${parts[0]} ${parts[parts.length - 1][0].toUpperCase()}`;
};

export const managerDisplayName = (name, columnCount) => (columnCount >= 4 ? shortManagerName(name) : String(name || ''));

export const signatureColumnFractions = (slots, getName = (slot) => slot?.name) => {
    const nameLengths = slots.map((slot) => String(getName(slot) || '').trim().length);
    if (!nameLengths.some((length) => length > 12)) return null;

    return nameLengths.map((length) => Math.min(2.25, Math.max(1, length / 12)));
};
