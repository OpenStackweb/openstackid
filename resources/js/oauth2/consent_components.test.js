import React from "react";
import {renderToStaticMarkup} from "react-dom/server";
import {AppName, RedirectNotice, safeWebsiteUrl} from "./consent_components";

const NATIVE_REDIRECT = 'com.fntech.ftnattendee://callback';

describe('safeWebsiteUrl', () => {
    it('accepts http and https', () => {
        expect(safeWebsiteUrl('https://example.com/app')).toBe('https://example.com/app');
        expect(safeWebsiteUrl('http://example.com/')).toBe('http://example.com/');
    });

    it('rejects empty, non-string, malformed and non-web schemes', () => {
        expect(safeWebsiteUrl(null)).toBeNull();
        expect(safeWebsiteUrl(undefined)).toBeNull();
        expect(safeWebsiteUrl('   ')).toBeNull();
        expect(safeWebsiteUrl('not a url')).toBeNull();
        expect(safeWebsiteUrl('javascript:alert(1)')).toBeNull();
        expect(safeWebsiteUrl(NATIVE_REDIRECT)).toBeNull();
    });
});

describe('AppName', () => {
    it('links to the website in a new tab when configured', () => {
        const html = renderToStaticMarkup(<AppName appName="My App" website="https://example.com/"/>);
        expect(html).toContain('href="https://example.com/"');
        expect(html).toContain('target="_blank"');
        expect(html).toContain('rel="noopener noreferrer"');
        expect(html).toContain('My App');
    });

    it('is plain text without a website', () => {
        const html = renderToStaticMarkup(<AppName appName="My App" website=""/>);
        expect(html).toBe('My App');
        expect(html).not.toContain('<a');
    });

    it('never emits an anchor for a native-scheme website', () => {
        const html = renderToStaticMarkup(<AppName appName="My App" website={NATIVE_REDIRECT}/>);
        expect(html).not.toContain('<a');
    });
});

describe('RedirectNotice', () => {
    it('shows a native redirect_uri as text, never as a link', () => {
        const html = renderToStaticMarkup(<RedirectNotice redirectURL={NATIVE_REDIRECT}/>);
        expect(html).toContain(NATIVE_REDIRECT);
        expect(html).not.toContain('<a');
        expect(html).not.toContain('href=');
    });
});
