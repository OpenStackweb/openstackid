import {isDisallowedNativeUriScheme, isValidNativeUri} from "./native_uri_schemes";

// the module reads the backend-injected policy at call time (see edit-client.blade.php);
// mirror a representative subset of IClient::DISALLOWED_NATIVE_URI_SCHEMES / NATIVE_LOOPBACK_HOSTS
beforeEach(() => {
    window.DISALLOWED_NATIVE_URI_SCHEMES = ['javascript', 'data', 'intent', 'file', 'itms-services'];
    window.NATIVE_LOOPBACK_HOSTS = ['127.0.0.1', '::1', '[::1]', 'localhost'];
});

describe('isDisallowedNativeUriScheme', () => {
    it('rejects deny-listed schemes, case-insensitively', () => {
        expect(isDisallowedNativeUriScheme('javascript:', '')).toBe(true);
        expect(isDisallowedNativeUriScheme('JAVASCRIPT:', '')).toBe(true);
        expect(isDisallowedNativeUriScheme('intent:', 'scan')).toBe(true);
    });

    it('allows genuine custom app schemes', () => {
        expect(isDisallowedNativeUriScheme('myapp:', 'callback')).toBe(false);
        expect(isDisallowedNativeUriScheme('com.example.app:', '')).toBe(false);
    });

    it('applies the RFC 8252 loopback carve-out to http', () => {
        expect(isDisallowedNativeUriScheme('http:', '127.0.0.1')).toBe(false);
        expect(isDisallowedNativeUriScheme('http:', 'localhost')).toBe(false);
        expect(isDisallowedNativeUriScheme('http:', 'evil.example.com')).toBe(true);
        expect(isDisallowedNativeUriScheme('http:', '')).toBe(true);
    });
});

describe('isValidNativeUri', () => {
    it('accepts https, custom schemes, the RFC 8252 authority-less form and http loopback', () => {
        expect(isValidNativeUri('https://web.example.com/cb')).toBe(true);
        expect(isValidNativeUri('myapp://callback')).toBe(true);
        expect(isValidNativeUri('com.example.app:/oauth2redirect')).toBe(true);
        expect(isValidNativeUri('http://127.0.0.1:8080/cb')).toBe(true);
    });

    it('rejects deny-listed schemes, non-loopback http and unparseable values', () => {
        expect(isValidNativeUri('javascript://x%0aalert(1)')).toBe(false);
        expect(isValidNativeUri('itms-services://x/?action=download-manifest')).toBe(false);
        expect(isValidNativeUri('http://evil.example.com/cb')).toBe(false);
        expect(isValidNativeUri('not a uri')).toBe(false);
    });

    it('rejects opaque URIs (no authority and no rooted path) that the runtime can never match', () => {
        // the one-character typo of the RFC 8252 SS7.1 form - backend write-time validation rejects
        // it with a 412 (assertNativeCustomSchemesAllowed), the inline validator must agree
        expect(isValidNativeUri('com.example.app:oauth2redirect')).toBe(false);
    });
});
