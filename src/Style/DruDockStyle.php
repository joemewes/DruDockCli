<?php

namespace Docker\Drupal\Style;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DruDockStyle extends SymfonyStyle
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        parent::__construct($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, $newLine = true)
    {
        $message = sprintf('<fg=yellow>%s</>', $message);
        if ($newLine) {
            $this->writeln($message);
        } else {
            $this->write($message);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function comment($message, $newLine = true)
    {
        $this->block(
            $message,
            null,
            'bg=yellow;fg=black',
            ' ',
            true
        );
    }

    public function note($message)
    {
        $this->block(
            $message,
            null,
            'bg=blue;fg=white',
            ' ',
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function simple($message, $newLine = true)
    {
        $message = sprintf(' %s', $message);
        if ($newLine) {
            $this->writeln($message);
        } else {
            $this->write($message);
        }
    }

  /**
   * {@inheritdoc}
   */
    public function section($message)
    {
      // If we do not have the clean-output flag prevent outputting the sections.
        if (false === $this->input->hasParameterOption(array('--clean-output', '-co'), true)) {
            parent::section($message);
        }
    }
}
