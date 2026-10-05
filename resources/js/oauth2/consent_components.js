import React from "react";

// only plain web URLs are safe to expose as a navigable link (blocks javascript:, custom schemes, etc.)
export const safeWebsiteUrl = (website) => {
    if (typeof website !== 'string' || website.trim() === '') return null;
    try {
        const url = new URL(website.trim());
        return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : null;
    } catch (e) {
        return null;
    }
};

// NOTE: never link to the client's redirect_uri; for native clients (custom URI scheme)
// navigating to it without an authorization code kills the login.
export const AppName = ({appName, website}) => {
    const href = safeWebsiteUrl(website);
    if (!href) return <>{appName}</>;
    return <a target='_blank' rel='noopener noreferrer' href={href}>{appName}</a>;
};

export const RedirectNotice = ({redirectURL}) => (
    <div>Clicking 'Accept' will redirect you to: <span style={{wordBreak: 'break-all'}}>{redirectURL}</span>.</div>
);
