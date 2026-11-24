import React from 'react';
import {render, screen, fireEvent, waitFor} from '@testing-library/react';
import Swal from 'sweetalert2';
import TrustedDevicesSection from '../../../resources/js/components/trusted_devices_section';
import {
    getTrustedDevices,
    revokeAllTrustedDevices,
    revokeTrustedDevice
} from '../../../resources/js/profile/actions';

jest.mock('../../../resources/js/profile/actions');
jest.mock('sweetalert2', () => jest.fn());

const device = (id, overrides = {}) => ({
    id,
    device_name: `Mozilla/5.0 device ${id}`,
    ip_address: '10.0.0.' + id,
    trusted_at: 1790000000,
    last_seen_at: 1790100000,
    expires_at: 1792592000,
    is_current: false,
    ...overrides,
});

describe('TrustedDevicesSection', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('renders the trusted devices table and marks the current device', async () => {
        getTrustedDevices.mockResolvedValue({response: {data: [device(1, {is_current: true}), device(2)]}});

        render(<TrustedDevicesSection/>);

        expect(await screen.findByTestId('trusted-devices-table')).toBeInTheDocument();
        expect(screen.getByText('Mozilla/5.0 device 1')).toBeInTheDocument();
        expect(screen.getByText('10.0.0.2')).toBeInTheDocument();
        expect(screen.getAllByTestId('trusted-device-current')).toHaveLength(1);
        expect(screen.getByTestId('trusted-device-row-1')).toContainElement(screen.getByTestId('trusted-device-current'));
        expect(screen.getByTestId('revoke-trusted-device-1')).toBeInTheDocument();
        expect(screen.getByTestId('revoke-all-trusted-devices')).toBeInTheDocument();
    });

    it('shows the empty state when there are no trusted devices', async () => {
        getTrustedDevices.mockResolvedValue({response: {data: []}});

        render(<TrustedDevicesSection/>);

        expect(await screen.findByTestId('trusted-devices-empty')).toBeInTheDocument();
        expect(screen.queryByTestId('trusted-devices-table')).not.toBeInTheDocument();
        expect(screen.queryByTestId('revoke-all-trusted-devices')).not.toBeInTheDocument();
    });

    it('revokes a single device and removes its row without reloading', async () => {
        getTrustedDevices.mockResolvedValue({response: {data: [device(1), device(2)]}});
        revokeTrustedDevice.mockResolvedValue({response: {}});

        render(<TrustedDevicesSection/>);
        fireEvent.click(await screen.findByTestId('revoke-trusted-device-1'));

        expect(revokeTrustedDevice).toHaveBeenCalledWith(1);
        await waitFor(() => expect(screen.queryByTestId('trusted-device-row-1')).not.toBeInTheDocument());
        expect(screen.getByTestId('trusted-device-row-2')).toBeInTheDocument();
        expect(getTrustedDevices).toHaveBeenCalledTimes(1);
    });

    it('keeps the row when the revoke request fails', async () => {
        getTrustedDevices.mockResolvedValue({response: {data: [device(1)]}});
        revokeTrustedDevice.mockRejectedValue({status: 500});

        render(<TrustedDevicesSection/>);
        fireEvent.click(await screen.findByTestId('revoke-trusted-device-1'));

        await waitFor(() => expect(screen.getByTestId('revoke-trusted-device-1')).not.toBeDisabled());
        expect(screen.getByTestId('trusted-device-row-1')).toBeInTheDocument();
        expect(Swal).toHaveBeenCalledWith('Something went wrong!', null, 'error');
    });

    it('revokes all devices after confirmation and shows the empty state', async () => {
        getTrustedDevices.mockResolvedValue({response: {data: [device(1), device(2)]}});
        revokeAllTrustedDevices.mockResolvedValue({response: {}});
        Swal.mockResolvedValue({value: true});

        render(<TrustedDevicesSection/>);
        fireEvent.click(await screen.findByTestId('revoke-all-trusted-devices'));

        expect(Swal).toHaveBeenCalledTimes(1);
        expect(await screen.findByTestId('trusted-devices-empty')).toBeInTheDocument();
        expect(revokeAllTrustedDevices).toHaveBeenCalledTimes(1);
    });

    it('does not revoke anything when the revoke-all confirmation is cancelled', async () => {
        getTrustedDevices.mockResolvedValue({response: {data: [device(1)]}});
        Swal.mockResolvedValue({dismiss: 'cancel'});

        render(<TrustedDevicesSection/>);
        fireEvent.click(await screen.findByTestId('revoke-all-trusted-devices'));

        await waitFor(() => expect(Swal).toHaveBeenCalledTimes(1));
        expect(revokeAllTrustedDevices).not.toHaveBeenCalled();
        expect(screen.getByTestId('trusted-device-row-1')).toBeInTheDocument();
    });

    it('never renders a submit button, since the profile page is a single form', async () => {
        getTrustedDevices.mockResolvedValue({response: {data: [device(1)]}});

        const {container} = render(<TrustedDevicesSection/>);
        await screen.findByTestId('trusted-devices-table');

        expect(container.querySelectorAll('button[type="submit"]')).toHaveLength(0);
    });
});
