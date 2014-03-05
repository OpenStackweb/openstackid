<?php

namespace auth;

use Illuminate\Auth\UserInterface;
use Member;
use MemberPhoto;
use openid\model\IOpenIdUser;
use oauth2\models\IOAuth2User;
use Eloquent;
use utils\model\BaseModelEloquent;
/**
 * Class User
 * @package auth
 */
class User extends BaseModelEloquent implements UserInterface, IOpenIdUser, IOAuth2User
{
    protected $table = 'openid_users';

    private $member;

    public function trusted_sites()
    {
        return $this->hasMany("OpenIdTrustedSite", 'user_id');
    }

    public function access_tokens()
    {
        return $this->hasMany('AccessToken','user_id');
    }

    public function refresh_tokens()
    {
        return $this->hasMany('RefreshToken','user_id');
    }

    public function consents()
    {
        return $this->hasMany('UserConsent','user_id');
    }

    public function clients()
    {
        return $this->hasMany("Client", 'user_id');
    }

    public function getActions()
    {
        return $this->actions()->orderBy('created_at', 'desc')->take(10)->get();
    }

    public function actions()
    {
        return $this->hasMany("UserAction", 'user_id');
    }

    public function setMember($member)
    {
        $this->member = $member;
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return $this->external_id;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return $this->member->Password;
    }

    public function getIdentifier()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return $this->identifier;
    }

    public function getEmail()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return $this->external_id;
    }

    public function getFullName()
    {
        return $this->getFirstName() . " " . $this->getLastName();
    }

    public function getFirstName()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return $this->member->FirstName;
    }

    public function getLastName()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return $this->member->Surname;
    }

    public function getNickName()
    {
        return $this->getFullName();
    }

    public function getGender()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return $this->member->Gender;
    }

    public function getCountry()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return $this->member->Country;
    }

    public function getLanguage()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return $this->member->Locale;
    }

    public function getTimeZone()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return "";
    }

    public function getDateOfBirth()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return "";
    }

    public function getId()
    {
        return $this->id;
    }

    public function getShowProfileFullName()
    {
        return $this->public_profile_show_fullname;
    }

    public function getShowProfilePic()
    {
        return $this->public_profile_show_photo;
    }

    public function getShowProfileBio()
    {
        return false;
    }

    public function getShowProfileEmail()
    {
        return $this->public_profile_show_email;
    }

    public function getBio()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return $this->member->Bio;
    }

    public function getPic()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        $url     = asset('img/generic-profile-photo.png');

        $photoId = $this->member->PhotoID;

        if (!is_null($photoId) && is_numeric($photoId) && $photoId > 0) {
            $photo                            = MemberPhoto::where('ID', '=', $photoId)->first();
            if(!is_null($photo)){
                $url                          = $photo->Filename;
            }
        }
        return $url;
    }

    public function getClients()
    {
        return $this->clients()->get();
    }
    /**
     * Could use system scopes on registered clients
     * @return bool
     */
    public function canUseSystemScopes()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        $group = $this->member->groups()->where('code','=',IOAuth2User::OAuth2SystemScopeAdminGroup)->first();
        return !is_null($group);
    }

    /**
     * Is Server Administrator
     * @return bool
     */
    public function isOAuth2ServerAdmin()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        $group = $this->member->groups()->where('code','=',IOAuth2User::OAuth2ServerAdminGroup)->first();
        return !is_null($group);
    }

    /**
     * @return bool
     */
    public function isOpenstackIdAdmin()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        $group = $this->member->groups()->where('code','=',IOpenIdUser::OpenstackIdServerAdminGroup)->first();
        return !is_null($group);
    }

    public function getStreetAddress()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return sprintf("%s, %s ",$this->member->Address,$this->member->Suburb);
    }

    public function getRegion()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return $this->member->State;
    }

    public function getLocality()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return $this->member->City;
    }

    public function getPostalCode()
    {
        if (is_null($this->member)) {
            $this->member = Member::where('Email', '=', $this->external_id)->first();
        }
        return $this->member->Postcode;
    }

	public function getTrustedSites()
	{
		return $this->trusted_sites()->get();
	}
}