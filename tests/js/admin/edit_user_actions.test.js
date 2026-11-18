import {normalizeEntity, save} from '../../../resources/js/admin/edit_user/actions';
import {putFile, putRawRequest} from '../../../resources/js/base_actions';

jest.mock('../../../resources/js/base_actions', () => ({
    getRawRequest: jest.fn(),
    putRawRequest: jest.fn(),
    putFile: jest.fn(),
}));

const entity = (overrides = {}) => ({
    public_profile_show_photo: true,
    public_profile_show_fullname: true,
    public_profile_show_email: false,
    public_profile_allow_chat_with_me: false,
    active: true,
    email_verified: true,
    post_code: 12345,
    groups: ['1', '2'],
    two_factor_enabled: false,
    two_factor_enforced: false,
    two_factor_enforced_at: null,
    two_factor_method: null,
    ...overrides,
});

describe('admin edit user normalizeEntity', () => {
    it('omits two_factor_enabled when the admin did not change the box', () => {
        expect(normalizeEntity(entity({two_factor_enabled: false}), false)).not.toHaveProperty('two_factor_enabled');
        expect(normalizeEntity(entity({two_factor_enabled: true}), true)).not.toHaveProperty('two_factor_enabled');
    });

    it('sends the new value as 1/0 when the admin changed the box', () => {
        expect(normalizeEntity(entity({two_factor_enabled: true}), false).two_factor_enabled).toBe(1);
        expect(normalizeEntity(entity({two_factor_enabled: false}), true).two_factor_enabled).toBe(0);
    });

    it('never sends the read only 2FA state', () => {
        const out = normalizeEntity(entity({two_factor_enforced: true, two_factor_enforced_at: 1, two_factor_method: 'otp'}), false);
        expect(out).not.toHaveProperty('two_factor_enforced');
        expect(out).not.toHaveProperty('two_factor_enforced_at');
        expect(out).not.toHaveProperty('two_factor_method');
    });
});


describe('admin edit user save', () => {
    const savedUser = {id: 5, two_factor_enabled: false, two_factor_enforced: true, two_factor_enforced_at: 1700000000};
    let sendPut;
    let sendFile;

    beforeEach(() => {
        window.SAVE_PROFILE_ENDPOINT = '/api/v1/users/5';
        window.SAVE_PIC_ENDPOINT = '/api/v1/users/5/pic';
        sendPut = jest.fn().mockResolvedValue({response: savedUser});
        sendFile = jest.fn().mockResolvedValue({response: {id: 5}});
        putRawRequest.mockReturnValue(sendPut);
        putFile.mockReturnValue(sendFile);
    });

    it('returns the saved user so the form can pick up the server 2FA state', async () => {
        await expect(save(entity(), null, 'token', false)).resolves.toEqual(savedUser);
        expect(sendFile).not.toHaveBeenCalled();
    });

    it('still returns the saved user of the profile request after uploading the picture', async () => {
        await expect(save(entity(), {name: 'pic.png'}, 'token', false)).resolves.toEqual(savedUser);
        expect(sendFile).toHaveBeenCalledTimes(1);
    });
});
