<?php

namespace Helper;
use Firebase\JWT\JWT;


class AuthHelper
{
    private static $private = <<<EOD
    -----BEGIN RSA PRIVATE KEY-----
    MIICXgIBAAKBgQC00Jb5MUHikp/4Laq/I+ih5YCcT4/BSDjLegl+X71tS8tCZbcr
    kuhQVlnVZOKCZSjvD5aBgIXY2hziaBbKBJ8QjKGEb+Cc1kyPRR5HIG4l+eZ90lu7
    uqzq+u3UMW48JmEGFqao5SPqxPDyA6mBNx1woEO8CFEJWSF0n6n1uASPswIDAQAB
    AoGBAIOaXog5Bc83ER+9fU1pgWR0ektKzULMoinXRRmp7WGjjOlixxL79gKjFvdu
    Wj62CjkVi2HufXz8I5HWWN/oxSw5DYNChgRLYuic3ufL9bR72kYJQhPh37VjcG8y
    Tt6LPDX+ioqF1ciMoU9d703uG/nUjNMstaGsQIRJdCG9R0GBAkEA3tkcfGaLXyT0
    ZSUKPqM45IqYXWXjic/m3Nt72zMz1yoYbndfizIQvbFtt9lST6AxgA5BXFw/vKKg
    mOKZF33SYwJBAM+2sZWdMgYs7c4X/0a/DVy5P5R5WSr7wvJaoiubREdBvcqHpB0r
    KDizAxDZXz3SQhtizG7l9lFpbBd6bTbAJnECQQC27Lj5VKNrAkarD/CM4ia9Uxcm
    85AHe+Uhvfi5Qhp3sFJFuy9ubzZWv+I0W+u4+OIpH4p/ainXihcR6E+KfPnJAkB7
    kagb5aR44Amo7cXEBKyiWOJmJbrSQ2w6WYjYgEoiSg3qir8rSx1mfbh5MZfjY05I
    lIIiB1R+IkVXwlFunOlBAkEAilvfU5XP51qeYRBnzEZqZ1pRyYsx7fgOnAskbEj7
    zVbPIEhYO5C749QFU2sjulU9h9afm3nZLHyHnJjyzN0yng==
    -----END RSA PRIVATE KEY-----
    EOD;

    #private static $public = "sasasystems210909";
    private static $public = <<<EOD
    -----BEGIN PUBLIC KEY-----
    MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC00Jb5MUHikp/4Laq/I+ih5YCc
    T4/BSDjLegl+X71tS8tCZbcrkuhQVlnVZOKCZSjvD5aBgIXY2hziaBbKBJ8QjKGE
    b+Cc1kyPRR5HIG4l+eZ90lu7uqzq+u3UMW48JmEGFqao5SPqxPDyA6mBNx1woEO8
    CFEJWSF0n6n1uASPswIDAQAB
    -----END PUBLIC KEY-----
    EOD;

    private static $mode = "RS256";

    private static $aud = null;

    static function init($credentials, $duration=60)
    {
        $start = time();
        $token = array(
            "aud" => self::Aud(),
            "iat" => time(), 
            "exp" => $start + ($duration*1000),
            "data" => $credentials,
        );

        $encode = JWT::encode($token, self::$private, self::$mode);

        return $encode;
    }

    public static function Check($token)
    {
        if(empty($token))
        {
            throw new \Exception("Invalid token supplied.");
        }

        $removeBearerWord = str_replace("Bearer ", "", $token);

        $decode = JWT::decode(
            $removeBearerWord,
            self::$private,
            [self::$mode]
        );

        if($decode->aud !== self::Aud())
        {
            throw new \Exception("Invalid user logged in.");
        }
    }

    static function decode($bearer)
    {
        $removeBearerWord = str_replace("Bearer ", "", $bearer);
        $decode           = JWT::decode($removeBearerWord, self::$public, [self::$mode]);
        return $decode;
    }

    static function verify($bearer){
        try {
            JWT::$leeway = 60;
            $removeBearerWord = str_replace("Bearer ", "", $bearer);
            $decode           = JWT::decode($removeBearerWord, self::$public, [self::$mode]);
            return $decode;
        } catch(\Exception $e){
            return false;
        }
    }

    static function Aud(){
        $aud = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $aud = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $aud = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $aud = $_SERVER['REMOTE_ADDR'];
        }

        $aud .= @$_SERVER['HTTP_USER_AGENT'];
        $aud .= gethostname();

        return sha1($aud);
    }

}
?>