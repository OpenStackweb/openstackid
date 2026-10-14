import { emailValidator } from './validator';

describe('emailValidator', () => {
    it('accepts regular emails', () => {
        expect(emailValidator('john@example.com')).toBe(true);
    });

    it('rejects emails with inner spaces', () => {
        expect(emailValidator('john doe@example.com')).toBe(false);
        expect(emailValidator('john@exa mple.com')).toBe(false);
    });

    it('rejects values without @', () => {
        expect(emailValidator('john.example.com')).toBe(false);
    });
});
