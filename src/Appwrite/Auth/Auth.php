<?php

namespace Appwrite\Auth;

use Appwrite\Auth\Hash\Argon2;
use Appwrite\Auth\Hash\Bcrypt;
use Appwrite\Auth\Hash\Md5;
use Appwrite\Auth\Hash\Phpass;
use Appwrite\Auth\Hash\Scrypt;
use Appwrite\Auth\Hash\Scryptmodified;
use Appwrite\Auth\Hash\Sha;
use Utopia\Database\Document;
use Utopia\Database\Validator\Authorization;

class Auth
{
    public const SUPPORTED_ALGOS = [
        'argon2',
        'bcrypt',
        'md5',
        'sha',
        'phpass',
        'scrypt',
        'scryptMod',
        'plaintext'
    ];

    public const DEFAULT_ALGO = 'argon2';
    public const DEFAULT_ALGO_OPTIONS = ['memoryCost' => 2048, 'timeCost' => 4, 'threads' => 3];

    /**
     * User Roles.
     */
    public const USER_ROLE_ALL = 'all';
    public const USER_ROLE_GUEST = 'guest';
    public const USER_ROLE_MEMBER = 'member';
    public const USER_ROLE_ADMIN = 'admin';
    public const USER_ROLE_DEVELOPER = 'developer';
    public const USER_ROLE_OWNER = 'owner';
    public const USER_ROLE_APP = 'app';
    public const USER_ROLE_SYSTEM = 'system';

    /**
     * Token Types.
     */
    public const TOKEN_TYPE_LOGIN = 1; // Deprecated
    public const TOKEN_TYPE_VERIFICATION = 2;
    public const TOKEN_TYPE_RECOVERY = 3;
    public const TOKEN_TYPE_INVITE = 4;
    public const TOKEN_TYPE_MAGIC_URL = 5;

    /**
     * Session Providers.
     */
    public const SESSION_PROVIDER_EMAIL = 'email';
    public const SESSION_PROVIDER_ANONYMOUS = 'anonymous';
    public const SESSION_PROVIDER_MAGIC_URL = 'magic-url';

    /**
     * Token Expiration times.
     */
    public const TOKEN_EXPIRATION_LOGIN_LONG = 31536000;      /* 1 year */
    public const TOKEN_EXPIRATION_LOGIN_SHORT = 3600;         /* 1 hour */
    public const TOKEN_EXPIRATION_RECOVERY = 3600;            /* 1 hour */
    public const TOKEN_EXPIRATION_CONFIRM = 3600 * 24 * 7;    /* 7 days */

    /**
     * @var string
     */
    public static $cookieName = 'a_session';

    /**
     * User Unique ID.
     *
     * @var string
     */
    public static $unique = '';

    /**
     * User Secret Key.
     *
     * @var string
     */
    public static $secret = '';

    /**
     * Set Cookie Name.
     *
     * @param $string
     *
     * @return string
     */
    public static function setCookieName($string)
    {
        return self::$cookieName = $string;
    }

    /**
     * Encode Session.
     *
     * @param string $id
     * @param string $secret
     *
     * @return string
     */
    public static function encodeSession($id, $secret)
    {
        return \base64_encode(\json_encode([
            'id' => $id,
            'secret' => $secret,
        ]));
    }

    /**
     * Decode Session.
     *
     * @param string $session
     *
     * @return array
     *
     * @throws \Exception
     */
    public static function decodeSession($session)
    {
        $session = \json_decode(\base64_decode($session), true);
        $default = ['id' => null, 'secret' => ''];

        if (!\is_array($session)) {
            return $default;
        }

        return \array_merge($default, $session);
    }

    /**
     * Encode.
     *
     * One-way encryption
     *
     * @param $string
     *
     * @return string
     */
    public static function hash(string $string)
    {
        return \hash('sha256', $string);
    }

    /**
     * Password Hash.
     *
     * One way string hashing for user passwords
     *
     * @param string $string
     * @param string $algo hashing algorithm to use
     * @param string $options algo-specific options
     *
     * @return bool|string|null
     */
    public static function passwordHash(string $string, string $algo, mixed $options = [])
    {
        // Plain text not supported, just an alias. Switch to recommended algo
        if ($algo === 'plaintext') {
            $algo = Auth::DEFAULT_ALGO;
            $options = Auth::DEFAULT_ALGO_OPTIONS;
        }

        if (!\in_array($algo, Auth::SUPPORTED_ALGOS)) {
            throw new \Exception('Hashing algorithm \'' . $algo . '\' is not supported.');
        }

        switch ($algo) {
            case 'argon2':
                $hasher = new Argon2($options);
                return $hasher->hash($string);
                break;
            case 'bcrypt':
                $hasher = new Bcrypt($options);
                return $hasher->hash($string);
                break;
            case 'md5':
                $hasher = new Md5($options);
                return $hasher->hash($string);
                break;
            case 'sha':
                $hasher = new Sha($options);
                return $hasher->hash($string);
                break;
            case 'phpass':
                $hasher = new Phpass($options);
                return $hasher->hash($string);
                break;
            case 'scrypt':
                $hasher = new Scrypt($options);
                return $hasher->hash($string);
                break;
            case 'scryptMod':
                $hasher = new Scryptmodified($options);
                return $hasher->hash($string);
                break;
            default:
                throw new \Exception('Hashing algorithm \'' . $algo . '\' is not supported.');
        }
    }

