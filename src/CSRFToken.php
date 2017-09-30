<?php

namespace Akizuki\ACTG;

use Akizuki\BSC\NonCreatable;
use Akizuki\ACTG\Exceptions\ {
    InvalidTokenPeriod,
    SessionInactiveException,
    InvalidTokenException,
    EnvInvalidTokenPeriod
};


/**
 * [ Library ] CSRF Token Generator
 *
 * Can customize behaviour by those variables.
 * - $_ENV[ 'AKIZUKI_ATGC_SESSION_KEY' ]  : int
 * - $_ENV[ 'AKIZUKI_ATGC_TOKEN_PERIOD' ] : string
 * - $_ENV[ 'AKIZUKI_ATGC_SESSION_AUTO_START' ] : int ( will be casted to boolean )
 * - $_ENV[ 'AKIZUKI_ATGC_POST_NAME' ] : string
 * Those values must be set before using any functions of this library.
 * Default settings and ENV settings will be overwritten by Set*** methods.
 * 
 * @author 4kizuki <akizuki.c10.l65@gmail.com>
 * @copyright 2017 4kizuki. All Rights Reserved.
 * @package 4kizuki/php-actg
 * @since 1.0.0-alpha
 */
class CSRFToken extends NonCreatable {
    
    const DefaultSessionKey = '4kizuki/php-actg';
    const DefaultTokenPeriod = 30 * 60;
    const DefaultSessionAutoStart = false;
    const DefaultInputName = 'AKIZUKI_ATGC_TOKEN';
    
    const ENVSessionKey = 'AKIZUKI_ATGC_SESSION_KEY';
    const ENVTokenPeriod = 'AKIZUKI_ATGC_TOKEN_PERIOD';
    const ENVSessionAutoStart = 'AKIZUKI_ATGC_SESSION_AUTO_START';
    const EnvInputName = 'AKIZUKI_ATGC_POST_NAME';
    
    const key_Token = 'token';
    const key_Expiration = 'expiration_date';
    
    
    public static function GenerateHiddenInput( ) : string {
        
        $g = self::Generate( );
        $p = self::GetInputName( );
        
        return "<input type=\"hidden\" name=\"{$p}\" value=\"{$g}\" />";
        
    }
    
    public static function PostVerify( bool $nothrow = false ) : bool {
        
        $p = self::GetInputName( );
        $t = ( isset( $_POST[ $p ] ) ? ( $_POST[ $p ] ) : ( '' ) );
        
        return self::Verify( $t, $nothrow );
        
    }
    
    
    public static function Generate( ) : string {
        
        self::InitSession( );
        
        $t = self::GenerateRawToken( );
        $_SESSION[ self::GetSessionKey( ) ][ ] = [
            self::key_Token => $t,
            self::key_Expiration => time( ) + self::GetTokenPeriod( )
        ];
        
        return $t;
        
    }
    
    
    public static function Verify( string $token, bool $nothrow = false ) : bool {
        
        $result = self::RawVerify( $token );
        
        if( !$nothrow && !$result ) throw new InvalidTokenException;
        
        return $result;
        
    }
    
    protected static function RawVerify( string $token ) : bool {
        
        self::InitSession( );
        
        $key = self::GetSessionKey( );
        foreach( $_SESSION[ $key ] as $i => $t ) {
            if( hash_equals( $t[self::key_Token], $token ) ) {
                unset( $_SESSION[ $key ][ $i ] );
                return true;
            }
        }
        
        return false;
        
    }
    
    protected static function GenerateRawToken( ) : string {
        
        return str_replace(
            [ '+', '/' ],
            [ '_', '.' ],
            base64_encode( random_bytes( 96 ) )
        );
        
    }
    
