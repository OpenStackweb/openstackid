import {isTwoFactorActive} from '../../../resources/js/profile/two_factor_state';

describe('isTwoFactorActive', () => {
    it('is on when the stored flag is on', () => {
        expect(isTwoFactorActive(true, false)).toBe(true);
    });

    it('is on for a group-enforced user whose stored flag is off', () => {
        expect(isTwoFactorActive(false, true)).toBe(true);
    });

    it('is off when neither applies', () => {
        expect(isTwoFactorActive(false, false)).toBe(false);
        expect(isTwoFactorActive(undefined, undefined)).toBe(false);
    });
});
