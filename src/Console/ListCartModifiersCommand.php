<?php

namespace Ingenius\ShopCart\Console;

use Illuminate\Console\Command;
use Ingenius\ShopCart\Services\CartModifierManager;

class ListCartModifiersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopcart:modifiers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered cart modifiers with their priorities';

    /**
     * The cart modifier manager instance.
     *
     * @var CartModifierManager
     */
    protected $modifierManager;

    /**
     * Create a new command instance.
     *
     * @param CartModifierManager $modifierManager
     * @return void
     */
    public function __construct(CartModifierManager $modifierManager)
    {
        parent::__construct();
        $this->modifierManager = $modifierManager;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $modifiers = $this->modifierManager->getModifiers();

        if (empty($modifiers)) {
            $this->info('No cart modifiers are registered.');
            return 0;
        }

        $headers = ['Priority', 'Name', 'Class'];
        $rows = [];

        foreach ($modifiers as $modifier) {
            $rows[] = [
                $modifier->getPriority(),
                $modifier->getName(),
                get_class($modifier)
            ];
        }

        // Sort rows by priority
        usort($rows, function ($a, $b) {
            return $a[0] <=> $b[0];
        });

        $this->table($headers, $rows);
        $this->info(sprintf('%d cart modifiers found.', count($modifiers)));

        return 0;
    }
}
