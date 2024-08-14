<?php
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ValidEmailDomain extends Constraint
{
    public $message = 'The email domain "{{ domain }}" does not have valid MX records.';

    public function validatedBy()
    {
        return static::class.'Validator';
    }
}
