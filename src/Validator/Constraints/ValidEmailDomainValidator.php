<?php 
namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidEmailDomainValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (strpos($value, '@') !== false) {
            list(, $domain) = explode('@', $value);

            if (!checkdnsrr($domain, 'MX')) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ domain }}', $domain)
                    ->addViolation();
            }
        }
    }
}
