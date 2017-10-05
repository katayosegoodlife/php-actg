<?php

namespace Akizuki\ACTG;

use Akizuki\BSC\NonCreatable;
use Akizuki\ACTG\Enums\ValueSource;
use Akizuki\ACTG\Exceptions\ {
    SessionInactiveException,
    InvalidTokenException,
    ConfigOverwrittenException,
    InvalidConfigException
};


/**
 * [ Library ] CSRF Token Generator
 *
 * Can customize behaviour by those variables.
 * - $_ENV[ 'AKIZUKI_ATGC_SESSION_KEY' ]        : string  ( no regex validation )
 * - $_ENV[ 'AKIZUKI_ATGC_TOKEN_PERIOD' ]       : numeric ( greater than 0 )
 * - $_ENV[ 'AKIZUKI_ATGC_SESSION_AUTO_START' ] : numeric ( casted to boolean )
 * - $_ENV[ 'AKIZUKI_ATGC_POST_NAME' ]          : string  ( no regex validation )
 * Those values must be set before using any functions of this library.
 * Default settings and ENV settings will be overwritten by Set*** methods.
 * 
 * @author 4kizuki <akizuki.c10.l65@gmail.com>
 * @copyright 2017 4kizuki. All Rights Reserved.
 * @package 4kizuki/php-actg
 * @since 1.0.0-alpha
 */
class CSRFToken extends NonCreatable {
    
    // Customizable
    protected const DefaultSessionKey = '4kizuki/php-actg';
    protected const DefaultTokenPeriod = 30 * 60;
    protected const DefaultSessionAutoStart = false;
    protected const DefaultInputName = 'AKIZUKI_ATGC_TOKEN';
    
    // Customizable
    public const ENVSessionKey = 'AKIZUKI_ATGC_SESSION_KEY';
    public const ENVTokenPeriod = 'AKIZUKI_ATGC_TOKEN_PERIOD';
    public const ENVSessionAutoStart = 'AKIZUKI_ATGC_SESSION_AUTO_START';
    public const ENVInputName = 'AKIZUKI_ATGC_POST_NAME';
    
    // Not Overridable
    protected const key_Token = 'token';
    protected const key_Expiration = 'expiration_date';
    
    
    final public static function GenerateHiddenInput( ) : string {
        
        $g = self::Generate( );
        $p = self::GetInputName( );
        
        return "<input type=\"hidden\" name=\"{$p}\" value=\"{$g}\" />";
        
    }
    
    final public static function PostVerify( bool $nothrow = false ) : bool {
        
        $p = self::GetInputName( );
        $t = ( isset( $_POST[ $p ] ) ? ( $_POST[ $p ] ) : ( '' ) );
        
        return self::Verify( $t, $nothrow );
        
    }
    
    
    final public static function Generate( ) : string {
        
        self::InitSession( );
        
        $t = self::GenerateRawToken( );
        $_SESSION[ self::GetSessionKey( ) ][ ] = [
            self::key_Token => $t,
            self::key_Expiration => time( ) + static::GetTokenPeriod( )
        ];
        
        return $t;
        
    }
    
    
    final public static function Verify( string $token, bool $nothrow = false ) : bool {
        
        $result = self::RawVerify( $token );
        
        if( !$nothrow && !$result ) throw new InvalidTokenException;
        
        return $result;
        
    }
    
