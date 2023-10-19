<?php

declare(strict_types=1);

namespace Dotclear\Plugin\kUtRL;

/**
 * @brief       kUtRL combo.
 * @ingroup     kUtRL
 *
 * @author      Jean-Christian Denis (author)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Combo
{
    /**
     * @return  array<string,string>
     */
    public static function sortbyCombo(): array
    {
        return [
            __('Date')       => 'kut_dt',
            __('Short link') => 'kut_hash',
            __('Long link')  => 'kut_url',
            __('Service')    => 'kut_service',
        ];
    }

    /**
     * @return  array<string,string>
     */
    public static function ServicesCombo(bool $with_none = false): array
    {
        $services_combo = [];
        foreach (Utils::getServices() as $service_id => $service) {
            $o                            = new $service();
            $services_combo[__($o->name)] = $o->id;
        }
        if ($with_none) {
            $services_combo = array_merge([__('Disabled') => ''], $services_combo);
        }

        return $services_combo;
    }
}
