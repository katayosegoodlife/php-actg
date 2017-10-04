<?php

namespace Akizuki\ACTG;

use PHPUnit\Framework\TestCase;
use Akizuki\ACTG\CSRFToken;
use Akizuki\ACTG\Exceptions\{ 
    InvalidTokenException,
    InvalidConfigException
};


class ENVCSRFToken extends CSRFToken { }
class ENVErrorCSRFToken extends CSRFToken { }

class CSRFTokenTest extends TestCase {
    
    public function setUp( ) {
        
        if( !isset( $_SESSION ) ) $_SESSION = [ ];
    }
    
    /**
     * @test
     */
    public function SuccessTest( ) {
        
        $t = CSRFToken::Generate( );
        $this->assertEquals( true, CSRFToken::Verify( $t, true ) );
        
    }
    
    /**
     * @test
     */
    public function ErrorTestRemoved( ) {
        
        $this->expectException( InvalidTokenException::class );
        
        $t = CSRFToken::Generate( );
        $this->assertEquals( true, CSRFToken::Verify( $t ) );
        
        CSRFToken::Verify( $t );
        
    }
    
    /**
     * @test
     */
    public function ErrorTest( ) {
        
        $this->expectException( InvalidTokenException::class );
        
        $this->assertEquals( true, CSRFToken::Verify( '33-4' ) );
        
    }
    
    /**
     * @test
     */
    public function ErrorTestNoThrow( ) {
        
        $this->assertEquals( false, CSRFToken::Verify( 'lol', true ) );
        
    }
    
    
    /**
     * @test
     */
    public function ENVTest( ) {
        
        $EnvCache = $_ENV;
        $CaughtThrowable = null;
        $_ENV = [ ];
        
        try {
            
            $_ENV[ ENVCSRFToken::ENVSessionKey ] = '33-4 = NNDY';
            $t = ENVCSRFToken::Generate( );
            ENVCSRFToken::Verify( $t );
            
            $this->assertEquals( true, isset( $_SESSION[ '33-4 = NNDY' ] ) );
            
        } catch( \Throwable $t ) {
            
            $CaughtThrowable = $t;
            
        } finally {
            
            $_ENV = $EnvCache;
            
        }
        
        if( !is_null( $CaughtThrowable ) ) throw $CaughtThrowable;
        
    }
    
    
    /**
     * @test
     */
    public function ENVErrorValueTest( ) {
        
        $this->expectException( InvalidConfigException::class );
        
        $EnvCache = $_ENV;
        $_ENV = [ ];
        $CaughtThrowable = null;
        
        try {
            
            $_ENV[ ENVErrorCSRFToken::ENVTokenPeriod ] = '-1';
            $t = ENVErrorCSRFToken::Generate( );
            ENVErrorCSRFToken::Verify( $t );
            
        } catch( \Throwable $t ) {
            
            $CaughtThrowable = $t;
            
        } finally {
            
            $_ENV = $EnvCache;
            
        }
        
        if( !is_null( $CaughtThrowable ) ) throw $CaughtThrowable;
        
    }
    
}