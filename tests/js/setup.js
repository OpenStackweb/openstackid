import '@testing-library/jest-dom';
import {TextEncoder, TextDecoder} from 'util';

// jsdom does not provide these globals; superagent (via base_actions.js) needs
// them transitively (through @paralleldrive/cuid2), even when the module ends
// up auto-mocked by jest.mock() — Jest still evaluates the real module once.
if (typeof global.TextEncoder === 'undefined') {
    global.TextEncoder = TextEncoder;
}
if (typeof global.TextDecoder === 'undefined') {
    global.TextDecoder = TextDecoder;
}
