<?php


namespace App\Lib\Selenium;


class DeferredContext
{
    protected $deferredActions = [];

    public function defer(callable $deferAction)
    {
        $this->deferredActions[] = $deferAction;
    }

    public function executeDeferredActions()
    {
        $actionsCount = count($this->deferredActions);
        if ($actionsCount > 0) {
            for ($i = $actionsCount - 1; $i >= 0; $i--) {
                $action = $this->deferredActions[$i];
                try {
                    $action();
                } catch (\Exception $e) {

                }
                unset($this->deferredActions[$i]);
            }
        }

        $this->deferredActions = [];
    }

    public static function run(callable $callback) {
        $context = new DeferredContext();
        try {
            return $callback($context);
        } finally {
            $context->executeDeferredActions();
        }
    }
}
