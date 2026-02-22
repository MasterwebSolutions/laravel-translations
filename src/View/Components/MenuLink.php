<?php

namespace Masterweb\Translations\View\Components;

use Illuminate\View\Component;

class MenuLink extends Component
{
    public string $url;
    public string $label;
    public string $icon;
    public bool $isActive;

    public function __construct(
        ?string $label = null,
        ?string $icon = null
    ) {
        $prefix = config('translations.route_name_prefix', 'translations');
        $this->url = route("{$prefix}.index");
        $this->label = $label ?? 'Translations';
        $this->icon = $icon ?? 'translate';
        $this->isActive = request()->is(trim(config('translations.admin_prefix', 'admin/translations'), '/') . '*');
    }

    public function render()
    {
        return view('translations::components.menu-link');
    }
}
