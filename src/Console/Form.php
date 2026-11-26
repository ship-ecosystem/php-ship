<?php

declare(strict_types=1);

namespace Ship\Console;

use Laravel\Prompts\Exceptions\FormRevertedException;
use Laravel\Prompts\FormBuilder;

/**
 * Questions asked as steps of one Prompts form (see Ui::form()): Ctrl+U on a step's first question
 * goes back to the previous step, which starts at the answer given before.
 *
 * Every step after the first says so in its first question's hint (see Ui::offeringBack()). Not
 * the first step, which has nothing to go back to, nor a step's later questions (a process's
 * command, say), where Ctrl+U goes back a whole step and drops what was just entered in this one.
 */
final class Form
{
    private bool $hasSteps = false;

    private ?string $rewindTarget = null;

    public function __construct(private readonly FormBuilder $form, private readonly Ui $ui)
    {
    }

    public function add(\Closure $step, string $name): self
    {
        return $this->addIf(true, $step, $name);
    }

    public function addIf(\Closure|bool $condition, \Closure $step, string $name): self
    {
        // Laravel Prompts skips disabled steps while reverting.
        $this->form->addIf(
            fn (array $answers): bool => ($this->rewindTarget === null || $this->rewindTarget === $name)
                && (is_bool($condition) ? $condition : $condition($answers)),
            $this->offeringBack(function (array $answers, mixed $previous) use ($step): mixed {
                $this->rewindTarget = null;

                return $step($answers, $previous);
            }),
            $name,
        );

        return $this;
    }

    /** Return to an earlier enabled step from within a step callback. */
    public function rewindTo(string $name): never
    {
        $this->rewindTarget = $name;

        throw new FormRevertedException();
    }

    /**
     * @return array<mixed>
     */
    public function submit(): array
    {
        return $this->form->submit();
    }

    private function offeringBack(\Closure $step): \Closure
    {
        if (!$this->hasSteps) {
            $this->hasSteps = true;

            return $step;
        }

        $ui = $this->ui;

        return static fn (array $answers, mixed $previous): mixed => $ui->offeringBack(static fn (): mixed => $step($answers, $previous));
    }
}
