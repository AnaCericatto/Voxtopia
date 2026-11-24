import { describe, expect, it } from 'vite-plus/test';

import { cn } from '@/lib/utils';

describe('cn', () => {
    it('joins class names and skips falsy values', () => {
        expect(cn('a', false, 'c')).toBe('a c');
    });

    it('lets the later Tailwind class win', () => {
        expect(cn('p-2', 'p-4')).toBe('p-4');
    });
});
