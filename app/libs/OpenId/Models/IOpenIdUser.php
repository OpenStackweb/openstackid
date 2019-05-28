<?php namespace OpenId\Models;
/**
 * Interface IOpenIdUser
 * @package OpenId\Models
 */
interface IOpenIdUser
{
    const OpenIdServerAdminGroup = 'openid-server-admins';

    /**
     * @return bool
     */
    public function isOpenIdServerAdmin();

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getIdentifier():?string;

    /**
     * @return string
     */
    public function getEmail();

    /**
     * @return string
     */
    public function getFirstName();

    /**
     * @return string
     */
    public function getLastName();

    /**
     * @return string
     */
    public function getFullName();

    /**
     * @return string
     */
    public function getNickName();

    /**
     * @return string
     */
    public function getGender();

    /**
     * @return string
     */
    public function getCountry();

    /**
     * @return string
     */
    public function getStreetAddress();

    /**
     * @return string
     */
    public function getRegion();

    /**
     * @return string
     */
    public function getLocality();

    /**
     * @return string
     */
    public function getPostalCode();

    /**
     * @return string
     */
    public function getFormattedAddress();

    public function getLanguage();

    public function getDateOfBirth();

    /**
     * @return bool
     */
    public function getShowProfileFullName();

    /**
     * @return bool
     */
    public function getShowProfilePic();

    /**
     * @return bool
     */
    public function getShowProfileBio();

    /**
     * @return bool
     */
    public function getShowProfileEmail();

    public function getBio();

    public function getPic();

    /**
     * @param int $n
     * @return mixed
     */
    public function getLatestNActions(int $n = 10);

    public function getTrustedSites();

    /**
     * @return int
     */
    public function getExternalIdentifier();

    /**
     * @return bool
     */
    public function isEmailVerified();
}