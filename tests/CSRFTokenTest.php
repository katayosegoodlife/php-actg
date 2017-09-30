<?php

namespace Akizuki\ACTG;

use PHPUnit\Framework\TestCase;
use Akizuki\ACTG\CSRFToken;
use Akizuki\ACTG\Exceptions\InvalidTokenException;


class ENVCSRFToken extends CSRFToken {
    
    protected static $SessionKey = null;
    protected static $TokenPeriod = null;
    protected static $SessionAutoStart = null;
    protected static $InputName = null;
    
}

class CSRFTokenTest extends TestCase {
    
    public function setUp( ) {
        $_SESSION = [ ];
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
    
}