    /**
     * Password verify.
     *
     * @param string $plain
     * @param string $hash
     * @param string $algo hashing algorithm used to hash
     * @param string $options algo-specific options
     *
     * @return bool
     */
    public static function passwordVerify(string $plain, string $hash, string $algo, mixed $options = [])
    {
        // Plain text not supported, just an alias. Switch to recommended algo
        if ($algo === 'plaintext') {
            $algo = Auth::DEFAULT_ALGO;
            $options = Auth::DEFAULT_ALGO_OPTIONS;
        }

        if (!\in_array($algo, Auth::SUPPORTED_ALGOS)) {
            throw new \Exception('Hashing algorithm \'' . $algo . '\' is not supported.');
        }

        switch ($algo) {
            case 'argon2':
                $hasher = new Argon2($options);
                return $hasher->verify($plain, $hash);
                break;
            case 'bcrypt':
                $hasher = new Bcrypt($options);
                return $hasher->verify($plain, $hash);
                break;
            case 'md5':
                $hasher = new Md5($options);
                return $hasher->verify($plain, $hash);
                break;
            case 'sha':
                $hasher = new Sha($options);
                return $hasher->verify($plain, $hash);
                break;
            case 'phpass':
                $hasher = new Phpass($options);
                return $hasher->verify($plain, $hash);
                break;
            case 'scrypt':
                $hasher = new Scrypt($options);
                return $hasher->verify($plain, $hash);
                break;
            case 'scryptMod':
                $hasher = new Scryptmodified($options);
                return $hasher->verify($plain, $hash);
                break;
            default:
                throw new \Exception('Hashing algorithm \'' . $algo . '\' is not supported.');
        }
    }

    /**
     * Password Generator.
     *
     * Generate random password string
     *
     * @param int $length
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function passwordGenerator(int $length = 20): string
    {
        return \bin2hex(\random_bytes($length));
    }

    /**
     * Token Generator.
     *
     * Generate random password string
     *
     * @param int $length
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function tokenGenerator(int $length = 128): string
    {
        return \bin2hex(\random_bytes($length));
    }

    /**
     * Verify token and check that its not expired.
     *
     * @param array  $tokens
     * @param int    $type
     * @param string $secret
     *
     * @return bool|string
     */
    public static function tokenVerify(array $tokens, int $type, string $secret)
    {
        foreach ($tokens as $token) { /** @var Document $token */
            if (
                $token->isSet('type') &&
                $token->isSet('secret') &&
                $token->isSet('expire') &&
                $token->getAttribute('type') == $type &&
                $token->getAttribute('secret') === self::hash($secret) &&
                $token->getAttribute('expire') >= \time()
            ) {
                return (string)$token->getId();
            }
        }

        return false;
    }

    /**
     * Verify session and check that its not expired.
     *
     * @param array  $sessions
     * @param string $secret
     *
     * @return bool|string
     */
    public static function sessionVerify(array $sessions, string $secret)
    {
        foreach ($sessions as $session) { /** @var Document $session */
            if (
                $session->isSet('secret') &&
                $session->isSet('expire') &&
                $session->isSet('provider') &&
                $session->getAttribute('secret') === self::hash($secret) &&
                $session->getAttribute('expire') >= \time()
            ) {
                return (string)$session->getId();
            }
        }

        return false;
    }

    /**
     * Is Privileged User?
     *
     * @param array $roles
     *
     * @return bool
     */
    public static function isPrivilegedUser(array $roles): bool
    {
        if (
            in_array('role:' . self::USER_ROLE_OWNER, $roles) ||
            in_array('role:' . self::USER_ROLE_DEVELOPER, $roles) ||
            in_array('role:' . self::USER_ROLE_ADMIN, $roles)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Is App User?
     *
     * @param array $roles
     *
     * @return bool
     */
    public static function isAppUser(array $roles): bool
    {
        if (in_array('role:' . self::USER_ROLE_APP, $roles)) {
            return true;
        }

        return false;
    }

    /**
     * Returns all roles for a user.
     *
     * @param Document $user
     * @return array
     */
    public static function getRoles(Document $user): array
    {
        $roles = [];

        if (!self::isPrivilegedUser(Authorization::getRoles()) && !self::isAppUser(Authorization::getRoles())) {
            if ($user->getId()) {
                $roles[] = 'user:' . $user->getId();
                $roles[] = 'role:' . Auth::USER_ROLE_MEMBER;
            } else {
                return ['role:' . Auth::USER_ROLE_GUEST];
            }
        }

        foreach ($user->getAttribute('memberships', []) as $node) {
            if (isset($node['teamId']) && isset($node['roles'])) {
                $roles[] = 'team:' . $node['teamId'];

                foreach ($node['roles'] as $nodeRole) { // Set all team roles
                    $roles[] = 'team:' . $node['teamId'] . '/' . $nodeRole;
                }
            }
        }

        return $roles;
    }
}
