import React from "react";
import moment from "moment";
import Checkbox from "@material-ui/core/Checkbox";
import FormControlLabel from "@material-ui/core/FormControlLabel";
import FormHelperText from "@material-ui/core/FormHelperText";

export const ENFORCED_BY_GROUP_TEXT = "Enforced by group membership";
export const OWN_ACCOUNT_TEXT = "Manage your own two-factor authentication from your profile";

/**
 * 2FA checkbox of the admin edit user form. A user enforced by group membership is
 * required to complete 2FA whatever the stored flag says, so the box shows checked and
 * disabled: nobody can switch it off. The stored flag is what the form submits.
 * On the admin's own account it is read only too: the user CRUD ignores the key when an
 * admin edits themselves, so turning 2FA off must go through the profile (password).
 */
const TwoFactorCheckbox = ({enabled, enforced = false, enforcedAt = null, ownAccount = false, onChange}) => {
    const readOnly = !!enforced || !!ownAccount;
    return (
    <>
        <FormControlLabel
            control={<Checkbox name="two_factor_enabled"
                               id="two_factor_enabled"
                               checked={!!enabled || !!enforced}
                               disabled={readOnly}
                               onChange={(e) => { if (!readOnly && onChange) onChange(e); }}
                               inputProps={{"data-testid": "two-factor-checkbox"}}
                               color="primary"/>}
            label="Two-Factor Authentication (Email OTP)"
        />
        {
            enforced &&
            <FormHelperText data-testid="two-factor-enforced-text">{ENFORCED_BY_GROUP_TEXT}</FormHelperText>
        }
        {
            !enforced && ownAccount &&
            <FormHelperText data-testid="two-factor-own-account-text">{OWN_ACCOUNT_TEXT}</FormHelperText>
        }
        {
            !!enforcedAt &&
            <FormHelperText data-testid="two-factor-enforced-at">
                Enforced since {moment.unix(Number(enforcedAt)).utc().format("YYYY-MM-DD HH:mm")} UTC
            </FormHelperText>
        }
    </>
    );
};

export default TwoFactorCheckbox;
