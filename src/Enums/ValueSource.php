<?php

namespace Akizuki\ACTG\Enums;

use Akizuki\enum\StringEnum;


/**
 * [ Enum ] Value Source
 * 
 * @author 4kizuki <akizuki.c10.l65@gmail.com>
 * @copyright 2017 4kizuki. All Rights Reserved.
 * @package 4kizuki/php-actg
 * @since 1.0.0-beta
 */
class ValueSource extends StringEnum {
    
    public const FromConstant = 'Class Constant ( Default )';
    public const FromEnvValue = 'Environment Variable';
    public const FromSetter   = 'Setter Method';
    
}
