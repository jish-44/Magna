<?php

declare(strict_types=1);

namespace Magna\Settings;

class GeneralSettings extends Settings
{
    public string $site_name = 'Magna CMS';

    public string $default_locale = 'en';

    public bool $registration_enabled = false;
}
