// Unlike the RecoveryCodesPanel/TwoFactorSection tests, this suite does NOT
// mock "profile/actions" - it exercises the real enableTwoFactor()/
// regenerateRecoveryCodes() against the real base_actions.js request layer,
// only stubbing the transport (superagent) itself. This is the layer where a
// broken import (e.g. a named export that doesn't exist) actually blows up -
// a test that mocks profile/actions can never catch that.
jest.mock("superagent", () => ({
    post: jest.fn(),
}));

import request from "superagent";
import {
    enableTwoFactor,
    regenerateRecoveryCodes,
    enrollRecoveryCodes,
    acknowledgeRecoveryCodes,
} from "profile/actions";

function makeChainableRequest({ body = {}, error = null } = {}) {
    const req = {};
    req.set = jest.fn(() => req);
    req.send = jest.fn(() => req);
    req.timeout = jest.fn(() => req);
    req.then = (onFulfilled, onRejected) =>
        (error ? Promise.reject(error) : Promise.resolve({ body })).then(onFulfilled, onRejected);
    req.catch = (onRejected) => req.then(undefined, onRejected);
    return req;
}

describe("profile/actions (unmocked request layer)", () => {
    beforeEach(() => {
        request.post.mockReset();
        window.ENABLE_TWO_FACTOR_ENDPOINT = "https://idp.test/api/v2/users/me/2fa/enable";
        window.REGENERATE_RECOVERY_CODES_ENDPOINT = "https://idp.test/api/v2/users/me/2fa/recovery-codes";
        window.ENROLL_RECOVERY_CODES_ENDPOINT = "https://idp.test/admin/api/v1/users/me/recovery-codes/enroll";
        window.ACKNOWLEDGE_RECOVERY_CODES_ENDPOINT = "https://idp.test/admin/api/v1/users/me/recovery-codes/acknowledge";
        window.CSFR_TOKEN = "test-csrf-token";
    });

    it("enableTwoFactor resolves the recovery codes through postRawRequestFull", async () => {
        const req = makeChainableRequest({ body: { recovery_codes: ["ABCD-1234"] } });
        request.post.mockReturnValue(req);

        const { response } = await enableTwoFactor("email_otp");

        expect(request.post).toHaveBeenCalledWith(window.ENABLE_TWO_FACTOR_ENDPOINT);
        expect(req.send).toHaveBeenCalledWith({ method: "email_otp" });
        expect(req.set).toHaveBeenCalledWith({ "X-CSRF-TOKEN": "test-csrf-token" });
        expect(response.recovery_codes).toEqual(["ABCD-1234"]);
    });

    it("regenerateRecoveryCodes sends current_password only in the request body, never in the URL", async () => {
        const req = makeChainableRequest({ body: { recovery_codes: ["WXYZ-5678"] } });
        request.post.mockReturnValue(req);

        const { response } = await regenerateRecoveryCodes("super-secret-password");

        const calledUrl = request.post.mock.calls[0][0];
        expect(calledUrl).toBe(window.REGENERATE_RECOVERY_CODES_ENDPOINT);
        expect(calledUrl).not.toContain("super-secret-password");
        expect(req.send).toHaveBeenCalledWith({ current_password: "super-secret-password" });
        expect(response.recovery_codes).toEqual(["WXYZ-5678"]);
    });

    it("regenerateRecoveryCodes without a password sends an empty body (passwordless regeneration inside the window)", async () => {
        const req = makeChainableRequest({ body: { recovery_codes: ["WXYZ-5678"], batch_id: 4 } });
        request.post.mockReturnValue(req);

        await regenerateRecoveryCodes();

        expect(req.send).toHaveBeenCalledWith({});
    });

    it("enrollRecoveryCodes posts to the enroll endpoint asking for JSON, so the auth middleware does not redirect an XHR to backurl", async () => {
        const req = makeChainableRequest({ body: { recovery_codes: ["ABCD-1234"], batch_id: 9 } });
        request.post.mockReturnValue(req);

        const { response } = await enrollRecoveryCodes();

        expect(request.post).toHaveBeenCalledWith(window.ENROLL_RECOVERY_CODES_ENDPOINT);
        expect(req.set).toHaveBeenCalledWith({ "X-CSRF-TOKEN": "test-csrf-token", Accept: "application/json" });
        expect(response.batch_id).toBe(9);
    });

    it("acknowledgeRecoveryCodes sends the batch id in the body, never in the URL", async () => {
        const req = makeChainableRequest({ body: { recovery_codes_acknowledged: true } });
        request.post.mockReturnValue(req);

        const { response } = await acknowledgeRecoveryCodes(9);

        expect(request.post).toHaveBeenCalledWith(window.ACKNOWLEDGE_RECOVERY_CODES_ENDPOINT);
        expect(req.send).toHaveBeenCalledWith({ batch_id: 9 });
        expect(req.set).toHaveBeenCalledWith({ "X-CSRF-TOKEN": "test-csrf-token", Accept: "application/json" });
        expect(response.recovery_codes_acknowledged).toBe(true);
    });
});
