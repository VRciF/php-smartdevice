<?php

namespace VRciF\PhpSmartdevice;

trait DebugTrait {
    protected $debugClosure = null;
    protected $debugEnabled = false;

    public function setDebugClosure ($debug = null) {
        $this->debugClosure = $debug;
        return $this;
    }
    public function setDebugEnabled (bool $enabled=true) {
        $this->debugEnabled = $enabled;
        return $this;
    }

    public function debug() {
        if ($this->debugClosure && $this->debugEnabled) {
            $bt = \debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = \array_shift($bt);

            $args = \array_merge([$caller], \func_get_args());

            \call_user_func_array($this->debugClosure,  $args);
        }
    }
}