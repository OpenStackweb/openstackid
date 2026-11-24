/**
 * Whether the account is subject to 2FA at this moment: the stored flag the user switched on,
 * or the enforcement derived from group membership (which applies whatever the stored flag
 * says). The trusted devices of the profile depend on this, not on the stored flag alone.
 */
export const isTwoFactorActive = (twoFactorEnabled, twoFactorEnforced) =>
    !!twoFactorEnabled || !!twoFactorEnforced;
