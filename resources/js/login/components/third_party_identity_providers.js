import React from 'react';
import DividerWithText from '../../components/divider_with_text';
import Button from '@material-ui/core/Button';
import {handleThirdPartyProvidersVerbiage} from '../../utils';
import styles from '../login.module.scss';
import '../third_party_identity_providers.scss';

const ThirdPartyIdentityProviders = ({ thirdPartyProviders, formAction, disableInput, allowNativeAuth }) => {
    return (
        <>
            {allowNativeAuth && <DividerWithText>or</DividerWithText>}
            {
                thirdPartyProviders.map((provider) => {
                    const verbiage = `${handleThirdPartyProvidersVerbiage(provider.name)} with ${provider.label}`;
                    return (
                        <Button
                            disabled={disableInput}
                            key={provider.name}
                            variant="contained"
                            className={styles.third_party_idp_button + ` ${provider.name}`}
                            color="primary"
                            target="_self"
                            title={verbiage}
                            href={`${formAction}/${provider.name}`}>
                            {verbiage}
                        </Button>
                    );
                })
            }
            <p>If you have a login, you may still choose to use a social login with <b>the same email address</b> to
                access your account.</p>
        </>
    );
}

export default ThirdPartyIdentityProviders;