    protected static function IsSessionActive( ) : bool {
        
        return isset( $_SESSION );
        
    }
    
    
    protected static function InitSession( ) {
        
        // Session not began
        if( !self::IsSessionActive( ) ) {
            
            if( self::GetSessionAutoStart( ) ) {
                session_start( );
            } else {
                throw SessionInactiveException;
            }
            
        }
        
        $key = self::GetSessionKey( );
        
        // Prepare Array
        if( !isset( $_SESSION[ $key ] ) or !is_array( $_SESSION[ $key ] ) )
            $_SESSION[ $key ] = [ ];

        // Remove Expired Token
        $now = time( );
        
        $removed = false;
        foreach( $_SESSION[ $key ] as $i => $token ) {
            if( $token[ self::key_Expiration ] < $now ) {
                $removed = true;
                unset( $_SESSION[ $key ][ $i ] );
            }
        }
        
        if( $removed ) $_SESSION[ $key ] = array_values( $_SESSION[ $key ] );
        
    }
    
    
    
    
    /***************************************************************************
     * 
     * Value Generator
     * 
     **************************************************************************/
    
    protected static $SessionKey = null;
    
    public static function SetSessionKey( string $key ) : void {
        
        static::$SessionKey = $key;
        
    }
    
    protected static function GetSessionKey( ) : string {
        
        if( !is_null( static::$SessionKey ) ) return static::$SessionKey;
        
        if( isset( $_ENV[ self::ENVSessionKey ] )
            &&  is_string( $_ENV[ self::ENVSessionKey ] ) ) {
            
            self::SetSessionKey( $_ENV[ self::ENVSessionKey ] );
            return self::GetSessionKey( );
            
        }
        
        return self::DefaultSessionKey;
        
    }
    
    protected static $TokenPeriod = null;
    
    public static function SetTokenPeriod( int $period ) : void {
        
        if( $period <= 0 ) throw new InvalidTokenPeriod;
        static::$TokenPeriod = $period;
        
    }
    
    protected static function GetTokenPeriod( ) : int {
        
        if( !is_null( static::$TokenPeriod ) ) return static::$TokenPeriod;
        
        if( isset( $_ENV[ self::ENVTokenPeriod ] ) ) {
            
            if( !is_numeric( $_ENV[ self::ENVTokenPeriod ] ) ) {
                throw new EnvInvalidTokenPeriod;
            }
            
            $ip = ( int ) $_ENV[ self::ENVTokenPeriod ];
            
            if( $ip <= 0 ) throw new EnvInvalidTokenPeriod;
            
            self::SetTokenPeriod( $ip );
            return self::GetTokenPeriod( );
            
        }
        
        return self::DefaultTokenPeriod;    
        
    }
    
    protected static $SessionAutoStart = null;
    
    public static function SetSessionAutoStart( bool $autoStart ) : void {
        
        static::$SessionAutoStart = $autoStart;
        
    }
    
    protected static function GetSessionAutoStart( ) : bool {
        
        if( !is_null( static::$SessionAutoStart ) ) return static::$SessionAutoStart;
        
        if( isset( $_ENV[ self::ENVSessionAutoStart ] )
            &&  is_numeric( $_ENV[ self::ENVSessionAutoStart ] ) ) {
            
            self::SetSessionAutoStart( (bool) ( (int) $_ENV[ self::ENVTokenPeriod ] ) );
            return self::GetSessionAutoStart( );
            
        }
        
        return self::DefaultSessionAutoStart;
        
    }
    
    protected static $InputName = null;
    
    public static function SetInputName( string $inputName ) : void {
        
        static::$InputName = $inputName;
        
    }
    
    protected static function GetInputName( ) : string {
        
        if( !is_null( static::$InputName ) ) return static::$InputName;
        
        if( isset( $_ENV[ self::EnvInputName ] )
            &&  is_string( $_ENV[ self::EnvInputName ] ) ) {
            
            self::SetInputName( (string) $_ENV[ self::EnvInputName ] );
            return self::GetInputName( );
            
        }
        
        return self::DefaultInputName;
        
    }
    
}
