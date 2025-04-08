<?php namespace OAuth2;
/**
 * Interface IResourceServerContext
 * Current Request OAUTH2 security context
 * @package OAuth2
 */
interface IResourceServerContext {

    const UserId = 'user_id';
    const UserFirstName = 'user_first_name';
    const UserLastName = 'user_last_name';
    const UserEmail = 'user_email';
    const UserEmailVerified = 'user_email_verified';

    const ApplicationType_Service = 'SERVICE';

    /**
     * returns given scopes for current request
     * @return array
     */
    public function getCurrentScope();

    /**
     * gets current access token values
     * @return string
     */
    public function getCurrentAccessToken();

    /**
     * gets current access token lifetime
     * @return mixed
     */
    public function getCurrentAccessTokenLifetime();

    /**
     * gets current client id
     * @return string
     */
    public function getCurrentClientId();

    /**
     * gets current user id (if was set)
     * @return int
     */
    public function getCurrentUserId();

    /**
     * @param array$auth_context
     * @return $this
     */
    public function setAuthorizationContext(array $auth_context);

    /**
     * @return string
     */
    public function getApplicationType():string;


    /**
     * @return string|null
     */
    public function getCurrentUserEmail():?string;
} 