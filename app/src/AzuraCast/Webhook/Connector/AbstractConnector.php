<?php
namespace AzuraCast\Webhook\Connector;

use Entity;

abstract class AbstractConnector implements ConnectorInterface
{
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
            if (\is_array($value)) {
                $return = array_merge($return, $this->_flattenArray($value, $separator, $key));
            } else {
                $return_key = $prefix ? $prefix.$separator.$key : $key;
                $return[$return_key] = $value;
            }
        }

        return $return;
    }
}