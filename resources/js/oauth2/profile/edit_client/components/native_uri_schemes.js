/**
 * Native-client URI scheme policy, client side. Mirrors Client::isDisallowedNativeUriScheme() on the
 * backend: window.DISALLOWED_NATIVE_URI_SCHEMES and window.NATIVE_LOOPBACK_HOSTS are injected
 * server-side from IClient::DISALLOWED_NATIVE_URI_SCHEMES / IClient::NATIVE_LOOPBACK_HOSTS (see
 * edit-client.blade.php) - the deny-list has one owner, not two. Single module so every URI field's
 * inline validator (redirect_uris, post_logout_redirect_uris) shares one implementation instead of
 * hand-rolling copies that drift.
 */

export const isDisallowedNativeUriScheme = (protocol, host) => {
    const scheme = protocol.toLowerCase().replace(/:$/, '');
    if (scheme === 'http') {
        return !(window.NATIVE_LOOPBACK_HOSTS || []).includes((host || '').toLowerCase());
    }
    return (window.DISALLOWED_NATIVE_URI_SCHEMES || []).includes(scheme);
}

/**
 * Inline validity of a single URI for a Native client's URI-bearing fields: https always passes;
 * anything else passes unless its scheme is deny-listed (with the RFC 8252 http-loopback carve-out).
 * Matches the backend write-time rule (ClientService::assertNativeCustomSchemesAllowed).
 */
export const isValidNativeUri = (value) => {
    try {
        const url = new URL(value);
        if (url.protocol === 'https:') return true;
        if (isDisallowedNativeUriScheme(url.protocol, url.hostname)) return false;
        // opaque URIs (scheme:data - no authority AND no rooted path, e.g. the one-character typo
        // "com.example.app:oauth2redirect") have no location to redirect to; the runtime matcher can
        // never canonicalize them, and the backend rejects them at write time with a 412 - agree inline.
        return url.hostname !== '' || url.pathname.startsWith('/');
    } catch (err) {
        return false;
    }
}
