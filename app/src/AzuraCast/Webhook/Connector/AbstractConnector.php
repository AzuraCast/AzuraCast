<?php
namespace AzuraCast\Webhook\Connector;

use Entity;

abstract class AbstractConnector implements ConnectorInterface
{
    public function shouldDispatch(array $current_events, array $triggers): bool
    {
        if (empty($triggers)) {
            return true;
        }

        foreach($triggers as $trigger) {
            if (in_array($trigger, $current_events)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Flatten an array from format:
     * [
     *   'user' => [
     *     'id' => 1,
     *     'name' => 'test',
     *   ]
     * ]
     *
     * to format:
     * [
     *   'user.id' => 1,
     *   'user.name' => 'test',
     * ]
     *
     * This function is used to create replacements for variables in strings.
     *
     * @param array $array
     * @param string $separator
     * @param null $prefix
     * @return array
     */
    protected function _flattenArray(array $array, $separator = '.', $prefix = null): array
    {
        $return = [];

        foreach($array as $key => $value) {
            $return_key = $prefix ? $prefix.$separator.$key : $key;
            if (\is_array($value)) {
                $return = array_merge($return, $this->_flattenArray($value, $separator, $return_key));
            } else {
                $return[$return_key] = $value;
            }
        }

        return $return;
    }

    /**
     * Replace variables in the format {{ blah }} with the flattened contents of the NowPlaying API array.
     *
     * @param array $raw_vars
     * @param Entity\Api\NowPlaying $np
     * @return array
     */
    public function _replaceVariables(array $raw_vars, Entity\Api\NowPlaying $np): array
    {
        $values = $this->_flattenArray(json_decode(json_encode($np), true));
        $vars = [];

        foreach($raw_vars as $var_key => $var_value) {
            // Replaces {{ var.name }} with the flattened $values['var.name']
            $vars[$var_key] = preg_replace_callback("/\{\{(\s*)([a-zA-Z0-9\-_\.]+)(\s*)\}\}/", function($matches) use ($values) {
                $inner_value = strtolower(trim($matches[2]));
                return $values[$inner_value] ?? '';
            }, $var_value);
        }

        return $vars;
    }
}