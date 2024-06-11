<?php namespace Auth;
/**
 * Copyright 2016 OpenStack Foundation
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
use Laminas\Crypt\Hash;
use Laminas\Math\Rand;
use Exception;
use Illuminate\Support\Facades\Hash as HashFacade;
/**
 * Class PasswordEncryptorStrategy
 * @package Auth
 */
abstract class PasswordEncryptorStrategy {
    /**
     * @param      $password
     * @param null $salt
     * @return string
     */
    abstract public function encrypt($password, $salt = null);

    /**
     * @param $hash1
     * @param $hash2
     * @return bool
     */
    public function compare($hash1, $hash2)
    {
        return hash_equals($hash1 , $hash2);
    }

    /**
     * @param string $raw
     * @param string $hash
     * @param string $salt
     * @return bool
     */
    abstract public function check(string $raw, string $hash, string $salt = null):bool;

}

/**
 * Class PasswordEncryptor_Legacy
 * @package Auth
 */
final class PasswordEncryptor_Legacy extends PasswordEncryptorStrategy {

    private $algorithm;

    private static $algorithms = [
        "none"       => "none",
        "md5"        => "md5",
        "sha1"       => "sha1",
        "md5_v2.4"   => "md5",
        "sha1_v2.4"  => "sha1",
    ];

    public function __construct($algorithm){
        $this->algorithm = $algorithm;
    }

    public function encrypt($password, $salt = null)
    {
        if ($this->algorithm != 'none')
            return Hash::compute(self::$algorithms[$this->algorithm], $password . $salt);
        return $password;
    }

    /**
     * @param $hash1
     * @param $hash2
     * @return bool
     */
    public function compare($hash1, $hash2)
    {
        // Due to flawed base_convert() floating poing precision,
        // only the first 10 characters are consistently useful for comparisons.
        return hash_equals(substr($hash1, 0, 10) ,substr($hash2, 0, 10));
    }

    /**
     * @param string $raw
     * @param string $hash
     * @param string $salt
     * @return bool
     */
    public function check(string $raw, string $hash, string $salt = null): bool
    {
        $output = $this->encrypt($raw, $salt);
        return $this->compare($output, $hash);
    }
}

/**
 * Class PasswordEncryptor_Blowfish
 * @package Auth
 */
final class PasswordEncryptor_Blowfish extends PasswordEncryptorStrategy {
    /**
     * Cost of encryption.
     * Higher costs will increase security, but also increase server load.
     * If you are using basic auth, you may need to decrease this as encryption
     * will be run on every request.
     * The two digit cost parameter is the base-2 logarithm of the iteration
     * count for the underlying Blowfish-based hashing algorithmeter and must
     * be in range 04-31, values outside this range will cause crypt() to fail.
     */
    protected static $cost = 10;

    /**
     * Sets the cost of the blowfish algorithm.
     * See {@link PasswordEncryptor_Blowfish::$cost}
     * Cost is set as an integer but
     * Ensure that set values are from 4-31
     *
     * @param int $cost range 4-31
     * @return null
     */
    public static function set_cost($cost) {
        self::$cost = max(min(31, $cost), 4);
    }

    /**
     * @return int
     */
    public static function get_cost() {
        return self::$cost;
    }

    public function encrypt($password, $salt = null) {
        // See: http://nz.php.net/security/crypt_blowfish.php
        // There are three version of the algorithm - y, a and x, in order
        // of decreasing security. Attempt to use the strongest version.
        $encryptedPassword = $this->encryptY($password, $salt);
        if(!$encryptedPassword) {
            $encryptedPassword = $this->encryptA($password, $salt);
        }
        if(!$encryptedPassword) {
            $encryptedPassword = $this->encryptX($password, $salt);
        }

        // We *never* want to generate blank passwords. If something
        // goes wrong, throw an exception.
        if(strpos($encryptedPassword, '$2') === false) {
            throw new \Exception('Blowfish password encryption failed.');
        }

        return $encryptedPassword;
    }

    public function encryptX($password, $salt) {
        $methodAndSalt = '$2x$' . $salt;
        $encryptedPassword = crypt($password, $methodAndSalt);

        if(strpos($encryptedPassword, '$2x$') === 0) {
            return $encryptedPassword;
        }

        // Check if system a is actually x, and if available, use that.
        if($this->checkAEncryptionLevel() == 'x') {
            $methodAndSalt = '$2a$' . $salt;
            $encryptedPassword = crypt($password, $methodAndSalt);

            if(strpos($encryptedPassword, '$2a$') === 0) {
                $encryptedPassword = '$2x$' . substr($encryptedPassword, strlen('$2a$'));
                return $encryptedPassword;
            }
        }

        return false;
    }

    public function encryptY($password, $salt) {
        $methodAndSalt = '$2y$' . $salt;
        $encryptedPassword = crypt($password, $methodAndSalt);

        if(strpos($encryptedPassword, '$2y$') === 0) {
            return $encryptedPassword;
        }

        // Check if system a is actually y, and if available, use that.
        if($this->checkAEncryptionLevel() == 'y') {
            $methodAndSalt = '$2a$' . $salt;
            $encryptedPassword = crypt($password, $methodAndSalt);

            if(strpos($encryptedPassword, '$2a$') === 0) {
                $encryptedPassword = '$2y$' . substr($encryptedPassword, strlen('$2a$'));
                return $encryptedPassword;
            }
        }

        return false;
    }

    public function encryptA($password, $salt) {
        if($this->checkAEncryptionLevel() == 'a') {
            $methodAndSalt = '$2a$' . $salt;
            $encryptedPassword = crypt($password, $methodAndSalt);

            if(strpos($encryptedPassword, '$2a$') === 0) {
                return $encryptedPassword;
            }
        }

        return false;
    }

