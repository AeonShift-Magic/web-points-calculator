<?php

declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\UpdateInterface;
use Attribute;
use InvalidArgumentException;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

#[Attribute(Attribute::TARGET_CLASS)]
final class DateRangeValidator extends ConstraintValidator
{
    /**
     * Validates that the ending date is after the starting date.
     *
     * @param mixed $value The value to validate
     * @param Constraint $constraint The constraint for validation
     */
    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        // Ensure the object has getStartingAt and getEndingAt methods
        if (! $value instanceof UpdateInterface) {
            return;
        }

        if(! $constraint instanceof DateRange) {
            throw new InvalidArgumentException(sprintf('Expected instance of %s, got %s', DateRange::class, get_debug_type($constraint)));
        }

        $start = $value->getStartingAt();
        $end = $value->getEndingAt();

        if($start === null || $end === null) {
            return;
        }

        if ($end <= $start) {
            $context = $this->context;
            $context->buildViolation(! empty($constraint->message) ? $constraint->message : 'daterange.invalid')
                ->setParameter('{{ startingAt }}', $start->format('Y-m-d H:i'))
                ->setParameter('{{ endingAt }}', $end->format('Y-m-d H:i'))
                ->atPath('endingAt')
                ->addViolation();
        }
    }
}
