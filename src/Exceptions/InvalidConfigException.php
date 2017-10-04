<?php

namespace Akizuki\ACTG\Exceptions;

use LogicException;
use Akizuki\ACTG\Enums\ValueSource;


/**
 * [ Exception ] Invalid Config
 * 
 * @author 4kizuki <akizuki.c10.l65@gmail.com>
 * @copyright 2017 4kizuki. All Rights Reserved.
 * @package 4kizuki/php-actg
 * @since 1.0.0-beta
 */
final class InvalidConfigException extends LogicException {
    
    public function __construct( string $configName, ValueSource $source ) {
        
        parent::__construct( "Invalid Config Given: {$configName} set by {$source->value( )}." );
        
    }
    
}
