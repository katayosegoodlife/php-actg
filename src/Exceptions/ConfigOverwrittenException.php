<?php

namespace Akizuki\ACTG\Exceptions;

use LogicException;


/**
 * [ Exception ] Config Overwritten
 * 
 * @author 4kizuki <akizuki.c10.l65@gmail.com>
 * @copyright 2017 4kizuki. All Rights Reserved.
 * @package 4kizuki/php-actg
 * @since 1.0.0-beta
 */
final class ConfigOverwrittenException extends LogicException {
    
    public function __construct( string $className, string $configName ) {
        
        parent::__construct( "Config \"{$className}::{$configName}\" is tried to overwrite." );
        
    }
    
}
