<?php

namespace VRciF\PhpSmartdevice;

class EchoDebugClosure {
    public function __invoke() {
        $args = \func_get_args();

        echo $this->formatArgs($args) . PHP_EOL;
    }

    protected function formatArgs ($args) {
        $caller = \array_shift($args);

        $formattedArgs = [];
        if (\is_array($caller)){
            $formattedCaller = '';
            if(isset($caller['function'])) {
                $formattedCaller .= $caller['function'];
            }
            if(isset($caller['line'])) {
                $formattedCaller .= ':'.$caller['line'];
            }
            if (!empty($formattedCaller)) {
                $formattedArgs[] = $formattedCaller;
            }
        }

        foreach ($args as $arg) {
            switch (\gettype($args)) {
                case 'boolean':
                    $formattedArgs[] = $arg ? 'true' : 'false';
                    break;
                case 'integer':
                case 'double':
                case 'string':
                case 'resource':
                case 'resource (closed)':
                    $formattedArgs[] = $arg;
                    break;
                case 'object':
                    $formattedArgs[] = '['.\get_class($arg).']=';
                case 'array':
                default:
                    $formattedArgs[] = \json_encode($arg);
                    break;
                case 'NULL':
                    $formattedArgs[] = 'null';
                    break;
            }
        }

        return \implode(' ', $formattedArgs);
    }
}