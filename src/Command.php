<?php

namespace Utopia;

use InvalidArgumentException;
use Stringable;
use Utopia\Servers\Validator;

class Command implements Stringable
{
    /**
     * @var array<int, string>
     */
    protected array $arguments = [];

    public function __construct(string $executable)
    {
        $this->arguments[] = $this->normalize($executable, 'Command executable');
    }

    public function add(string|int|float|Stringable $value, Validator|callable|null $validator = null): self
    {
        $argument = $this->normalize($value, 'Command argument');

        if ($validator !== null) {
            $this->validate($argument, $validator);
        }

        $this->arguments[] = $argument;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function toArray(): array
    {
        return $this->arguments;
    }

    public function toString(): string
    {
        $escaped = array_map(static fn (string $argument): string => escapeshellarg($argument), $this->arguments);

        return implode(' ', $escaped);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    protected function normalize(string|int|float|Stringable $value, string $context): string
    {
        $value = (string) $value;

        if ($value === '') {
            throw new InvalidArgumentException($context.' cannot be empty');
        }

        return $value;
    }

    /**
     * @param  Validator|callable  $validator
     */
    protected function validate(string $argument, Validator|callable $validator): void
    {
        if ($validator instanceof Validator) {
            if (! $validator->isValid($argument)) {
                throw new InvalidArgumentException('Invalid command argument: '.$argument.' ('.$validator->getDescription().')');
            }

            return;
        }

        $isValid = (bool) $validator($argument);

        if (! $isValid) {
            throw new InvalidArgumentException('Invalid command argument: '.$argument);
        }
    }
}
