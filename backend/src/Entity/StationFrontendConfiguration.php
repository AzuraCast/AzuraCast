<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\AbstractArrayEntity;
use App\Utilities\Strings;
use App\Utilities\Types;
use LogicException;

class StationFrontendConfiguration extends AbstractArrayEntity
{
    public function __construct(array $elements = [])
    {
        // Generate defaults if not set.
        $autoAssignPasswords = [
            'source_pw',
            'admin_pw',
            'relay_pw',
            'streamer_pw',
        ];

        foreach ($autoAssignPasswords as $autoAssignPassword) {
            if (empty($elements[$autoAssignPassword])) {
                $elements[$autoAssignPassword] = Strings::generatePassword();
            }
        }

        parent::__construct($elements);
    }

    public ?string $custom_config {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public string $source_pw {
        get => Types::stringOrNull($this->get(__PROPERTY__), true)
            ?? throw new LogicException('Password not generated');
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public string $admin_pw {
        get => Types::stringOrNull($this->get(__PROPERTY__), true)
            ?? throw new LogicException('Password not generated');
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public string $relay_pw {
        get => Types::stringOrNull($this->get(__PROPERTY__), true)
            ?? throw new LogicException('Password not generated');
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public string $streamer_pw {
        get => Types::stringOrNull($this->get(__PROPERTY__))
            ?? throw new LogicException('Password not generated');
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?int $port {
        get => Types::intOrNull($this->get(__PROPERTY__));
        set (int|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?int $max_listeners {
        get => Types::intOrNull($this->get(__PROPERTY__));
        set (int|string|null $value) {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?string $banned_ips {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?string $banned_user_agents {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?array $banned_countries {
        get => Types::arrayOrNull($this->get(__PROPERTY__));
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?string $allowed_ips {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?string $sc_license_id {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }

    public ?string $sc_user_id {
        get => Types::stringOrNull($this->get(__PROPERTY__), true);
        set {
            $this->set(__PROPERTY__, $value);
        }
    }
}