    /**
     * The algorithm returned by using '$2a$' is not consistent -
     * it might be either the correct (y), incorrect (x) or mostly-correct (a)
     * version, depending on the version of PHP and the operating system,
     * so we need to test it.
     */
    public function checkAEncryptionLevel() {
        // Test hashes taken from
        // http://cvsweb.openwall.com/cgi/cvsweb.cgi/~checkout~/Owl/packages/glibc
        //    /crypt_blowfish/wrapper.c?rev=1.9.2.1;content-type=text%2Fplain
        $xOrY = crypt("\xff\xa334\xff\xff\xff\xa3345", '$2a$05$/OK.fbVrR/bpIqNJ5ianF.o./n25XVfn6oAPaUvHe.Csk4zRfsYPi')
            == '$2a$05$/OK.fbVrR/bpIqNJ5ianF.o./n25XVfn6oAPaUvHe.Csk4zRfsYPi';
        $yOrA = crypt("\xa3", '$2a$05$/OK.fbVrR/bpIqNJ5ianF.Sa7shbm4.OzKpvFnX1pQLmQW96oUlCq')
            == '$2a$05$/OK.fbVrR/bpIqNJ5ianF.Sa7shbm4.OzKpvFnX1pQLmQW96oUlCq';

        if($xOrY && $yOrA) {
            return 'y';
        } elseif($xOrY) {
            return 'x';
        } elseif($yOrA) {
            return 'a';
        }

        return 'unknown';
    }

    /**
     * self::$cost param is forced to be two digits with leading zeroes for ints 4-9
     */
    public static function salt() {
        $generator =  Hash::compute('sha1',Rand::getString(128, null, true));
        return sprintf('%02d', self::$cost) . '$' . substr($generator, 0, 22);
    }

    /**
     * @param string $raw
     * @param string $hash
     * @param string|null $salt
     * @return bool
     */
    public function check(string $raw, string $hash, string $salt = null):bool {
        if(strpos($hash, '$2y$') === 0) {
            return $this->compare($hash , $this->encryptY($raw, $salt));
        }
        if(strpos($hash, '$2a$') === 0) {
            return $this->compare($hash , $this->encryptA($raw, $salt));
        }
        if(strpos($hash, '$2x$') === 0) {
            return $this->compare($hash , $this->encryptX($raw, $salt));
        }
        return false;
    }


}

/**
 * Class PasswordEncryptor_Native
 * @package Auth
 */
final class PasswordEncryptor_Native extends PasswordEncryptorStrategy {

    /**
     * @param      $password
     * @param null $salt
     * @return string
     */
    public function encrypt($password, $salt = null)
    {
        return HashFacade::make($password.$salt);
    }

    /**
     * @param $hash1
     * @param $hash2
     * @return bool
     */
    public function compare($hash1, $hash2)
    {
        return HashFacade::check($hash1, $hash2);
    }

    /**
     * @param string $raw
     * @param string $hash
     * @param string $salt
     * @return bool
     */
    public function check(string $raw, string $hash, string $salt = null): bool
    {
        return $this->compare($raw.$salt, $hash);
    }
}

/**
 * Class AuthHelper
 * @package Auth
 */
final class AuthHelper
{

    const AlgNone      = 'none';
    const AlgMD5       = 'md5';
    const AlgSHA1      = 'sha1';
    const AlgBlowFish  = 'blowfish';
    const AlgSHA1_V2_4 = "sha1_v2.4";
    const AlgNative    = 'native';
    const AlgMD5_v_2_4 = 'md5_v2.4';

    private static $algorithms = [
        self::AlgNone      => "auth\\PasswordEncryptor_Legacy",
        self::AlgMD5       => "auth\\PasswordEncryptor_Legacy",
        self::AlgSHA1      => "auth\\PasswordEncryptor_Legacy",
        self::AlgMD5_v_2_4 => "auth\\PasswordEncryptor_Legacy",
        self::AlgSHA1_V2_4 => "auth\\PasswordEncryptor_Legacy",
        self::AlgBlowFish  => "auth\\PasswordEncryptor_Blowfish",
        self::AlgNative    => "auth\\PasswordEncryptor_Native",
    ];

    /**
     * @param $password
     * @param $salt
     * @param string $algorithm
     * @return string
     * @throws Exception
     */
    public static function encrypt_password($password, $salt, $algorithm = self::AlgSHA1)
    {
        if (!isset(self::$algorithms[$algorithm]))
            throw new Exception(sprintf("non supported algorithm %s", $algorithm));

        $class    = self::$algorithms[$algorithm];
        $strategy = new $class($algorithm);

        return $strategy->encrypt($password, $salt);
    }

    /**
     * @param string $raw
     * @param string $hash
     * @param string $algorithm
     * @param string $salt
     * @return mixed
     * @throws Exception
     */
    public static function check(string $raw, string $hash, $algorithm = self::AlgSHA1, $salt = "")
    {
        if (!isset(self::$algorithms[$algorithm]))
            throw new Exception(sprintf("non supported algorithm %s", $algorithm));
        $class    = self::$algorithms[$algorithm];
        $strategy = new $class($algorithm);
        return $strategy->check($raw, $hash, $salt);
    }

    /**
     * @param int $n
     * @param string $alg
     * @return string
     */
    public static function generateSalt(int $n = 255, string $alg = self::AlgNative):string {
        if(!empty($alg) && $alg == self::AlgBlowFish){
            return PasswordEncryptor_Blowfish::salt();
        }
        return str_random($n);
    }
}