    /***************************************************************************
     * 
     * Helper Functions
     * 
     **************************************************************************/
    final protected static function RawVerify( string $token ) : bool {
        
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
    
    final protected static function GenerateRawToken( ) : string {
        
        return str_replace(
            [ '+', '/' ],
            [ '_', '.' ],
            base64_encode( random_bytes( 96 ) )
        );
        
    }
    
    final protected static function IsSessionActive( ) : bool {
        
        return isset( $_SESSION );
        
    }
    
    private static $initialized = [ ];
    final protected static function InitSession( ) {
        
        if( isset( self::$initialized[ static::class ] ) ) return;
        
        // Session not began
        if( !self::IsSessionActive( ) ) {
            if( !self::GetSessionAutoStart( ) ) throw new SessionInactiveException;
            session_start( );
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
        
        self::$initialized[ static::class ] = true;
        
    }
    
    
    
    
    /***************************************************************************
     * 
     * Value Generator
     * 
     **************************************************************************/
    
    private static $values = [ ];
    private const akSessionKey = 'Session Key';
    private const akTokenPeriod = 'Token Period';
    private const akSessionAutoStart = 'Session Auto Start';
    private const akInputName = 'HTML Input Name';
    
    final private static function SetConfig( $value, string $key, ValueSource $source, ?callable $validator = null ) : void {
        
        if( isset( self::$values[ static::class ][ $key ] ) )
            throw new ConfigOverwrittenException( static::class, $key );
        
        if( !is_null( $validator ) ) $value = $validator( $value, $source );
        
        self::$values[ static::class ][ $key ] = $value;
        
    }
    
    final private static function GetConfig( string $key, string $envKey, string $defaultValue, ?callable $validator = null ) {
        
        if( isset( self::$values[ static::class ][ $key ] ) )
            return self::$values[ static::class ][ $key ];
        
        if( isset( $_ENV[ $envKey ] )
            && is_string( $_ENV[ $envKey ] ) ) {
            
            self::SetConfig( $_ENV[ $envKey ], $key, new ValueSource( ValueSource::FromEnvValue ), $validator );
            return self::GetConfig( $key, $envKey, $defaultValue );
            
        }
        
        self::SetConfig( $defaultValue, $key, new ValueSource( ValueSource::FromConstant ), $validator );
        return $defaultValue;
        
    }
    
    /***************************************************************************
     * 
     * Value Generator - Session Key
     * 
     **************************************************************************/
    final public static function SetSessionKey( string $key ) : void {
        
        self::SetConfig(
                $key,
                self::akSessionKey,
                new ValueSource( ValueSource::FromSetter ),
                self::ValidateSessionKey( )
        );
        
    }
    
    final public static function GetSessionKey( ) : string {
        
        return self::GetConfig(
                self::akSessionKey,
                static::ENVSessionKey,
                static::DefaultSessionKey,
                self::ValidateSessionKey( )
        );
        
    }
    
    final private static function ValidateSessionKey( $value = null, ?ValueSource $source = null ) : string {
        
        if( is_null( $source ) ) return __METHOD__;
        
        if( !is_string( $value ) && !is_int( $value ) )
            throw new InvalidConfigException( self::akSessionKey, $source );
        
        return (string) $value;
        
    }
    
    /***************************************************************************
     * 
     * Value Generator - Token Period
     * 
     **************************************************************************/
    final public static function SetTokenPeriod( int $period ) : void {
        
        self::SetConfig(
                $period,
                self::akTokenPeriod,
                new ValueSource( ValueSource::FromSetter ),
                self::ValidateTokenPeriod( )
        );
        
    }
    
    final public static function GetTokenPeriod( ) : int {
        
        return self::GetConfig(
                self::akTokenPeriod,
                static::ENVTokenPeriod,
                static::DefaultTokenPeriod,
                self::ValidateTokenPeriod( )
        );
        
    }
    
    final private static function ValidateTokenPeriod( $value = null, ?ValueSource $source = null ) {
        
        if( is_null( $source ) ) return __METHOD__;
        
        if( !is_numeric( $value ) )
            throw new InvalidConfigException( self::akTokenPeriod, $source );
        
        $intValue = (int) $value;
        
        if( $intValue <= 0 ) throw new InvalidConfigException( self::akTokenPeriod, $source );
        
        return $intValue;
        
    }
    
    
    
    /***************************************************************************
     * 
     * Value Generator - Session Auto Start
     * 
     **************************************************************************/
    final public static function SetSessionAutoStart( bool $autoStart ) : void {
        
        self::SetConfig(
                $autoStart,
                self::akSessionAutoStart,
                new ValueSource( ValueSource::FromSetter ),
                self::ValidateSessionAutoStart( )
        );
        
    }
    
    final public static function GetSessionAutoStart( ) : bool {
        
        return self::GetConfig(
                self::akSessionAutoStart,
                static::ENVSessionAutoStart,
                static::DefaultSessionAutoStart,
                self::ValidateSessionAutoStart( ) );
        
    }
    
    final private static function ValidateSessionAutoStart( $value = null, ?ValueSource $source = null ) {
        
        if( is_null( $source ) ) return __METHOD__;
        
        if( !is_numeric( $value ) )
            throw new InvalidConfigException( self::akSessionAutoStart, $source );
        
        return (bool) (int) $value;
        
    }
    
    /***************************************************************************
     * 
     * Value Generator - HTML Input Name
     * 
     **************************************************************************/
    final public static function SetInputName( string $inputName ) : void {
        
        self::SetConfig(
                $inputName,
                self::akInputName,
                new ValueSource( ValueSource::FromSetter ),
                self::ValidateInputName( )
        );
        
    }
    
    final public static function GetInputName( ) : string {
        
        return self::GetConfig(
                self::akInputName,
                static::ENVInputName,
                static::DefaultInputName,
                self::ValidateInputName( )
        );
        
    }
    
    final private static function ValidateInputName( $value = null, ?ValueSource $source = null ) : string {
        
        if( is_null( $source ) ) return __METHOD__;
        
        if( !is_string( $value ) )
            throw new InvalidConfigException( self::akInputName, $source );
        
        return (string) $value;
        
    }
}
