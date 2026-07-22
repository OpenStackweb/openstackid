<?php namespace Strategies;
/**
 * Interface ILoginStrategy
 * @package Strategies
 */
interface ILoginStrategy
{
    /**
     * error_code returned by challengeRequired() when factor 1 passed but a
     * 2FA challenge is pending.
     */
    const MFA_REQUIRED = 'mfa_required';

    /**
     * @return mixed
     */
    public function  getLogin();

    /**
     * @param array $params
     * @return mixed
     */
    public function  postLogin(array $params = []);

    /**
     * @return mixed
     */
    public function  cancelLogin();

    /**
     * @param array $params
     * @return mixed
     */
    public function errorLogin(array $params);

    /**
     * Factor 1 (password) passed but a 2FA challenge must be completed before
     * a session is established. Distinct from errorLogin(): this is a pending
     * mid-flow state, not a failed attempt.
     *
     * @param array $params
     * @return mixed
     */
    public function challengeRequired(array $params);
}