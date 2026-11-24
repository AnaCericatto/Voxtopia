export const pieceKinds = ['cube', 'shape', 'decor'] as const;
export type PieceKind = (typeof pieceKinds)[number];

export const pieceVisibilities = ['private', 'public'] as const;
export type PieceVisibility = (typeof pieceVisibilities)[number];

export const buildVisibilities = ['private', 'unlisted', 'public'] as const;
export type BuildVisibility = (typeof buildVisibilities)[number];
