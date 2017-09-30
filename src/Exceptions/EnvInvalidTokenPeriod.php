<?php

namespace Akizuki\ACTG\Exceptions;

use RuntimeException;


/**
 * [ Exception ] Invalid Token Period Given
 * 
 * @author 4kizuki <akizuki.c10.l65@gmail.com>
 * @copyright 2017 4kizuki. All Rights Reserved.
 * @package 4kizuki/php-actg
 * @since 1.0.0-alpha
 */
final class EnvInvalidTokenPeriod extends RuntimeException {
    
    public function __construct( ) {
        
        parent::__construct( 'Invalid Token Period.' );
        
    }
    
}
