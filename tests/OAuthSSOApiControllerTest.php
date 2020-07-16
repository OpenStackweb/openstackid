<?php
/**
 * Copyright 2020 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/
use LaravelDoctrine\ORM\Facades\EntityManager;
use LaravelDoctrine\ORM\Facades\Registry;
use Doctrine\Common\Persistence\ObjectRepository;
use Illuminate\Support\Facades\DB;
use App\Models\SSO\DisqusSSOProfile;
use App\Models\Utils\BaseEntity;
use App\Models\SSO\RocketChatSSOProfile;
use App\Models\SSO\StreamChat\StreamChatSSOProfile;
/**
 * Class OAuthSSOApiControllerTest
 */
final class OAuthSSOApiControllerTest extends OAuth2ProtectedApiTest
{
    /**
     * @var EntityManager
     */
    static $em;

    /**
     * @var ObjectRepository
     */
    static $disqus_repository;

    /**
     * @var ObjectRepository
     */
    static $rocket_chat_repository;

    /**
     * @var ObjectRepository
     */
    static $stream_chat_repository;

    /**
     * @var DisqusSSOProfile
     */
    static $disqus_profile;

    /**
     * @var RocketChatSSOProfile
     */
    static $rocket_chat_profile;

    /**
     * @var StreamChatSSOProfile
     */
    static $stream_chat_profile;


    public function setUp()
    {
        parent::setUp();
        DB::table("sso_disqus_profile")->delete();
        DB::table("sso_rocket_chat_profile")->delete();
        DB::table("sso_stream_chat_profile")->delete();

        self::$disqus_repository = EntityManager::getRepository(DisqusSSOProfile::class);
        self::$rocket_chat_repository = EntityManager::getRepository(RocketChatSSOProfile::class);
        self::$stream_chat_repository = EntityManager::getRepository(StreamChatSSOProfile::class);

        self::$disqus_profile = new DisqusSSOProfile();
        self::$disqus_profile->setForumSlug("poc_disqus");
        self::$disqus_profile->setPublicKey("PUBLIC_KEY");
        self::$disqus_profile->setSecretKey("SECRET_KEY");

        self::$rocket_chat_profile = new RocketChatSSOProfile();
        self::$rocket_chat_profile->setForumSlug("poc_rocket_chat");
        self::$rocket_chat_profile->setBaseUrl("https://rocket-chat.dev.fnopen.com");
        self::$rocket_chat_profile->setServiceName("fnid");

        self::$stream_chat_profile = new StreamChatSSOProfile();
        self::$stream_chat_profile->setForumSlug("poc_stream_chat");
        self::$stream_chat_profile->setApiKey(env("STREAM_CHAT_API_KEY") ?? '');
        self::$stream_chat_profile->setApiSecret(env("STREAM_CHAT_API_SECRET") ?? '');

        self::$em = Registry::getManager(BaseEntity::EntityManager);
        if (!self::$em ->isOpen()) {
            self::$em  = Registry::resetManager(BaseEntity::EntityManager);
        }

        self::$em->persist(self::$disqus_profile);
        self::$em->persist(self::$rocket_chat_profile);
        self::$em->persist(self::$stream_chat_profile);
        self::$em->flush();
    }

    protected function tearDown()
    {
        self::$em  = Registry::resetManager(BaseEntity::EntityManager);
        self::$disqus_profile = self::$disqus_repository->find(self::$disqus_profile->getId());
        self::$em->remove(self::$disqus_profile);
        self::$em->flush();
        parent::tearDown();
    }

    public function testDisqusGetUserProfile(){

        $params = [
            "forum_slug" => self::$disqus_profile->getForumSlug()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action
        (
            "GET",
            "Api\\OAuth2\\OAuth2DisqusSSOApiController@getUserProfile",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(200);
        $content = $response->getContent();
        $profile = json_decode($content);

        $this->assertTrue(!empty($profile->auth));
        $this->assertTrue(!empty($profile->public_key));
        $this->assertTrue($profile->public_key == self::$disqus_profile->getPublicKey());
    }

    public function testRocketGetUserProfileFails(){

        $params = [
            "forum_slug" => self::$rocket_chat_profile->getForumSlug()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action
        (
            "GET",
            "Api\\OAuth2\\OAuth2RocketChatSSOApiController@getUserProfile",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(412);
        $content = $response->getContent();
    }

    /**
    public function testStreamChatGetUserProfileOK(){

        $params = [
            "forum_slug" => self::$stream_chat_profile->getForumSlug()
        ];

        $headers = [
            "HTTP_Authorization" => " Bearer " . $this->access_token,
            "CONTENT_TYPE"        => "application/json"
        ];

        $response = $this->action
        (
            "GET",
            "Api\\OAuth2\\OAuth2StreamChatSSOApiController@getUserProfile",
            $params,
            [],
            [],
            [],
            $headers
        );

        $this->assertResponseStatus(200);
        $content = $response->getContent();
        $profile = json_decode($content, true);
        $this->assertTrue(isset($profile['id']));
        $this->assertTrue(isset($profile['token']));
        $this->assertTrue(isset($profile['api_key']));
    }
    **/

    protected function getScopes()
    {
        $scope = array(
            \App\libs\OAuth2\IUserScopes::SSO
        );

        return $scope;
    }
}